<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Invoice;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\InvoiceResource;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('finalize')
                ->label('Finalize')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(Invoice $record): bool => $record->status === 'draft')
                ->action(function (Invoice $record) {
                    $record->status = 'unpaid';
                    $record->save();
                    Notification::make()
                        ->title('Invoice finalized successfully')
                        ->success()
                        ->send();
                    $record->refresh();
                }),
        ];
    }
}
