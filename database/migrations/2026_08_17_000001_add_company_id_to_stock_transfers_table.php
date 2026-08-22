<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * stock_transfers টেবিলে company_id যোগ করা।
     *
     * কেন দরকার:
     *   আগে stock_transfers টেবিলে কোনো company_id ছিল না।
     *   ফলে BelongsToCompany global scope কাজ করত না এবং
     *   controller-এ প্রতিটি query-তে product.company_id দিয়ে
     *   manual JOIN লাগত — যা ভুলে গেলেই IDOR হত।
     *
     * Backfill strategy:
     *   বিদ্যমান rows-এ product-এর company_id থেকে মান নেওয়া হয়।
     */
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')
                ->nullable()          // backfill শেষে NOT NULL করা হবে
                ->after('id');
        });

               // Backfill: বিদ্যমান rows-এ product.company_id থেকে মান নাও
        // (driver-agnostic — MySQL-এর JOIN-UPDATE সিনট্যাক্স sqlite-এ
        // চলে না, আর `php artisan test` সবসময় sqlite ব্যবহার করে)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE stock_transfers st
                INNER JOIN products p ON p.id = st.product_id
                SET st.company_id = p.company_id
            ');
        } else {
            DB::statement('
                UPDATE stock_transfers
                SET company_id = (SELECT company_id FROM products WHERE products.id = stock_transfers.product_id)
            ');
        }
        

        Schema::table('stock_transfers', function (Blueprint $table) {
            // Backfill শেষ, এখন NOT NULL + foreign key
            $table->unsignedBigInteger('company_id')
                ->nullable(false)
                ->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};