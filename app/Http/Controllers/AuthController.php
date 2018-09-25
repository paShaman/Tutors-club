<?php

namespace App\Http\Controllers;

use App\Common;
use App\Mail\PasswordRecovery;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

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

        $post = $request->post();

        if (empty(Common::checkRecaptcha($post))) {
            return $this->_resultError(lng('error.recaptcha'));
        }

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return $this->_resultError($validator);
        }

        //проверка email на существование
        $user = User::where('email', $post['email'])->first();

        if (!empty($user)) {
            return $this->_resultError(lng('duplicate_email'));
        }

        $user = new User();
        $user->email        = $post['email'];
        $user->password     = Hash::make($post['password']);
        $user->first_name   = $post['first_name'] ?? '';
        $user->last_name    = $post['last_name'] ?? '';
        $user->middle_name  = $post['middle_name'] ?? '';
        $user->date_agree   = DB::raw('now()');

        try {
            //create new user
            $user->save();
        } catch (\Exception $e) {
            return $this->_resultError(lng('error.register'));
        }

        //force auth
        Auth::loginUsingId($user->id, true);

        return $this->_resultSuccess(lng('success.register'));
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

        $post = $request->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return $this->_resultError($validator);
        }

        if (Auth::attempt(['email' => $post['email'], 'password' => $post['password']], true)) {
            return $this->_resultSuccess(lng('success.login'));
        }

        return $this->_resultError(lng('error.login'));
    }

    /**
     * выход
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout()
    {
        Auth::logout();

        return redirect(route('login'));
    }

    /**
     * Восстановление пароля
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recovery(Request $request)
    {
        $rules = [
            'email'             => 'required|email',
        ];

        $post = $request->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return $this->_resultError($validator);
        }

        $user = User::where('email', $post['email'])->first();

        if (empty($user)) {
            return $this->_resultError(lng('error.no_user_with_this_email'));
        }

        $newPassword = str_random(8);

        $message = new PasswordRecovery([
            'password' => $newPassword
        ]);

        Mail::to($user->email)->send($message);

        if (empty(Mail::failures())) {
            $user->password = Hash::make($newPassword);
            $user->save();

            return $this->_resultSuccess(lng('success.recovery'));
        }

        return $this->_resultError(lng('error.recovery'));
    }
}