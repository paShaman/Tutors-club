<?php namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * регистрация пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $rules = [
            'email'                 => 'required|email',
            'first_name'            => 'required',
            'last_name'             => 'required',
            'password'              => 'required',
            'password_confirmation' => 'required|same:password',
            'policy_agree'          => ['required', Rule::in(['on'])], //checkbox
        ];

        $input = $request->post();

        $validator = Validator::make($input, $rules);

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
        $user->password     = Hash::make($input['password']);
        $user->first_name   = $input['first_name'] ?? '';
        $user->last_name    = $input['last_name'] ?? '';
        $user->middle_name  = $input['middle_name'] ?? '';
        $user->date_agree   = DB::raw('now()');

        try {
            //create new user
            $user->save();
        } catch (\Exception $e) {
            return $this->_resultError(lng('error.registration'));
        }

        //force auth
        Auth::loginUsingId($user->id, true);

        return $this->_resultSuccess(lng('success.registration'));
    }

    /**
     * авторизация пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $rules = [
            'email'             => 'required|email',
            'password'          => 'required',
        ];

        $input = $request->post();

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return $this->_resultError($validator);
        }

        if (Auth::attempt(['email' => $input['email'], 'password' => $input['password']], true)) {
            return $this->_resultSuccess(lng('success.auth'));
        }

        return $this->_resultError(lng('error.auth'));
    }

    /**
     * выход
     */
    public function logout()
    {
        Auth::logout();

        return redirect(route('login'));
    }
}