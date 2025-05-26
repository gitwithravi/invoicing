<?php
namespace App\Filament\Resources\CustomerResource\Pages;

use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CustomerImporter;
use App\Filament\Resources\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
            Actions\ImportAction::make()
                ->importer(CustomerImporter::class)
                ->color('primary'),
        ];
    }
}
