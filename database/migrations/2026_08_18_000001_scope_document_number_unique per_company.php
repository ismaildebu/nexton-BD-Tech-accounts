<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ✅ Fix: document_number আগে পুরো legal_documents টেবিলজুড়ে globally unique
 * ছিল, ফলে দুই আলাদা company একই ডকুমেন্ট নম্বর ব্যবহার করতে পারত না
 * (বাস্তবে দুই আলাদা কোম্পানির Trade License/TIN নম্বর একই হতেই পারে না,
 * কিন্তু ভিন্ন ফরম্যাটের অভ্যন্তরীণ রেফারেন্স নম্বর ব্যবহার করলে collision হতো)।
 * এখন company_id + document_number একসাথে unique।
 *
 * NOTE: dropUnique(['document_number']) Laravel-এর কনভেনশন অনুযায়ী
 * 'legal_documents_document_number_unique' নাম আশা করে, কিন্তু আসল
 * টেবিলে constraint-টা ভিন্ন নামে থাকতে পারে (বা না-ও থাকতে পারে) —
 * তাই এখানে নাম না ধরে, document_number কলামের উপর থাকা যেকোনো
 * single-column unique index নিজে খুঁজে বের করে ড্রপ করা হচ্ছে, এবং
 * শুধু composite unique-টা আগে থেকে না থাকলেই যোগ করা হচ্ছে —
 * যাতে migration বারবার নিরাপদে চালানো যায় (idempotent)।
 */
return new class extends Migration
{
    public function up(): void
    {
        $existingUniqueIndex = $this->findSingleColumnUniqueIndex('legal_documents', 'document_number');

        if ($existingUniqueIndex !== null) {
            Schema::table('legal_documents', function (Blueprint $table) use ($existingUniqueIndex) {
                $table->dropUnique($existingUniqueIndex);
            });
        }

        if (! $this->hasCompositeUniqueIndex('legal_documents', ['company_id', 'document_number'])) {
            Schema::table('legal_documents', function (Blueprint $table) {
                $table->unique(['company_id', 'document_number']);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasCompositeUniqueIndex('legal_documents', ['company_id', 'document_number'])) {
            Schema::table('legal_documents', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'document_number']);
            });
        }

        if ($this->findSingleColumnUniqueIndex('legal_documents', 'document_number') === null) {
            Schema::table('legal_documents', function (Blueprint $table) {
                $table->unique('document_number');
            });
        }
    }

    /**
     * Finds the name of a UNIQUE index that covers exactly one column
     * (document_number), regardless of what it's actually named.
     * Returns null if no such index exists.
     */
    private function findSingleColumnUniqueIndex(string $table, string $column): ?string
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['unique']
                && count($index['columns']) === 1
                && $index['columns'][0] === $column
            ) {
                return $index['name'];
            }
        }

        return null;
    }

    /**
     * Whether a UNIQUE index already exists covering exactly this
     * ordered set of columns.
     */
    private function hasCompositeUniqueIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['unique'] && $index['columns'] === $columns) {
                return true;
            }
        }

        return false;
    }
};