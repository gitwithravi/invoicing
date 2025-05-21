<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->maxLength(255),

                    ]),
                    Forms\Components\Section::make('Address Information')

                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->maxLength(255),
                    ]),
                    Forms\Components\Section::make('Tax Information')

                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tax_identifier_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tax_identifier_number')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label('Customer Name')
                ->searchable(),

                Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                ->label('Phone'),

                Tables\Columns\TextColumn::make('address')
                ->label('Address')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city')
                ->label('City')
                ->toggleable(isToggledHiddenByDefault: true)    ,

                Tables\Columns\TextColumn::make('state')
                ->label('State')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('zip')
                ->label('Zip')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('country')
                ->label('Country')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('business_name')
                ->label('Business Name')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tax_identifier_name')
                ->label('Tax Identifier Name')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tax_identifier_number')
                ->label('Tax Identifier Number')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                ->label('Created At')
                ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                ->label('Updated At')
                ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()
                ->slideOver(),
                Tables\Actions\DeleteAction::make()->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            //'create' => Pages\CreateCustomer::route('/create'),
            //'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
