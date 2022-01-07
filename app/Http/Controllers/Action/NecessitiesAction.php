<?php


namespace App\Http\Controllers\Action;


use App\Models\GeneralSetting;

class NecessitiesAction
{
    public static function generalSettings()
    {
        return GeneralSetting::first();
    }
}
