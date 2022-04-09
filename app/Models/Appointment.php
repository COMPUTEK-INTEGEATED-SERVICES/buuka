<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'scheduled', 'book_id', 'status', 'vendor_id'
    ];

    public function book()
    {
        return $this->hasOne(Book::class, 'id', 'book_id')->with('products');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public static function today(Vendor $vendor)
    {
        return self::with('book')->where('vendor_id', $vendor->id)
            ->where('scheduled', Carbon::today())
            ->get();
    }
}
