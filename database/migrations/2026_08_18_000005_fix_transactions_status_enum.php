<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The transactions.status column was created as
 * ENUM('Draft','Posted','Cancelled') with a default of 'Posted'.
 *
 * The voucher workflow (Transaction::STATUS_*) needs five states:
 * Draft, Submitted, Approved, Posted, Cancelled. Without this fix,
 * submitting a draft for approval fails because 'Submitted' is not
 * a valid value for the column, and the dangerous 'Posted' default
 * means any insert that skips status would silently become Posted.
 */
return new class extends Migration
{
       public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `transactions` MODIFY `status` ENUM('Draft','Submitted','Approved','Posted','Cancelled') NOT NULL DEFAULT 'Draft'");
    }


    public function down(): void
    {
        DB::statement("ALTER TABLE `transactions` MODIFY `status` ENUM('Draft','Posted','Cancelled') NOT NULL DEFAULT 'Posted'");
    }
};
