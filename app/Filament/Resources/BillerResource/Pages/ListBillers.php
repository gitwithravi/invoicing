<?php

namespace App\Filament\Resources\BillerResource\Pages;

use App\Filament\Resources\BillerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillers extends ListRecords
{
    protected static string $resource = BillerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
        ];
    }
}
