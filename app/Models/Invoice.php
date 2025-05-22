<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'biller_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'amount_paid',
        'amount_due',
        'status',
        'payment_details',
        'terms',
    ];

    public function biller()
    {
        return $this->belongsTo(Biller::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function extraCharges()
    {
        return $this->hasMany(InvoiceExtraCharge::class);
    }
}
