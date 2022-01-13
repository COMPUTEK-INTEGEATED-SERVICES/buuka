<?php

namespace App\Notifications\Order;

use App\Models\Book;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserBookCompleteNotification extends Notification
{
    use Queueable;

    /**
     * @var Book
     */
    private $book;
    /**
     * @var Vendor
     */
    private $vendor;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Book $book, Vendor $vendor)
    {
        $this->book = $book;
        $this->vendor = $vendor;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
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
                    ->line("Hi $notifiable->first_name, Your order has been sent to {$this->vendor->business_name}")
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
            'subject'=>'Order received',
            'message'=>"{$this->vendor->business_name} has received your order",
            'action'=>''
        ];
    }
}
