<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    private static function getDataFromThirdPartyAPI(): array
    {
        $response = Http::get('https://api.trendyol.com/sapigw/product-categories');
        $data = $response->json();
        $categories = self::processCategories($data['categories']);

        $array = array_map(function($id, $category) {
            return [
                'id' => $category['id'],
                'name' => $category['name'],
            ];
        }, array_keys($categories), $categories);
        return collect($array)
            ->mapWithKeys(function ($item) {
                return [$item['id'] => $item['name']];
            })
            ->toArray();
    }

    private static function processCategories(array $categories): array
    {
        $result = [];
        foreach ($categories as $category) {
            $result[$category['id']] = [
                'id' => $category['id'],
                'name' => $category['name'],
            ];

            if (!empty($category['subCategories'])) {
                $result = array_merge($result, self::processCategories($category['subCategories']));
            }
        }
        return $result;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('trendyolId')
                    ->label('Trendyol Category')
                    ->required()
                    ->options(static::getDataFromThirdPartyAPI())
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trendyolId')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
