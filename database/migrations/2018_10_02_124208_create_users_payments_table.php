<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('amount');
            $table->string('external_payment_id')->nullable();
            $table->integer('user_id');
            $table->integer('charged_user_id');
            $table->text('reason');
            $table->timestamps();
            $table->index('user_id');
            $table->unique('external_payment_id');
            $table->index('charged_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_payments');
    }
}
