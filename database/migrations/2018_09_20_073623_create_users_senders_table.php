<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersSendersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_senders', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('sender', ['webpush_onesignal', 'telegram']);
            $table->string('sender_id');
            $table->integer('user_id');
            $table->index(['user_id', 'sender']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_senders');
    }
}
