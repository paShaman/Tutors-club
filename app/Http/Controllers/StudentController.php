<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Form;
use App\Model\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final class StudentController extends Controller
{
    /**
     * Students list page.
     */
    public function getStudents(): Response
    {
        $students = Auth::user()->students()
            ->orderBy('is_deleted')
            ->orderBy('type')
            ->get();

        $deletedFlag = false;
        $specialFlag = false;

        foreach ($students as $student) {
            if (!empty($student->is_deleted)) {
                $deletedFlag = true;
            }
            if (!empty($student->type)) {
                $specialFlag = true;
            }
        }

        return Inertia::render('Students', [
            'students'        => $students->toArray(),
            'deletedFlag'     => $deletedFlag,
            'specialFlag'     => $specialFlag,
            'modalAddStudent' => Form::buildModal('student.add', lng('add_student')),
            'modalEditStudent' => Form::buildModal('student.edit', lng('add_student')),
        ]);
    }

    /**
     * Add or edit a student.
     */
    public function editStudent(): \Illuminate\Http\JsonResponse
    {
        $rules = [
            'student_name'  => 'required',
            'student_class'  => 'required',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return ControllerHelper::resultError($validator);
        }

        $params = [
            'name'          => $post['student_name'],
            'class'         => $post['student_class'] ?? null,
            'type'          => $post['student_type'] ?? null,
            'description'   => $post['student_description'] ?? '',
        ];

        $str = 'add_student';

        if (!empty($post['student_id'])) {
            $str = 'edit_student';

            $student = Student::findOrFail($post['student_id']);

            $student->name = $params['name'];
            $student->class = $params['class'];
            $student->type = $params['type'];
            $student->description = $params['description'];

            $result = $student->save();
        } else {
            $result = Auth::user()->addStudent($params);
        }

        if (empty($result)) {
            return ControllerHelper::resultError(lng('error.' . $str));
        }

        return ControllerHelper::resultSuccess(lng('success.' . $str));
    }

    /**
     * Delete or restore a student.
     */
    public function deleteStudent(): \Illuminate\Http\JsonResponse
    {
        $post = request()->post();

        $result = Auth::user()->editStudent($post);

        $str = 'edit_student';

        if (isset($post['is_deleted'])) {
            $str = $post['is_deleted'] ? 'delete_student' : 'return_student';
        }

        if (empty($result)) {
            return ControllerHelper::resultError(lng('error.' . $str));
        }

        return ControllerHelper::resultSuccess(lng('success.' . $str));
    }
}