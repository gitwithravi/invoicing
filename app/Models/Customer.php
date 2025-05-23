<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'tax_identifier_name',
        'tax_identifier_number',
        'user_id',
        'business_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customerGroups()
    {
        return $this->belongsToMany(CustomerGroup::class);
    }


}
