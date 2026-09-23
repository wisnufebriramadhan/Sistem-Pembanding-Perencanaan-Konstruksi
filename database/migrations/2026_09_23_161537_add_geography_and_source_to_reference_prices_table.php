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
        Schema::table('reference_prices', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('source_type', 30)->default('government')->after('source_name');
            $table->unsignedTinyInteger('priority')->default(30)->after('source_type');
            $table->string('source_url')->nullable()->after('source_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_prices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn(['source_type', 'priority', 'source_url']);
        });
    }
};
