<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('source_uuid')->index();
            $table->uuid('destination_uuid')->index();
            $table->char('currency', 3)->index();
            $table->unsignedDecimal('st_rate', 15, 5)->default(1);
            $table->unsignedDecimal('td_rate', 15, 5)->default(1);
            $table->unsignedDecimal('amount', 15, 5);
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('hash')->nullable();

            $table->foreign('source_uuid')->references('uuid')->on('accounting_accounts');
            $table->foreign('destination_uuid')->references('uuid')->on('accounting_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_transactions');
    }
}
