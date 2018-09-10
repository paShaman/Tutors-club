<?php namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class AuthController extends Controller
{
    /**
     * регистрация пользователя
     *
     * @return mixed
     */
    public function register()
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ];

        $input = $_POST;

        /*$validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return $this->respondWithFailedValidation($validator);
        }*/

        $user = new User();
        $user->email        = $input['email'];
        $user->password     = password_hash($input['password'], PASSWORD_DEFAULT);
        $user->name         = $input['name'];
        $user->lastname     = $input['lastname'];
        $user->save();

        return $this->_resultSuccess('Register successful');
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