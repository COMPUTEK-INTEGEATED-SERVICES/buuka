<?php

use App\Http\Controllers\Action\NecessitiesAction;
use App\Http\Controllers\Plugins\TwilioPlugin;

if (! function_exists('general_settings')) {
    function general_settings()
    {
        return NecessitiesAction::generalSettings();
    }
}

if (! function_exists('send_sms')) {
    function send_sms($number, $message)
    {
        return (new TwilioPlugin())->notify($number, $message);
    }
}
