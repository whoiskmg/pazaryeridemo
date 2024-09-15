<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('barcode');
            $table->string('title');
            $table->string('mainId');
            $table->integer('brandId');
            $table->integer('categoryId');
            $table->integer('quantity');
            $table->string('stockCode');
            $table->string('dimensionalWeight');
            $table->string('description');
            $table->string('currencyType')->default('USD');
            $table->integer('listPrice');
            $table->integer('salePrice');
            $table->integer('vatRate');
            $table->integer('cargoCompanyId');
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
