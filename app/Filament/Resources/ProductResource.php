<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Http\Controllers\ProductController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('barcode')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mainId')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('brandId')
                    ->required()
                    ->options(Brand::all()->pluck('name', 'trendyolId')->toArray()) // Use trendyolId for options
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Fetch the brand's currency type based on the trendyolId
                        $brand = Brand::where('trendyolId', $state)->first(); // Searching by trendyolId
                        if ($brand) {
                            $set('currencyType', $brand->trendyolCurrency); // Assuming trendyolCurrency is the correct column
                        } else {
                            $set('currencyType', null); // Clear currencyType if no brand is found
                        }
                    }),

                Forms\Components\TextInput::make('currencyType')
                    ->required()
                    ->disabled(), // Optionally disable this field to make it read-only
                Forms\Components\Select::make('categoryId')
                    ->required()
                    ->label('Category')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Category::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'trendyolId')->toArray())
                    ->options(Category::all()->pluck('name', 'trendyolId'))
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $response = Http::get("https://api.trendyol.com/sapigw/product-categories/{$state}/attributes");

                            if ($response->successful()) {
                                $data = $response->json();
                                $attributes = $data['categoryAttributes'] ?? [];

                                $formattedAttributes = collect($attributes)
                                    ->map(function ($attribute) {
                                        return [
                                            'id' => $attribute['attribute']['id'],
                                            'name' => $attribute['attribute']['name'],
                                            'required' => $attribute['required'],
                                            'allowCustom' => $attribute['allowCustom'],
                                            'values' => collect($attribute['attributeValues'])
                                                ->pluck('name', 'id')
                                                ->toArray(),
                                        ];
                                    })
                                    ->toArray();

                                $set('dynamicAttributes', $formattedAttributes);
                            }
                        }
                    }),


                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stockCode')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('dimensionalWeight')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('listPrice')
                    ->required()
                    ->numeric()
                    ->reactive() // Enables reactive updates
                    ->afterStateUpdated(function ($state, callable $set) {
                        // If SalePrice is empty, set it to ListPrice
                        if ($state) {
                            $set('salePrice', $state);
                        }
                    }),
                Forms\Components\TextInput::make('salePrice')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('vatRate')
                    ->required()
                    ->native(false)
                    ->options([
                        0 => '%0',
                        1 => '%1',
                        10 => '%10',
                        20 => '%20',
                    ]),
                Forms\Components\Select::make('cargoCompanyId')
                    ->required()
                    ->options([
                        42 => 'DHL Marketplace',
                        38 => 'Sendeo Marketplace',
                        30 => 'Borusan Lojistik Marketplace',
                        14 => 'Cainiao Marketplace',
                        10 => 'MNG Kargo Marketplace',
                        19 => 'PTT Kargo Marketplace',
                        9 => 'Sürat Kargo Marketplace',
                        17 => 'Trendyol Express Marketplace',
                        6 => 'Horoz Kargo Marketplace',
                        20 => 'CEVA Marketplace',
                        4 => 'Yurtiçi Kargo Marketplace',
                        7 => 'Aras Kargo Marketplace',
                    ])->native(false),
                Forms\Components\FileUpload::make('images')
                    ->required()
                    ->multiple()
                    ->maxFiles(5)
                    ->image()
                    ->disk('public') // or whichever disk you're using
                    ->directory('product-images'), // specify the directory where images will be stored
                Forms\Components\Hidden::make('dynamicAttributes'),

                Forms\Components\Section::make('Product Attributes')
                    ->schema(function (callable $get) {
                        $attributes = $get('dynamicAttributes') ?? [];

                        return collect($attributes)->map(function ($attribute) {
                            // Assuming 'name' is a string for the label and 'id' is used as the key
                            $field = Forms\Components\Select::make("attributes.{$attribute['id']}")
                                ->label($attribute['name'])
                                ->required($attribute['required'])// Adjust based on your actual relationship; this is an example
                                ->dehydrated()
                                ->preload(); // Preload options for better UX


                            if (!empty($attribute['values'])) {
                                // If attribute values are provided, set them as options
                                $field->options(array_combine($attribute['values'], $attribute['values']));
                            }

                            if ($attribute['allowCustom']) {
                                $field = Forms\Components\TextInput::make("attributes.{$attribute['id']}_custom")
                                    ->label($attribute['name'])
                                    ->required($attribute['required']);
                            }

                            return $field;
                        })->toArray();
                    })
                    ->columns(2)
                    ->visible(fn (callable $get) => !empty($get('dynamicAttributes')))
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
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mainId')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brandId')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('categoryId')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stockCode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dimensionalWeight')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currencyType')
                    ->searchable(),
                Tables\Columns\TextColumn::make('listPrice')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salePrice')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vatRate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cargoCompanyId')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('images')
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Publish Trendyol')
                    ->action(fn (Product $record) => ProductController::sendPostRequest($record))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
