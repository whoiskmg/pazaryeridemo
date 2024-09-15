<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    private static function getDataFromThirdPartyAPI(string $search): array
    {
        $response = Http::get('https://api.trendyol.com/sapigw/brands/by-name?name=' . urlencode($search));
        $data = $response->json();
        $array = array_map(function($id, $data) {
            return [
                'id' => $data['id'],
                'name' => $data['name'],
            ];
        }, array_keys($data), $data);
        return collect($array)
            ->mapWithKeys(function ($item) {
                return [$item['id'] => $item['name']];
            })
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('trendyolId')
                    ->label('Trendyol Brand')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        $brands = static::getDataFromThirdPartyAPI($search);
                        return collect($brands)->toArray();
                    })
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('trendyolId', (int)$state);
                    }),
                Forms\Components\Select::make('trendyolCurrency')
                    ->required()
                    ->options([
                        'TRY' => 'TRY',
                    ])
                    ->native(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trendyolId')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trendyolCurrency')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
