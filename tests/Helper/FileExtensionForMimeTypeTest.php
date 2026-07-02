<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileExtensionForMimeTypeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für File::extensionForMimeType() (Umkehrhelfer zu File::mimeType()).
 */
class FileExtensionForMimeTypeTest extends BaseTestCase {
    public function test_maps_document_mime_types(): void {
        $this->assertSame('pdf', File::extensionForMimeType('application/pdf'));
        $this->assertSame('xlsx', File::extensionForMimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        $this->assertSame('docx', File::extensionForMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
        $this->assertSame('xls', File::extensionForMimeType('application/vnd.ms-excel'));
        $this->assertSame('doc', File::extensionForMimeType('application/msword'));
    }

    public function test_maps_image_mime_types(): void {
        $this->assertSame('jpg', File::extensionForMimeType('image/jpeg'));
        $this->assertSame('png', File::extensionForMimeType('image/png'));
        $this->assertSame('gif', File::extensionForMimeType('image/gif'));
        $this->assertSame('tif', File::extensionForMimeType('image/tiff'));
        $this->assertSame('webp', File::extensionForMimeType('image/webp'));
        $this->assertSame('svg', File::extensionForMimeType('image/svg+xml'));
        $this->assertSame('avif', File::extensionForMimeType('image/avif'));
        $this->assertSame('heif', File::extensionForMimeType('image/heif'));
        $this->assertSame('ico', File::extensionForMimeType('image/vnd.microsoft.icon'));
    }

    public function test_maps_text_and_data_mime_types(): void {
        $this->assertSame('xml', File::extensionForMimeType('application/xml'));
        $this->assertSame('xml', File::extensionForMimeType('text/xml'));
        $this->assertSame('csv', File::extensionForMimeType('text/csv'));
        $this->assertSame('txt', File::extensionForMimeType('text/plain'));
        $this->assertSame('html', File::extensionForMimeType('text/html'));
        $this->assertSame('json', File::extensionForMimeType('application/json'));
        $this->assertSame('yaml', File::extensionForMimeType('application/yaml'));
        $this->assertSame('md', File::extensionForMimeType('text/markdown'));
        $this->assertSame('ics', File::extensionForMimeType('text/calendar'));
        $this->assertSame('zip', File::extensionForMimeType('application/zip'));
    }

    public function test_maps_archive_mime_types_and_aliases(): void {
        $this->assertSame('zip', File::extensionForMimeType('application/x-zip'));
        $this->assertSame('gz', File::extensionForMimeType('application/gzip'));
        $this->assertSame('tar', File::extensionForMimeType('application/x-tar'));
        $this->assertSame('7z', File::extensionForMimeType('application/x-7z-compressed'));
        $this->assertSame('rar', File::extensionForMimeType('application/vnd.rar'));
    }

    public function test_maps_media_and_font_mime_types(): void {
        $this->assertSame('mp3', File::extensionForMimeType('audio/mpeg'));
        $this->assertSame('wav', File::extensionForMimeType('audio/x-wav'));
        $this->assertSame('mp4', File::extensionForMimeType('video/mp4'));
        $this->assertSame('mov', File::extensionForMimeType('video/quicktime'));
        $this->assertSame('woff2', File::extensionForMimeType('font/woff2'));
        $this->assertSame('ttf', File::extensionForMimeType('application/x-font-ttf'));
    }

    public function test_ignores_mime_parameters_and_case(): void {
        $this->assertSame('csv', File::extensionForMimeType('text/csv; charset=utf-8'));
        $this->assertSame('pdf', File::extensionForMimeType('Application/PDF'));
        $this->assertSame('txt', File::extensionForMimeType('  text/plain '));
    }

    public function test_unknown_mime_type_returns_null(): void {
        $this->assertNull(File::extensionForMimeType('application/octet-stream'));
        $this->assertNull(File::extensionForMimeType('application/x-unknown'));
        $this->assertNull(File::extensionForMimeType(''));
        $this->assertNull(File::extensionForMimeType('kein-mime'));
    }

    public function test_roundtrip_with_mime_type_detection(): void {
        // Umkehrhelfer-Eigenschaft: mimeType(datei.pdf) → application/pdf → 'pdf'
        $tmp = sys_get_temp_dir() . '/ext-for-mime-' . bin2hex(random_bytes(4)) . '.pdf';
        file_put_contents($tmp, "%PDF-1.4\n%%EOF\n");

        try {
            $mime = File::mimeType($tmp);
            $this->assertSame('application/pdf', $mime);
            $this->assertSame('pdf', File::extensionForMimeType((string) $mime));
        } finally {
            @unlink($tmp);
        }
    }
}
