<?php


namespace App\CronJobs;


use App\Models\CompletedTransfer;
use App\Models\PaymentChannel;
use App\Models\TransactionReference;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Str;

class WithdrawalRequestCronJob
{
    private $secret_key;

    public function __construct()
    {
        $this->secret_key = PaymentChannel::find(1)->secret_key;
    }

    public function call()
    {
        try {
            $this->createRecipientCode();
            $this->initiateBulkTransfer();
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
    }

    private function createRecipientCode()
    {
        $withdrawals = WithdrawalRequest::PendingWithdrawals();
        foreach ($withdrawals as $withdrawal)
        {
            //give users recipient code
            if ($withdrawal->user->recipient_code == null)
            {
                $recipient[] = [
                    'type'=>'nuban',
                    'name'=>$withdrawal->account_name,
                    'account_number'=>$withdrawal->account_number,
                    'bank_code'=>$withdrawal->bank->code,
                    'currency'=>'NGN'
                ];
            }
        }

        if (!empty($recipient))
        {
            $url = "https://api.paystack.co/transferrecipient/bulk";
            $fields = [
                "batch" => (object)$recipient
            ];
            $fields_string = http_build_query($fields);
            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $this->secret_keyr",
                "Cache-Control: no-cache",
            ));

            //So that curl_exec returns the contents of the cURL; rather than echoing it
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            //execute post
            $result = curl_exec($ch);
            if ($result)
            {
                $result = json_decode($result, true);
                if ($result['data']) {
                    if (!empty($result['data']['success'])) {
                        $results = $result['data']['success'];
                        foreach ($results as $result)
                        {
                            $request = WithdrawalRequest::where('account_name', $result->name)
                                ->where('account_number', $result->details->account_number)
                                ->first();
                            $user = User::find($request->user_id);
                            $user->recipient_code = $result->recipient_code;
                            $user->save();
                        }
                    }
                }
            }
        }
    }

    private function initiateBulkTransfer()
    {
        $withdrawals = WithdrawalRequest::PendingWithdrawals();
        foreach ($withdrawals as $withdrawal)
        {
            //give users recipient code
            if ($withdrawal->user->recipient_code != null)
            {
                $reference = Str::lower(Str::random());

                $recipient[] = [
                    'amount'=>$withdrawal->amount,
                    'recipient'=>$withdrawal->user->recipient_code,
                    'reference'=>$reference
                ];

                TransactionReference::create([
                    'referenceable_id'=>$withdrawal->id,
                    'reference'=>$reference,
                    'referenceable_type'=>'App\Models\WithdrawalRequest'
                ]);
            }
        }
        if (!empty($recipient))
        {

            $url = "https://api.paystack.co/transfer/bulk";
            $fields = [
                "currency" => "NGN",
                "source" => "balance",
                "reason" => "Withdrawal request from account",
                "transfers" => (object)$recipient
            ];
            $fields_string = http_build_query($fields);
            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $this->secret_key",
                "Cache-Control: no-cache",
            ));

            //So that curl_exec returns the contents of the cURL; rather than echoing it
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            //execute post
            $result = curl_exec($ch);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['status']) {
                    if (!empty($result['data'])) {
                        foreach ($result['data'] as $datum)
                        {
                            $user = User::where('recipient_code', $datum->recipient)->first();
                            $withdrawal = WithdrawalRequest::where('user_id', $user->id)
                                ->where('status', 0);
                            CompletedTransfer::create([
                                'withdrawal_id'=>$withdrawal->id,
                                'transfer_code'=>$datum->transfer_code
                            ]);

                            //notify the user that his payment is on the way
                        }
                    }
                }
            }
        }
    }
}

(new WithdrawalRequestCronJob())->call();
