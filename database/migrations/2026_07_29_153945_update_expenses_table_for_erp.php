<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {

            if (!Schema::hasColumn('expenses', 'company_id')) {
                $table->foreignId('company_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('expenses', 'expense_date')) {
                $table->date('expense_date')
                    ->after('company_id');
            }

            if (!Schema::hasColumn('expenses', 'category')) {
                $table->string('category')
                    ->after('expense_date');
            }

            if (!Schema::hasColumn('expenses', 'description')) {
                $table->text('description')
                    ->nullable()
                    ->after('category');
            }

            if (!Schema::hasColumn('expenses', 'amount')) {
                $table->decimal('amount',15,2)
                    ->default(0)
                    ->after('description');
            }

            if (!Schema::hasColumn('expenses', 'status')) {
                $table->string('status')
                    ->default('approved')
                    ->after('amount');
            }

        });
    }


    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {

            $columns = [
                'status',
                'amount',
                'description',
                'category',
                'expense_date',
                'company_id',
            ];

            foreach($columns as $column){

                if(Schema::hasColumn('expenses',$column)){
                    $table->dropColumn($column);
                }

            }

        });
    }
};