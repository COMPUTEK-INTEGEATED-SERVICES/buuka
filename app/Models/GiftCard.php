<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    public static function debit($id, $amount)
    {
        $g = self::find($id);
        $g->balance = $g->balance - $amount;
        return $g->save();
    }

    public static function byCode($code)
    {
        return self::where('code', $code);
    }
}
