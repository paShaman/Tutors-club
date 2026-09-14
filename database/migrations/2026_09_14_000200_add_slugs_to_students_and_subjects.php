<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('code');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Slug предмета — код без служебного префикса (lesson_subject_maths -> maths).
        foreach (DB::table('subjects')->orderBy('id')->get() as $row) {
            $base = Str::slug(preg_replace('/^lesson_subject_/', '', (string) $row->code)) ?: 'subject';

            $slug = $base;
            $i = 2;

            while (DB::table('subjects')->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }

            DB::table('subjects')->where('id', $row->id)->update(['slug' => $slug]);
        }

        // Slug ученика — из имени, с числовым суффиксом при совпадении.
        foreach (DB::table('students')->orderBy('id')->get() as $row) {
            $base = Str::slug((string) $row->name) ?: 'student';

            $slug = $base;
            $i = 2;

            while (DB::table('students')->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }

            DB::table('students')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
