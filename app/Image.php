<?php

namespace App;

use Intervention\Image\ImageManagerStatic as ImageParent;

class Image extends ImageParent
{
    /**
     * создаем урл для картинки
     *
     * @param $imgUrl
     * @return string
     */
    public static function createImgUrl($imgUrl, $imgParams = [])
    {
        $imgUrl = preg_replace("/(.*)\?(.*)/", '$1', $imgUrl);

        $avatar = Image::make($imgUrl);

        $avatar->setFileInfoFromPath($imgUrl);

        if (!empty($imgParams['fit'])) {
            $avatar->fit($imgParams['fit']);
        }

        list($filePath, $dir) = Common::generateFilePath(
            !empty($avatar->filename) ? $avatar->filename : ($imgParams['filename'] ?? uniqid()),
            !empty($avatar->extension) ? $avatar->extension : ($imgParams['extension'] ?? 'jpg'),
            true)
        ;

        $avatar = $avatar->save($filePath);

        return $dir . $avatar->filename . '.' . $avatar->extension;
    }
}
