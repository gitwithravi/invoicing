<?php

namespace App\Filament\Resources\CustomerGroupResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Customer;
use Filament\Notifications\Notification;

class CustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
                Tables\Actions\Action::make('bulkAttach')
                    ->label('Bulk Attach')
                    ->icon('heroicon-o-paper-clip')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('emails')
                            ->label('Email Addresses')
                            ->placeholder('Enter comma-separated email addresses (e.g., john@example.com, jane@example.com)')
                            ->required()
                            ->rows(5)
                            ->helperText('Enter email addresses separated by commas. Customers with these emails will be attached to this customer group.'),
                    ])
                    ->action(function (array $data) {
                        $emails = array_map('trim', explode(',', $data['emails']));
                        $emails = array_filter($emails, fn($email) => !empty($email));

                        $foundEmails = [];
                        $notFoundEmails = [];
                        $alreadyAttachedEmails = [];

                        foreach ($emails as $email) {
                            // Validate email format
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $notFoundEmails[] = $email;
                                continue;
                            }

                            $customer = Customer::where('email', $email)->first();

                            if ($customer) {
                                // Check if customer is already attached to this group
                                if ($this->getOwnerRecord()->customers()->where('customer_id', $customer->id)->exists()) {
                                    $alreadyAttachedEmails[] = $email;
                                } else {
                                    // Attach customer to the group
                                    $this->getOwnerRecord()->customers()->attach($customer->id);
                                    $foundEmails[] = $email;
                                }
                            } else {
                                $notFoundEmails[] = $email;
                            }
                        }

                        // Build notification message
                        $messages = [];

                        if (!empty($foundEmails)) {
                            $messages[] = count($foundEmails) . ' customer(s) attached successfully: ' . implode(', ', $foundEmails);
                        }

                        if (!empty($alreadyAttachedEmails)) {
                            $messages[] = count($alreadyAttachedEmails) . ' customer(s) already attached: ' . implode(', ', $alreadyAttachedEmails);
                        }

                        if (!empty($notFoundEmails)) {
                            $messages[] = 'The following email IDs not found: ' . implode(', ', $notFoundEmails);
                        }

                        $title = 'Bulk Attach Complete';
                        $color = 'success';

                        // If there were any issues, change the notification style
                        if (!empty($notFoundEmails) || !empty($alreadyAttachedEmails)) {
                            $color = !empty($foundEmails) ? 'warning' : 'danger';
                        }

                        Notification::make()
                            ->title($title)
                            ->body(implode('<br>', $messages))
                            ->color($color)
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
