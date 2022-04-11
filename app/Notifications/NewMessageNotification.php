<?php

namespace App\Notifications;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use NotificationChannels\PusherPushNotifications\PusherChannel;
use NotificationChannels\PusherPushNotifications\PusherMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    /**
     * @var Chat
     */
    private $chat;
    /**
     * @var User
     */
    private $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, Chat $chat)
    {
        $this->chat = $chat;
        $this->user = $user;
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
                    ->subject($this->user->first_name." sent you a message")
                    ->line($this->chat->type == 'text'?$this->chat->message: new HtmlString('<a href="#"><strong>file</strong></a>'))
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
            'subject'=>'Cancelled Book',
            'message'=>"You cancelled a book",
            'action'=>''
        ];
    }


    public function toPushNotification($notifiable)
    {
        $message = "You have a new message!";
        $title = $this->user->first_name." sent you a message";

        return PusherMessage::create()
            ->iOS()
            ->badge(1)
            ->body($title)
            ->withAndroid(
                PusherMessage::create()
                    ->title($message)
                    ->icon('icon')
            );
    }
}
