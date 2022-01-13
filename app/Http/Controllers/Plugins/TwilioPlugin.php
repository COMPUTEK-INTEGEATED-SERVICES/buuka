<?php


namespace App\Http\Controllers\Plugins;


use Twilio\Rest\Client;

class TwilioPlugin
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function notify(string $number, string $message): void
    {
        try {
            $this->client->messages->create('+'.$number, [
                'from' => getenv('TWILIO_FROM'),
                'body' => $message
            ]);
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
    }
}
