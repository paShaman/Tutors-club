<?php

declare(strict_types=1);

namespace App;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class Image
{
    /**
     * Target side (px) of a processed square avatar.
     * 512px keeps avatars sharp on 2x/3x (retina) displays while staying light.
     */
    public const AVATAR_SIZE = 512;

    /**
     * JPEG quality used when re-encoding avatars.
     */
    public const AVATAR_QUALITY = 82;

    /**
     * Directory (relative to public path) where avatars are stored.
     */
    public const AVATAR_DIR = 'upload/avatars';

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

        $destDir = public_path(self::AVATAR_DIR);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . '/' . $filename . '.' . $extension;
        $destUrl = '/' . self::AVATAR_DIR . '/' . $filename . '.' . $extension;

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

    /**
     * Store an uploaded avatar: centre-crop to a square, downscale to a retina-friendly
     * size and re-encode as JPEG. Returns the public URL of the stored file.
     *
     * @throws RuntimeException When the file cannot be processed
     */
    public static function storeAvatar(UploadedFile $file): string
    {
        $destDir = public_path(self::AVATAR_DIR);

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $raw = $file->get();

        if (extension_loaded('gd') && ($info = @getimagesizefromstring($raw)) !== false) {
            $src = @imagecreatefromstring($raw);

            if ($src === false) {
                throw new RuntimeException('bad_image');
            }

            [$width, $height] = $info;

            $side = min($width, $height);
            $size = min($side, self::AVATAR_SIZE);

            $dst = imagecreatetruecolor($size, $size);

            // White background for images with transparency (JPEG has no alpha channel)
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);

            $srcX = (int) (($width - $side) / 2);
            $srcY = (int) (($height - $side) / 2);

            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $side, $side);

            $filename = self::makeFilename('jpg');
            $destPath = $destDir . '/' . $filename;

            if (!imagejpeg($dst, $destPath, self::AVATAR_QUALITY)) {
                imagedestroy($src);
                imagedestroy($dst);
                throw new RuntimeException('save_image');
            }

            imagedestroy($src);
            imagedestroy($dst);

            return '/' . self::AVATAR_DIR . '/' . $filename;
        }

        // GD unavailable: keep the original file untouched
        $filename = self::makeFilename($file->guessExtension() ?: 'jpg');
        $file->move($destDir, $filename);

        return '/' . self::AVATAR_DIR . '/' . $filename;
    }

    /**
     * Delete a previously stored avatar file (only local files inside the avatar dir).
     */
    public static function deleteStoredAvatar(?string $url): void
    {
        if (empty($url) || !str_starts_with($url, '/' . self::AVATAR_DIR . '/')) {
            return;
        }

        $path = public_path(ltrim($url, '/'));

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function makeFilename(string $extension): string
    {
        return 'av_' . date('Ymd_His') . '_' . Str::random(16) . '.' . $extension;
    }
}