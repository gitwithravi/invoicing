<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'payment_reference',
        'payment_note',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
