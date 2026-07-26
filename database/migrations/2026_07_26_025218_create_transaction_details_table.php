public function up()
{
    Schema::create('transaction_details', function (Blueprint $table) {

        $table->id();


        $table->foreignId('transaction_id')
            ->constrained()
            ->cascadeOnDelete();


        $table->foreignId('account_id')
            ->constrained()
            ->cascadeOnDelete();


        $table->decimal('debit',12,2)
            ->default(0);


        $table->decimal('credit',12,2)
            ->default(0);


        $table->timestamps();

    });
}