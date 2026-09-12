<?php

namespace App\Support;

class DocumentStorage
{
    public static function disk(): string
    {
        return config('filesystems.documents_disk', config('filesystems.default', 'local'));
    }
}
