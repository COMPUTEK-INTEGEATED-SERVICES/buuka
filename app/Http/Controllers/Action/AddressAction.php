<?php


namespace App\Http\Controllers\Action;


use ipinfo\ipinfo\IPinfo;

class AddressAction
{
    /**
     * @var IPinfo
     */
    private $instance;

    public function __construct()
    {
        $this->instance = new IPinfo(env("IPINFO_ACCESS_TOKEN"));
    }

    public function getIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function getDetails($useServerIP = false): \ipinfo\ipinfo\Details
    {
        return $this->instance->getDetails(!$useServerIP?$this->getIP():null);
    }
}
