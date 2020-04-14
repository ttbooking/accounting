<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            //$table->increments('id');
            //$table->string('owner_type', 36)->index();
            //$table->unsignedInteger('owner_id')->index();
            $table->uuid('uuid')->primary();
            $table->morphs('owner');
            $table->string('type', 36)->index();
            $table->char('currency', 3)->index();
            $table->decimal('balance', 15, 5)->default(0);
            $table->decimal('limit', 15, 5)->nullable();
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
        Schema::dropIfExists('accounting_accounts');
    }
}
