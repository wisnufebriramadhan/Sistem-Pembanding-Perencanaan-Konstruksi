<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original deployment may have completed this first ALTER TABLE
        // before MySQL rejected the later index removal. Keep the migration
        // safe to rerun from that partial state.
        if (! Schema::hasColumn('price_candidates', 'crawl_request_id')) {
            Schema::table('price_candidates', function (Blueprint $table) {
                $table->foreignId('crawl_request_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        Schema::table('price_candidates', function (Blueprint $table) {
            // The old composite unique key is also the supporting index for the
            // price_source_id foreign key on MySQL, so add a replacement first.
            $table->index('price_source_id');
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
            $table->dropIndex(['price_source_id']);
        });
    }
};
