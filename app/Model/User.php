<?php

namespace App\Model;

use App\Access;
use App\Common;
use App\Notification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'first_name', 'last_name', 'middle_name', 'date_agree', 'avatar', 'account'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * Полное ФИО (фамилия + имя + отчество).
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(
                implode(' ', array_filter([$this->last_name, $this->first_name, $this->middle_name]))
            ),
        );
    }

    /**
     * get users roles
     */
    public function roles()
    {
        return $this->belongsToMany('App\Model\Role', 'roles_to_users');
    }

    /**
     * gte history of users payments
     */
    public function payments()
    {
        return $this->hasMany('App\Model\Payment');
    }

    /**
     * get users students
     */
    public function students()
    {
        return $this->belongsToMany('App\Model\Student', 'students_to_users');
    }

    /*
     * FUNCTIONS ---------------------------------
     */

    /**
     * get protected param
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * генерил урл для авторизации по хешу
     *
     * @return string
     */
    public function generateUrlForForceLogin()
    {
        return URL::signedRoute('auth', ['user' => $this->id]);
    }

    /**
     * проверка на админа
     */
    public function isAdmin()
    {
        return $this->roles()->where('id', Access::ROLE_ADMIN)->first();
    }

    /**
     * получаем списки пользователей
     *
     * @param $params
     * @param array $pagination
     * @return array
     */
    public static function getList($params = [], $pagination = [])
    {
        $sql = DB::table('users as u')
            ->leftJoin('roles_to_users as rtu', 'rtu.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'rtu.role_id', '=', 'r.id')
            ->groupBy('u.id')
            ->select([
                'u.avatar',
                'u.date_agree',
                'u.email',
                'u.account',
                'u.first_name',
                'u.id',
                'u.last_name',
                'u.middle_name',
                DB::raw('group_concat(r.title separator ", ") as roles')
            ])
        ;

        if (!empty($params['id'])) {
            $sql->where('id', $params['id']);
        }

        if (!empty($params['email'])) {
            $sql->where('email', 'like', '%' . $params['email'] . '%');
        }

        if (!empty($params['first_name'])) {
            $sql->where('first_name', 'like', '%' . $params['first_name'] . '%');
        }

        if (!empty($params['last_name'])) {
            $sql->where('last_name', 'like', '%' . $params['last_name'] . '%');
        }

        if (!empty($params['middle_name'])) {
            $sql->where('middle_name', 'like', '%' . $params['middle_name'] . '%');
        }

        if (!empty($params['order'])) {
            $sql->orderBy($params['order'], $params['order_way'] ?? 'asc');
        }

        if (!empty($pagination)) {
            return Common::pagination($sql, $pagination);
        }

        return $sql->get()->toArray();
    }

    /**
     * внесение оплаты
     *
     * @param $amount
     * @param $reason
     * @return bool
     */
    public function addPayment($amount, $reason)
    {
        try {
            if (empty($amount) || empty($reason)) {
                throw new \Exception('empty_params');
            }

            $payment = new Payment([
                'amount'            => $amount,
                'user_id'           => $this->id,
                'charged_user_id'   => Auth::id(),
                'reason'            => $reason
            ]);

            $this->payments()->save($payment);

            $this->recalcAccount();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * пересчет счета пользователя
     *
     * @todo так как тонны транзакций не планируется, то для верности будем делать при каждом изменении счета
     *
     * @return bool
     */
    public function recalcAccount()
    {
        try {
            $payments = $this->payments()->get();

            $account = 0;

            foreach ($payments as $payment) {
                $account += $payment->amount;
            }

            $this->account = $account;
            $this->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * добавление ученика
     *
     * @todo если более одного ученика, то необходима активная подписка
     *
     * @param $params
     * @return bool
     */
    public function addStudent($params)
    {
        try {
            if (empty($params['name'])) {
                throw new \Exception('empty_params');
            }

            $student = new Student([
                'name'            => $params['name'],
                'class'           => $params['class'] ?? '',
                'description'     => $params['description'] ?? '',
            ]);

            $this->students()->save($student);

            return true;
        } catch (\Exception $e) {
            Notification::put($e->getMessage());
            return false;
        }
    }

    /**
     * редактирование ученика
     *
     * @param $params
     * @return bool
     */
    public function editStudent($params)
    {
        try {
            if (empty($params['student_id'])) {
                throw new \Exception('empty_params');
            }

            $student = $this->students()->where('id', $params['student_id'])->first();

            if (!$student) {
                throw new \Exception('student_not_connected');
            }

            if (isset($params['is_deleted'])) {
                $student->is_deleted = $params['is_deleted'];
            }
            $student->save();

            return true;
        } catch (\Exception $e) {
            Notification::put($e->getMessage());
            return false;
        }
    }
}
