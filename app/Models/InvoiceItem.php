<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_name',
        'unit_price',
        'quantity',
        'total_price',
        'tax_name',
        'tax_rate',
        'total_tax_amount',
        'amount_with_tax',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);

    }

}
