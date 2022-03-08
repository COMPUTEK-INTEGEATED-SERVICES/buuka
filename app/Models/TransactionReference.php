<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'referenceable_id', 'store_card_id', 'reference', 'referenceable_type'
    ];

    public function store_card(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StoreCard::class, 'store_card_id', 'id');
    }

    public function book(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id', 'id');
    }
}
