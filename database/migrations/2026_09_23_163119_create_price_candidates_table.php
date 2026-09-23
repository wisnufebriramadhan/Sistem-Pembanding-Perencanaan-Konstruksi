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
        Schema::create('price_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_key')->nullable();
            $table->string('name');
            $table->string('unit', 30);
            $table->decimal('unit_price', 18, 2);
            $table->json('raw_payload')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('received_at');
            $table->unique(['price_source_id', 'external_key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_candidates');
    }
};
