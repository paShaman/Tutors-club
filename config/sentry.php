<?php

declare(strict_types=1);

use Sentry\Dsn;

/*
|--------------------------------------------------------------------------
| GlitchTip (Sentry-совместимый трекер ошибок)
|--------------------------------------------------------------------------
|
| DSN проекта берётся из GLITCHTIP_DSN: панель GlitchTip → проект → Settings → DSN,
| вид https://<public-key>@<host>/<project-id>.
| Пока переменная пустая или не похожа на DSN (например, в ней API-токен),
| здесь остаётся null и SDK полностью отключается — приложение работает как раньше.
|
*/

$rawDsn = env('GLITCHTIP_DSN');

$dsn = null;

if (is_string($rawDsn) && trim($rawDsn) !== '') {
    try {
        $dsn = (string) Dsn::createFromString(trim($rawDsn));
    } catch (InvalidArgumentException) {
        // Невалидный DSN валит SDK на разборе опций, поэтому такое значение просто игнорируем.
        $dsn = null;
    }
}

return [

    // Без DSN SDK не отправляет ничего и не регистрирует обработчики (см. ServiceProvider).
    'dsn' => $dsn,

    // Пусто = окружение Laravel (APP_ENV).
    'environment' => env('SENTRY_ENVIRONMENT'),

    // Версия сборки: заполняется вручную при релизе, в трекере отделяет старые ошибки от новых.
    'release' => env('SENTRY_RELEASE'),

    // Трейсинг и профилирование в GlitchTip не используем — канал для ошибок и логов.
    'traces_sample_rate'   => env('SENTRY_TRACES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_TRACES_SAMPLE_RATE'),
    'profiles_sample_rate' => null,

    // Структурные логи Sentry (канал sentry_logs) GlitchTip не поддерживает.
    'enable_logs' => false,

    // Тела запросов и заголовки содержат пароли и данные учеников — в трекер не уходят.
    'send_default_pii' => false,

    // Хлебные крошки: логи и SQL дают полезный контекст, кэш (Redis) слишком шумный,
    // привязки SQL-запросов не пишем — там персональные данные.
    'breadcrumbs' => [
        'logs'                  => true,
        'sql_queries'           => true,
        'sql_bindings'          => false,
        'cache'                 => false,
        'http_client_requests'  => true,
        'queue_info'            => true,
        'command_info'          => true,
        'notifications'         => false,
    ],

];
