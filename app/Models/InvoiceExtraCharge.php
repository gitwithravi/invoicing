<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceExtraCharge extends Model
{
    protected $fillable = ['invoice_id', 'type', 'name', 'amount'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
