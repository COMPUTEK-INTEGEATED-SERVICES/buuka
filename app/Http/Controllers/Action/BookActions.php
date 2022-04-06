<?php


namespace App\Http\Controllers\Action;


use App\Events\Order\UserBookSuccessfulEvent;
use App\Http\Controllers\API\EscrowController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\WalletController;
use App\Models\Appointment;
use App\Models\Book;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductBookRelation;
use App\Models\TransactionReference;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Order\UserBookCompleteNotification;
use App\Notifications\Order\UserBookSuccessfulNotification;
use App\Notifications\Order\UserMarkedOrderAsCompletedNotification;
use App\Notifications\Order\VendorMarkedOrderAsCompletedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookActions
{
    public function createFixedBook($request, $user)
    {
        DB::beginTransaction();
        try {
            $book = Book::create([
                'user_id'=>$user->id,
                'vendor_id'=>$request->vendor_id,
                'product_id'=>json_encode($request->input('product_id')),
                'schedule'=>Carbon::make($request->input('scheduled')),
                'note'=>$request->input('note'),
                'type'=>'fixed'
            ]);

            //here i will want to get the total amount
            $total_amount = 0;
            foreach ($request->input('product_id') as $p)
            {
                $product = Product::find($p);
                $total_amount = $total_amount + $product->price;
                ProductBookRelation::create([
                    'book_id'=>$book->id,
                    'product_id'=>$product->id
                ]);
            }

            $book->amount = $total_amount;
            $book->save();

            $ref = Str::random();
            TransactionReference::create([
                'referenceable_id'=>$book->id,
                'store_card_id'=>0,
                'reference'=>$ref,
                'referenceable_type'=>'App\Models\Book'
            ]);

            $link = (new PaymentController())->initiateFlutterwaveForBook($ref);

            if ($link)
            {
                $user->notify(new UserBookSuccessfulNotification($book));
                broadcast( new UserBookSuccessfulEvent($book, $user));

                DB::commit();
                return [
                    'book'=>Book::with('reference')->find($book->id),
                    'link'=>$link
                ];
            }
        }catch (\Throwable $throwable){
            report($throwable);
            DB::rollBack();
        }
        return false;
    }

    public function createCustomBook($book, $user)
    {
        try {
            $booked =  Book::create([
                'user_id'=>$user->id,
                'vendor_id'=>$book->vendor_id,
                'product_id'=>json_encode([$book->product_id]),
                'schedule'=>$book->scheduled,
                'amount'=>$book->amount,
                'note'=>$book->extras,
                'type'=>'custom',
                'proposed_by'=>($book->vendor_id == $book->user_id)?'vendor':'client'
            ]);

            //here i will want to get the total amount
            $product = Product::find($book->product_id);
            $total_amount = $product->price;
            ProductBookRelation::create([
                'book_id'=>$book->id,
                'product_id'=>$product->id
            ]);

            $booked->amount = $total_amount;
            $booked->save();

            TransactionReference::create([
                'referenceable_id'=>$booked->id,
                'store_card_id'=>0,
                'reference'=>Str::random(),
                'referenceable_type'=>'App\Models\Book'
            ]);

            return Book::with('reference')->find($booked->id);
        }catch (\Throwable $throwable){
            report($throwable);
        }
        return false;
    }

    public function markBookAsPaid($book_id)
    {
        $book = Book::find($book_id);
        if($book->status == 1)
        {
            return true;
        }
        //mark the bok as done
        $book->status = 1;
        //get the vendor and alert them
        try {
            $vendor = Vendor::find($book->vendor_id);
            User::find($book->user_id)->notify(new UserBookCompleteNotification($book, $vendor));
            User::find($vendor->user_id)->notify(new UserBookCompleteNotification($book, $vendor));

            //before returning result, save the vendor's client
            Client::firstOrNew([
                'user_id'=>$book->user_id,
                'vendor_id'=>$book->vendor_id
            ]);

            Appointment::create([
                'user_id'=>$this->user->id,
                'vendor_id'=>$book->vendor_id,
                'scheduled'=>$book->schedule,
                'book_id'=>$book->id
            ]);

            //move money to escrow account
            EscrowController::addFund($book->vendor_id, 'vendor', $book->amount);
            //finally save the book
            return $book->save();
        }catch (\Throwable $throwable)
        {
            report($throwable);
            return false;
        }
    }

    public function markOrderAsComplete($book_id, $user)
    {
        try {
            $book = Book::find($book_id);

            $vendor = Vendor::find($book->vendor_id);
            if ($user->can('participate', $book, $vendor))
            {
                if ($book->status == 1)
                {
                    //notify the vendor that a user marked order as paid or vise versa
                    User::find($book->user_id)->notify(new VendorMarkedOrderAsCompletedNotification($vendor, $book));
                    User::find($book->vendor_id)->notify(new UserMarkedOrderAsCompletedNotification($book));

                    //change book status to complete
                    $book->status = 2;

                    //we credit vendor from escrow
                    EscrowController::subtractFund($vendor->user_id, 'vendor', $book->amount);
                    WalletController::credit($vendor->user_id, 'vendor', $book->amount);

                    $book->save();
                    return true;
                }
            }
        }catch (\Throwable $throwable){
            report($throwable);
        }
        return false;
    }
}
