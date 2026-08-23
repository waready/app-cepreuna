<?php

namespace App\Support;

class MediaUrl
{
    public static function profile($path): string
    {
        return static::resolve($path, config('app.external_image_url'), 'storage/fotos');
    }

    public static function publication($path): string
    {
        return static::resolve($path, config('app.url'), 'storage/publicaciones');
    }

    public static function publicAsset($path): string
    {
        return static::resolve($path, config('app.url'), 'storage');
    }

    protected static function resolve($path, $baseUrl, string $directory): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $directory = trim($directory, '/');
        $shortDirectory = preg_replace('#^storage/#', '', $directory);

        if (strpos($path, 'storage/') === 0) {
            $publicPath = $path;
        } elseif ($shortDirectory !== '' && strpos($path, $shortDirectory . '/') === 0) {
            $publicPath = 'storage/' . $path;
        } else {
            $publicPath = $directory . '/' . $path;
        }

        $baseUrl = rtrim((string) ($baseUrl ?: config('app.url')), '/');

        return $baseUrl . '/' . $publicPath;
    }
}
