<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    const TRANSACTION_TABLE = 'accounting_transactions';
    const ACCOUNT_TABLE = 'accounting_accounts';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(self::TRANSACTION_TABLE, function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('parent_uuid')->nullable()->index();
            $table->uuid('origin_uuid')->index();
            $table->uuid('destination_uuid')->index();
            $table->string('type', 36)->index();
            $table->char('currency', 3)->index();
            $table->unsignedDecimal('amount', 13, 4);
            $table->unsignedDecimal('base_amount', 13, 4)->nullable();
            $table->unsignedDecimal('origin_amount', 13, 4)->nullable();
            $table->unsignedDecimal('destination_amount', 13, 4)->nullable();
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->timestamp('started_at', 6)->nullable()->index();
            $table->timestamp('finished_at', 6)->nullable()->index();
            $table->char('digest', 64)->nullable();
        });

        Schema::table(self::TRANSACTION_TABLE, function (Blueprint $table) {
            $table->foreign('parent_uuid')->references('uuid')->on(self::TRANSACTION_TABLE);
            $table->foreign('origin_uuid')->references('uuid')->on(self::ACCOUNT_TABLE);
            $table->foreign('destination_uuid')->references('uuid')->on(self::ACCOUNT_TABLE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(self::TRANSACTION_TABLE);
    }
}
