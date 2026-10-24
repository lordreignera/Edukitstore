<?php

namespace Tests\Unit;

use App\Support\DocumentStorage;
use PHPUnit\Framework\TestCase;

class DocumentStorageTest extends TestCase
{
    public function test_download_names_cannot_escape_the_filename_or_inject_headers(): void
    {
        $this->assertSame('invoice.pdf', DocumentStorage::downloadName('..\\invoice.pdf', 'document'));
        $this->assertSame('invoice--X-Injected: yes.pdf', DocumentStorage::downloadName("invoice\r\nX-Injected: yes.pdf", 'document'));
        $this->assertSame('document', DocumentStorage::downloadName('../', 'document'));
    }
}
