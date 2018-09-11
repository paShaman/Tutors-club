<?php namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;

class AuthController extends Controller
{
    /**
     * регистрация пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $rules = [
            'email'             => 'required|email',
            'first_name'        => 'required',
            'last_name'         => 'required',
            'password'          => 'required',
            'password_confirm'  => 'required|same:password',
            'policy_agree'      => ['required', Rule::in(['on'])], //checkbox
        ];

        $input = $request->post();

        $validator = Validator::make($request->post(), $rules);

        if ($validator->fails()) {
            return $this->_resultError($validator);
        }

        //проверка email на существование
        $user = User::where('email', $input['email'])->first();

        if (!empty($user)) {
            return $this->_resultError(lng('duplicate_email'));
        }

        $user = new User();
        $user->email        = $input['email'];
        $user->password     = password_hash($input['password'], PASSWORD_DEFAULT);
        $user->first_name   = $input['first_name'];
        $user->last_name    = $input['last_name'];
        $user->middle_name  = $input['middle_name'];
        $user->date_agree   = DB::raw('now()');

        try {
            $user->save();
        } catch (\Exception $e) {
            return $this->_resultError(lng('error.registration'));
        }

        return $this->_resultSuccess(lng('success.registration'));
    }

    /**
     * авторизация пользователя
     *
     * @return mixed
     */
    public function login()
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $input = $_POST;

        /*$validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return $this->respondWithFailedValidation($validator);
        }*/

        if (Auth::attempt(['email' => $input['email'], 'password' => $input['password']], true)) {
            return $this->_resultSuccess('Login successful');
        }

        return $this->_resultError(['Invalid Email/Password pair']);
    }
}