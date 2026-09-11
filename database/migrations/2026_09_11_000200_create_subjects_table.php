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
        Schema::create('subjects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->json('name');
            $table->integer('position')->default(0);
            $table->boolean('is_deleted')->default(0);
            $table->timestamps();
        });

        // Переносим предметы, которые раньше были константой Lesson::LESSON_SUBJECTS.
        // code — значение, которое хранится в lessons.subject и topics.subject,
        // name — названия по локалям в формате {"ru":"...","en":"..."}.
        $now = now();

        DB::table('subjects')->insert([
            [
                'code'       => 'lesson_subject_maths',
                'name'       => json_encode(['ru' => 'Математика', 'en' => 'Maths'], JSON_UNESCAPED_UNICODE),
                'position'   => 1,
                'is_deleted' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'       => 'lesson_subject_informatics',
                'name'       => json_encode(['ru' => 'Информатика', 'en' => 'Computer science'], JSON_UNESCAPED_UNICODE),
                'position'   => 2,
                'is_deleted' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'       => 'lesson_subject_english',
                'name'       => json_encode(['ru' => 'Английский', 'en' => 'English'], JSON_UNESCAPED_UNICODE),
                'position'   => 3,
                'is_deleted' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
