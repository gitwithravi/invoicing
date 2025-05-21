<?php

namespace App\Filament\Resources\BillerResource\Pages;

use App\Filament\Resources\BillerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBiller extends EditRecord
{
    protected static string $resource = BillerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
