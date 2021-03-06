<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersSocialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_social', function (Blueprint $table) {
            $table->enum('social', ['facebook', 'google', 'vkontakte']);
            $table->integer('user_id');
            $table->string('social_id');
            $table->primary(['user_id', 'social']);
            $table->index('user_id');
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
        Schema::dropIfExists('users_social');
    }
}
