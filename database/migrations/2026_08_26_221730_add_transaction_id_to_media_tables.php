<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_distributions', function (Blueprint $table) {
            if (! Schema::hasColumn('media_distributions', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->after('notes')
                    ->constrained('transactions')->nullOnDelete();
            }
        });
        Schema::table('media_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('media_returns', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->after('notes')
                    ->constrained('transactions')->nullOnDelete();
            }
        });
        Schema::table('media_collections', function (Blueprint $table) {
            if (! Schema::hasColumn('media_collections', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->after('created_by')
                    ->constrained('transactions')->nullOnDelete();
            }
        });
    }
    public function down(): void
    {
        Schema::table('media_distributions', function (Blueprint $table) {
            if (Schema::hasColumn('media_distributions', 'transaction_id')) {
                $table->dropConstrainedForeignId('transaction_id');
            }
        });
        Schema::table('media_returns', function (Blueprint $table) {
            if (Schema::hasColumn('media_returns', 'transaction_id')) {
                $table->dropConstrainedForeignId('transaction_id');
            }
        });
        Schema::table('media_collections', function (Blueprint $table) {
            if (Schema::hasColumn('media_collections', 'transaction_id')) {
                $table->dropConstrainedForeignId('transaction_id');
            }
        });
    }
};
