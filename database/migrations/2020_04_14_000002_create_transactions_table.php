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
            $table->uuid('previous_uuid')->nullable()->unique();
            $table->uuid('parent_uuid')->nullable()->index();
            $table->uuid('origin_uuid')->index();
            $table->uuid('destination_uuid')->index();
            $table->char('currency', 3)->index();
            $table->unsignedDecimal('ot_rate', 15, 5)->default(1);
            $table->unsignedDecimal('td_rate', 15, 5)->default(1);
            $table->unsignedDecimal('amount', 15, 5);
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('digest', 64)->nullable();
            //$table->binary('digest')->nullable();
        });

        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->foreign('previous_uuid')->references('uuid')->on('accounting_transactions');
            $table->foreign('parent_uuid')->references('uuid')->on('accounting_transactions');
            $table->foreign('origin_uuid')->references('uuid')->on('accounting_accounts');
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
