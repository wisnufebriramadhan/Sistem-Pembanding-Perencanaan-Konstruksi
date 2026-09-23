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
        Schema::create('comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reference_price_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('unit', 30);
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('reference_unit_price', 18, 2)->default(0);
            $table->decimal('correction_multiplier', 8, 4)->default(1);
            $table->decimal('estimated_unit_price', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comparison_items');
    }
};
