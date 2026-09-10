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
        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('theme', 'comment');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->integer('topic_id')->nullable()->index()->after('subject');
            $table->integer('subtopic_id')->nullable()->index()->after('topic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['topic_id', 'subtopic_id']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('comment', 'theme');
        });
    }
};
