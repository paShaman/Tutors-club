<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_topics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id')->index();
            $table->integer('topic_id')->index();
            $table->string('status')->default('not_started');
            $table->date('mastered_at')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_topics');
    }
};
