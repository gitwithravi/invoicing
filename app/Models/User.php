<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Request;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        /* TODO: Please implement your own logic here. */
        return true; // str_ends_with($this->email, '@larament.test');
    }

    /**
     * Boot the model and set up event listeners
     */
    protected static function boot(): void
    {
        parent::boot();

        // Prevent user creation during OAuth flows if user doesn't already exist
        static::creating(function (User $user) {
            // Check if this is an OAuth callback request by examining the current route
            $currentRoute = Request::route();

            if ($currentRoute && str_contains($currentRoute->uri(), 'login/') && str_contains($currentRoute->uri(), 'callback')) {
                // This is an OAuth callback - prevent user creation
                // Log the attempt for debugging
                \Log::warning('OAuth user creation blocked', [
                    'email' => $user->email,
                    'route' => $currentRoute->uri(),
                    'ip' => Request::ip()
                ]);

                // Prevent the user from being created by returning false
                return false;
            }

            return true;
        });
    }
}
