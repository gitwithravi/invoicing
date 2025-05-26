<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerObserver
{
    /**
     * Handle the Customer "creating" event.
     */
    public function creating(Customer $customer): void
    {
        // Check if a user exists with the customer's email
        if ($customer->email) {
            $existingUser = User::where('email', $customer->email)->first();

            if ($existingUser) {
                // User exists, set the user_id to the existing user's id
                $customer->user_id = $existingUser->id;
            } else {
                // No user exists, create a new user
                $newUser = User::create([
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'password' => Hash::make(Str::random(14)), // You might want to generate a random password
                    'user_type' => 'user',
                ]);

                // Set the user_id to the newly created user's id
                $customer->user_id = $newUser->id;
            }
        }
    }
}
