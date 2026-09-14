<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->integer('subject_id')->nullable()->index()->after('student_id');
        });

        // Переносим код предмета из lessons.subject в subject_id по таблице subjects.
        $subjectIds = DB::table('subjects')->pluck('id', 'code');

        foreach ($subjectIds as $code => $id) {
            DB::table('lessons')->where('subject', $code)->update(['subject_id' => $id]);
        }

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('subject')->nullable()->index()->after('student_id');
        });

        $subjectCodes = DB::table('subjects')->pluck('code', 'id');

        foreach ($subjectCodes as $id => $code) {
            DB::table('lessons')->where('subject_id', $id)->update(['subject' => $code]);
        }

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('subject_id');
        });
    }
};
