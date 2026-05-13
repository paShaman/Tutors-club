<?php

declare(strict_types=1);

namespace App;

class Image
{
    /**
     * Create an image URL from a remote source, optionally downloading and resizing.
     *
     * @param  string $url     Source image URL
     * @param  array  $options Options: filename, extension, fit (width in px)
     * @return string          Resulting image URL/path
     */
    public static function createImgUrl(string $url, array $options = []): string
    {
        $filename = $options['filename'] ?? md5($url);
        $extension = $options['extension'] ?? 'jpg';
        $fit = (int) ($options['fit'] ?? 0);

        $destDir = public_path('upload/avatars');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . '/' . $filename . '.' . $extension;
        $destUrl = '/upload/avatars/' . $filename . '.' . $extension;

        if (file_exists($destPath)) {
            return $destUrl;
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || empty($imageData)) {
                return $url;
            }

            file_put_contents($destPath, $imageData);

            if ($fit > 0 && extension_loaded('gd')) {
                $info = getimagesize($destPath);
                if ($info) {
                    [$width, $height] = $info;
                    $ratio = $fit / max($width, $height);
                    $newWidth = (int) round($width * $ratio);
                    $newHeight = (int) round($height * $ratio);

                    $src = match ($info[2]) {
                        IMAGETYPE_JPEG => imagecreatefromjpeg($destPath),
                        IMAGETYPE_PNG  => imagecreatefrompng($destPath),
                        IMAGETYPE_GIF  => imagecreatefromgif($destPath),
                        default        => null,
                    };

                    if ($src) {
                        $dst = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        imagejpeg($dst, $destPath, 90);
                        imagedestroy($src);
                        imagedestroy($dst);
                    }
                }
            }

            return $destUrl;
        } catch (\Throwable) {
            return $url;
        }
    }
}