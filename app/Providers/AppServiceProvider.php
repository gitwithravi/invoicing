<?php
namespace App\Providers;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Observers\CustomerObserver;
use App\Observers\ConnectedAccountObserver;
use Filament\Tables\Columns\Column;
use Filament\Forms\Components\Field;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Filament\Infolists\Components\Entry;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Components\Component;
use ChrisReedIO\Socialment\Facades\Socialment;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    protected function translatableComponents(): void
    {
        foreach ([Field::class, BaseFilter::class, Placeholder::class, Column::class, Entry::class] as $component) {
            /* @var Configurable $component */
            $component::configureUsing(function (Component $translatable): void {
                /** @phpstan-ignore method.notFound */
                $translatable->translateLabel();
            });
        }
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    private function configureModels(): void
    {
        Model::shouldBeStrict(! app()->isProduction());
    }

    public function boot(): void
    {
        $this->configureCommands();
        $this->configureModels();
        $this->translatableComponents();

        // Register the CustomerObserver
        Customer::observe(CustomerObserver::class);

        // Register the ConnectedAccountObserver to prevent creation when user_id is null
        ConnectedAccount::observe(ConnectedAccountObserver::class);

        // Handle blocked OAuth login attempts (this should rarely trigger now)
        Socialment::postLogin(function (ConnectedAccount $account) {
            $user = $account->user;

            // This should not happen anymore due to our observers, but just in case
            if (! $user) {
                \Log::error('ConnectedAccount created without user despite observers', [
                    'account_id' => $account->id,
                    'provider' => $account->provider,
                    'email' => $account->email
                ]);

                // Clean up the connected account that was created
                $account->delete();

                Session::flash('error', 'Access denied. Only existing users can log in via social authentication.');
                return redirect()->route('filament.admin.auth.login');
            }

            // Login successful for existing user
            return null;
        });
    }
}
