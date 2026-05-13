<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class ControllerHelper
{
    public static function resultSuccess(mixed $message = ''): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $message,
        ]);
    }

    public static function resultError(mixed $message = ''): JsonResponse
    {
        if ($message instanceof \Illuminate\Validation\Validator) {
            $errors = array_combine(
                $message->errors()->keys(),
                $message->errors()->all(),
            );
        } else {
            $errors = $message;
        }

        return response()->json([
            'success' => false,
            'data'    => $errors,
        ]);
    }
}