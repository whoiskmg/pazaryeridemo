<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $casts = [
        'images' => 'array',
    ];

    protected $fillable = [
        'barcode',
        'title',
        'mainId',
        'brandId',
        'categoryId',
        'quantity',
        'stockCode',
        'dimensionalWeight',
        'description',
        'currencyType',
        'listPrice',
        'salePrice',
        'vatRate',
        'cargoCompanyId',
        'images',
        'attributes',
    ];
}
