<?php

namespace App\Support;

class DocumentStorage
{
    public static function disk(): string
    {
        return config('filesystems.documents_disk', config('filesystems.default', 'local'));
    }

    public static function downloadName(?string $name, string $fallback): string
    {
        $name = basename(str_replace('\\', '/', trim((string) $name)));
        $name = preg_replace('/[\x00-\x1F\x7F]/', '-', $name) ?? '';
        $name = trim(substr($name, 0, 180), " .\t\n\r\0\x0B");

        return $name !== '' && $name !== '.' ? $name : $fallback;
    }
}
