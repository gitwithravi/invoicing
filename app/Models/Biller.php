<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biller extends Model
{
    protected $fillable = [
        'business_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'tax_identifier_name',
        'tax_identifier_number',
        'logo',
        'website',
        'currency',
        'signature_name',
        'signature_image',
    ];



}
