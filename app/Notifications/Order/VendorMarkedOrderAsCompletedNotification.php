<?php

namespace App\Notifications\Order;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\PusherPushNotifications\PusherChannel;
use NotificationChannels\PusherPushNotifications\PusherMessage;
use Rich2k\PusherBeams\PusherBeams;
use Rich2k\PusherBeams\PusherBeamsMessage;

class VendorMarkedOrderAsCompletedNotification extends Notification
{
    use Queueable;

    private $vendor;
    private $book;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($vendor, $book)
    {
        $this->vendor = $vendor;
        $this->book = $book;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', PusherChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function toDatabase($notifiable):array
    {
        return [
            'subject'=>'Order Completed',
            'message'=>"Vendor has marked this order as completed",
            'action'=>''
        ];
    }

    public function toPusherBeamsNotification($notifiable)
    {
        return PusherBeamsMessage::create()
            ->android()
            ->sound('success')
            ->body("Your {$notifiable->service} account was approved!")
            ->withiOS(PusherBeamsMessage::create()
                ->body("Your {$notifiable->service} account was approved!")
                ->badge(1)
                ->sound('success')
            );
    }

    public function toPushNotification($notifiable)
    {
        $message = "Book Successful!";

        return PusherMessage::create()
            ->iOS()
            ->badge(1)
            ->body($message)
            ->withAndroid(
                PusherMessage::create()
                    ->title($message)
                    ->icon('icon')
            );
    }
}
