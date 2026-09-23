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
        Schema::create('reference_prices', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->nullable()->index();
            $table->string('name');
            $table->string('category')->default('material');
            $table->string('unit', 30);
            $table->decimal('unit_price', 18, 2);
            $table->string('source_name');
            $table->unsignedSmallInteger('source_year');
            $table->string('region')->nullable();
            $table->date('effective_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_prices');
    }
};
