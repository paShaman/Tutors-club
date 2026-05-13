<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id')->index();
            $table->string('subject');
            $table->string('theme');
            $table->string('price');
            $table->integer('duration');
            $table->boolean('is_payed')->default(0);
            $table->boolean('is_deleted')->default(0);
            $table->boolean('is_future')->default(0);
            $table->timestamp('date')->nullable();
            $table->time('time')->nullable();
            $table->timestamp('date_payed')->nullable();
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
        Schema::dropIfExists('lessons');
    }
}
