<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('amount'),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('payment_date')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\TextColumn::make('payment_reference'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('add_payment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-check-circle')
                    ->visible(function() {
                        $invoice = $this->getOwnerRecord();
                        return $invoice->status == 'unpaid' || $invoice->status == 'partially_paid';
                    })
                    ->form([
                        Forms\Components\TextInput::make('amount_paid')
                            ->required()
                            ->numeric()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\TextInput::make('payment_method')
                            ->required()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now())
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\TextInput::make('payment_reference')
                            ->required()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                    ])
                    ->action(function (array $data) {
                        $record = $this->getOwnerRecord();
                        Log::info("Amount Due: ");
                        if($record->amount_due == NULL)
                        {
                            $record->amount_due = $record->total_amount;
                        }
                        if($data['amount_paid'] > ($record->amount_due ))
                        {
                            Log::info("Amount Paid: " . $data['amount_paid']);
                            Log::info("Amount Due: " . $record->amount_due);
                            Notification::make()
                            ->title('Amount Paid is greater than Amount Due')
                            ->danger()
                            ->send();
                            return;
                        }
                        $amount_paid = 0;
                        if($record->amount_paid != NULL)
                        {
                            $amount_paid = $record->amount_paid;
                        }
                        $amount_paid += $data['amount_paid'];
                        $record->update(['status' => 'partially_paid', 'amount_paid' => $amount_paid, 'amount_due' => $record->total_amount - $amount_paid]);
                        if ($record->amount_due == 0) {
                            $record->update(['status' => 'paid']);
                        }
                        $record->payments()->create([
                            'amount' => $data['amount_paid'],
                            'payment_method' => $data['payment_method'],
                            'payment_date' => $data['payment_date'],
                            'payment_status' => 'paid',
                            'payment_reference' => $data['payment_reference'],
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\TextInput::make('payment_method')
                            ->required()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\TextInput::make('payment_reference')
                            ->required()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                    ])
                    ->using(function ($record, array $data) {
                        $invoice = $this->getOwnerRecord();
                        $oldAmount = $record->amount;
                        $newAmount = $data['amount'];
                        $difference = $newAmount - $oldAmount;

                        // Update payment record
                        $record->update($data);

                        // Update invoice amounts
                        $newAmountPaid = $invoice->amount_paid + $difference;
                        $newAmountDue = $invoice->total_amount - $newAmountPaid;

                        // Determine status
                        $status = 'unpaid';
                        if ($newAmountPaid > 0 && $newAmountDue > 0) {
                            $status = 'partially_paid';
                        } elseif ($newAmountDue <= 0) {
                            $status = 'paid';
                            $newAmountDue = 0;
                        }

                        $invoice->update([
                            'amount_paid' => $newAmountPaid,
                            'amount_due' => $newAmountDue,
                            'status' => $status
                        ]);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->using(function ($record) {
                        $invoice = $this->getOwnerRecord();
                        $paymentAmount = $record->amount;

                        // Delete payment record first
                        $record->delete();

                        // Update invoice amounts
                        $newAmountPaid = $invoice->amount_paid - $paymentAmount;
                        $newAmountDue = $invoice->total_amount - $newAmountPaid;

                        // Determine status
                        $status = 'unpaid';
                        if ($newAmountPaid > 0 && $newAmountDue > 0) {
                            $status = 'partially_paid';
                        } elseif ($newAmountDue <= 0) {
                            $status = 'paid';
                            $newAmountDue = 0;
                        }

                        $invoice->update([
                            'amount_paid' => max(0, $newAmountPaid), // Ensure non-negative
                            'amount_due' => $newAmountDue,
                            'status' => $status
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
