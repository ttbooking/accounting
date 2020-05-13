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
        Schema::dropIfExists('accounting_accounts');
    }
}
