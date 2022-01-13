<?php


namespace App\CronJobs;


use App\Models\GiftCard;
use App\Models\GiftCardPurchase;
use App\Models\Resource;
use App\Notifications\GiftCard\NewBuukaGiftCardWasSentToYouNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class GiftCardNotificationAndSMSSenderCronJob
{
    public function call()
    {
        $giftcards = GiftCardPurchase::where('status', 1)->get();

        foreach ($giftcards as $giftcard)
        {
            if ($giftcard->delivery_day == Carbon::today())
            {
                $code = GiftCard::where('purchase_id', $giftcard->id)
                    ->select('code')
                    ->get();
                try {
                    $image = Resource::were('resourceable_id', $giftcard->id)
                        ->where('resourceable_type', 'App\Models\GiftCardPurchase')
                        ->first()
                        ->path;
                    if ($giftcard->delivery = 'email')
                    {
                        Notification::route('mail', [
                            $giftcard->to => $giftcard->from,
                        ])->notify(new NewBuukaGiftCardWasSentToYouNotification($code, $image));
                    }
                    elseif ($giftcard->delivery = 'sms')
                    {
                        $after = "Hope you enjoy this Buuka Gift Card(s)! - ".implode(', ', $code)."\n From $giftcard->from";
                        send_sms($giftcard->to, $giftcard->message.$after);
                    }else{
                        Notification::route('mail', [
                            $giftcard->to => $giftcard->from,
                        ])->notify(new NewBuukaGiftCardWasSentToYouNotification($code, $image));

                        $after = "Hope you enjoy this Buuka Gift Card(s)! - ".implode(', ', $code)."\n From $giftcard->from";
                        send_sms($giftcard->to, $giftcard->message.$after);
                    }
                    $g = GiftCardPurchase::find($giftcard->id);
                    $g->status = 2;
                    $g->save();
                }catch (\Throwable $throwable)
                {
                    report($throwable);
                }
            }
        }
    }
}

(new GiftCardNotificationAndSMSSenderCronJob())->call();
