<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class StudentController extends Controller
{
    /**
     * добавлеие ученика
     */
    public function addStudent()
    {
        $rules = [
            'student_name'  => 'required',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return $this->_resultError($validator);
        }

        $result = Auth::user()->addStudent([
            'name'          => $post['student_name'],
            'description'   => $post['student_description'] ?? '',
        ]);

        if (empty($result)) {
            return $this->_resultError(lng('error.add_student'));
        }

        return $this->_resultSuccess(lng('success.add_student'));
    }
}