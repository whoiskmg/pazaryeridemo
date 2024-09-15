<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Http\Controllers\ProductController;
use App\Models\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getFormActions(): array
    {
        return array_merge(parent::getFormActions(), [
            Actions\Action::make('Trendyol Update')
                ->action(function (Product $record, array $data): void {
                    // Store the original data before updating
                    $originalData = $record->attributesToArray();
                    $barcode = $originalData['barcode'];
                    $brandId = $originalData['brandId'];
                    // Update the record with the new data
                    $record->fill($this->form->getState());
                    $record->save();
                    // Get the updated data
                    $updatedData = $record->fresh()->attributesToArray();

                    // Compare the differences
                    $differences = $this->compareArraysRecursively($originalData, $updatedData);
                    ProductController::sendUpdateRequest($record, $barcode, );
                    if (empty($differences)) {
                        Notification::make()
                            ->title('No changes detected')
                            ->info()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Product updated')
                            ->success()
                            ->send();

                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Update Product on Trendyol')
                ->modalDescription('Are you sure you want to update this product on Trendyol? This action will save the current form data.')
                ->modalSubmitActionLabel('Yes, update product')
        ],
        );
    }
}
