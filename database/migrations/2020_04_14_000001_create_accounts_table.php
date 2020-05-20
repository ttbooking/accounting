<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    const ACCOUNT_TABLE = 'accounting_accounts';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(self::ACCOUNT_TABLE, function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->morphs('owner');
            $table->string('type', 36)->index();
            $table->char('currency', 3)->index();
            $table->decimal('balance', 14, 4)->default(0);
            $table->json('context')->nullable();
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'type', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(self::ACCOUNT_TABLE);
    }
}
