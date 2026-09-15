<?php

namespace App\Model;

use App\Support\CacheKeys;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable
{
    use Notifiable;

    /** Кэш ролей в рамках запроса: isAdmin() не должен ходить в БД дважды. */
    private ?array $roleTitlesCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'first_name', 'last_name', 'middle_name', 'date_agree', 'avatar', 'account', 'locale'
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
     * get users students
     */
    public function students()
    {
        return $this->belongsToMany('App\Model\Student', 'students_to_users');
    }

    /**
     * темы преподавателя (справочник планирования)
     */
    public function topics()
    {
        return $this->hasMany('App\Model\Topic', 'user_id');
    }

    /**
     * подписки (тарифы) пользователя
     */
    public function subscriptions()
    {
        return $this->hasMany('App\Model\UserSubscription', 'user_id');
    }

    /**
     * роли пользователя
     */
    public function roles()
    {
        return $this->belongsToMany('App\Model\Role', 'roles_to_users')->withTimestamps();
    }

    /*
     * FUNCTIONS ---------------------------------
     */

    /**
     * названия ролей пользователя (кэш 30 минут, сбрасывается при правке ролей в админке)
     *
     * @return array<int, string>
     */
    public function roleTitles()
    {
        if ($this->roleTitlesCache !== null) {
            return $this->roleTitlesCache;
        }

        return $this->roleTitlesCache = Cache::remember(
            CacheKeys::userRoles((int) $this->id),
            now()->addMinutes(30),
            fn (): array => $this->roles()->pluck('title')->all(),
        );
    }

    /** сброс кэша ролей после изменения в админке */
    public static function flushRoleCache(int $userId): void
    {
        Cache::forget(CacheKeys::userRoles($userId));
    }

    /**
     * есть ли у пользователя роль с указанным кодом
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array($role, $this->roleTitles(), true);
    }

    /**
     * является ли пользователь администратором
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole(\App\Model\Role::ADMIN);
    }

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
     * добавление ученика
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

            // Лимит учеников по тарифу (основная проверка — в StudentController).
            if (!app(\App\Services\TariffService::class)->canUse($this, 'students')) {
                throw new \Exception('tariff_students_limit');
            }

            $student = new Student([
                'name'            => $params['name'],
                'gender'          => $params['gender'] ?? 'boy',
                'color'           => $params['color'] ?? Student::randomColor(),
                'class'           => $params['class'] ?? '',
                'description'     => $params['description'] ?? '',
            ]);

            $this->students()->save($student);

            return true;
        } catch (\Exception $e) {
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
            return false;
        }
    }
}
