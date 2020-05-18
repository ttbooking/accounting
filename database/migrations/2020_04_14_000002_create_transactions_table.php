<?php

use Daniser\Accounting\Facades\Account;
use Daniser\Accounting\Facades\Transaction;
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
        Schema::create(Transaction::getTable(), function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('previous_uuid')->nullable()->unique();
            $table->uuid('parent_uuid')->nullable()->index();
            $table->uuid('origin_uuid')->index();
            $table->uuid('destination_uuid')->index();
            $table->char('currency', 3)->index();
            $table->unsignedDecimal('amount', 13, 4);
            $table->unsignedDecimal('base_amount', 13, 4)->nullable();
            $table->unsignedDecimal('origin_amount', 13, 4)->nullable();
            $table->unsignedDecimal('destination_amount', 13, 4)->nullable();
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->char('digest', 64)->nullable();
        });

        Schema::table(Transaction::getTable(), function (Blueprint $table) {
            $table->foreign('previous_uuid')->references('uuid')->on(Transaction::getTable());
            $table->foreign('parent_uuid')->references('uuid')->on(Transaction::getTable());
            $table->foreign('origin_uuid')->references('uuid')->on(Account::getTable());
            $table->foreign('destination_uuid')->references('uuid')->on(Account::getTable());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Transaction::getTable());
    }
}
