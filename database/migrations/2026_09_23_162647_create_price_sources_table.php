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
        Schema::create('price_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('publisher');
            $table->string('type', 30); // government, local_survey, internal
            $table->string('document_type', 30); // xlsx, pdf, csv, html
            $table->string('url', 2048);
            $table->unsignedSmallInteger('reference_year');
            $table->string('status')->default('active');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_sources');
    }
};
