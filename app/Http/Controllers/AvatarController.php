<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

final class AvatarController extends Controller
{
    /**
     * Accept an avatar image upload, process it (square crop + retina-friendly
     * compression) and return the stored URL. The entity is persisted later,
     * when the corresponding profile/student form is saved.
     */
    public function upload(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
                'dimensions:max_width=6000,max_height=6000',
            ],
        ], [
            'required'   => 'Выберите файл изображения',
            'image'      => 'Файл должен быть изображением',
            'mimes'      => 'Допустимы только изображения JPG, PNG или WebP',
            'max'        => 'Изображение не должно превышать 5 МБ',
            'dimensions' => 'Изображение слишком большое (максимум 6000×6000 px)',
        ]);

        if ($validator->fails()) {
            return ControllerHelper::resultError($validator);
        }

        try {
            $url = Image::storeAvatar(request()->file('avatar'));
        } catch (\Throwable $e) {
            return ControllerHelper::resultError(lng('error.bad_image'));
        }

        return ControllerHelper::resultSuccess([
            'avatar' => $url,
        ]);
    }
}
