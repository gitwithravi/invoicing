<?php

namespace App\Observers;

use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ConnectedAccountObserver
{
    /**
     * Handle the ConnectedAccount "creating" event.
     */
    public function creating(ConnectedAccount $connectedAccount): bool
    {
        // If user_id is null, it means user creation was blocked
        if (is_null($connectedAccount->user_id)) {
            Log::warning('ConnectedAccount creation blocked - user_id is null', [
                'email' => $connectedAccount->email,
                'provider' => $connectedAccount->provider,
                'provider_user_id' => $connectedAccount->provider_user_id,
            ]);

            // Throw an exception to abort the OAuth flow completely
            throw new \Exception('Access denied. Only existing users can log in via social authentication.');
        }

        return true;
    }

    /**
     * Handle the ConnectedAccount "created" event.
     */
    public function created(ConnectedAccount $connectedAccount): void
    {
        //
    }

    /**
     * Handle the ConnectedAccount "updated" event.
     */
    public function updated(ConnectedAccount $connectedAccount): void
    {
        //
    }

    /**
     * Handle the ConnectedAccount "deleted" event.
     */
    public function deleted(ConnectedAccount $connectedAccount): void
    {
        //
    }

    /**
     * Handle the ConnectedAccount "restored" event.
     */
    public function restored(ConnectedAccount $connectedAccount): void
    {
        //
    }

    /**
     * Handle the ConnectedAccount "force deleted" event.
     */
    public function forceDeleted(ConnectedAccount $connectedAccount): void
    {
        //
    }
}
