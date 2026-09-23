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
        Schema::create('crawl_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->json('sources');
            $table->json('items');
            $table->string('status')->default('queued');
            $table->unsignedInteger('bank_hit_count')->default(0);
            $table->unsignedInteger('queued_item_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_requests');
    }
};
