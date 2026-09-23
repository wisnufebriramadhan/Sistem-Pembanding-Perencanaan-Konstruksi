<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_candidates', function (Blueprint $table) {
            $table->foreignId('crawl_request_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique('price_candidates_price_source_id_external_key_unique');
            $table->unique(['crawl_request_id', 'price_source_id', 'external_key'], 'price_candidates_crawl_source_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('price_candidates', function (Blueprint $table) {
            $table->dropUnique('price_candidates_crawl_source_key_unique');
            $table->dropConstrainedForeignId('crawl_request_id');
            $table->unique(['price_source_id', 'external_key']);
        });
    }
};
