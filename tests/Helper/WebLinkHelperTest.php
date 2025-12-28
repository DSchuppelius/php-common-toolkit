<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WebLinkHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\WebLinkHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class WebLinkHelperTest extends BaseTestCase {
    #[DataProvider('validUrlProvider')]
    public function testIsUrlWithValidUrls(string $url): void {
        $this->assertTrue(WebLinkHelper::isUrl($url));
    }

    #[DataProvider('invalidUrlProvider')]
    public function testIsUrlWithInvalidUrls(?string $url): void {
        $this->assertFalse(WebLinkHelper::isUrl($url));
    }

    public static function validUrlProvider(): array {
        return [
            'https url' => ['https://example.com'],
            'http url' => ['http://example.com'],
            'ftp url' => ['ftp://ftp.example.com'],
            'url with path' => ['https://example.com/path/to/file'],
            'url with query' => ['https://example.com?param=value'],
            'url with fragment' => ['https://example.com#section'],
            'url with port' => ['https://example.com:8080'],
            'url with all parts' => ['https://user:pass@example.com:8080/path?query=value#fragment'],
            'localhost' => ['http://localhost'],
            'ip address' => ['http://192.168.1.1'],
        ];
    }

    public static function invalidUrlProvider(): array {
        return [
            'null' => [null],
            'empty string' => [''],
            'plain text' => ['not a url'],
            'missing scheme' => ['example.com'],
            'missing host' => ['https://'],
            'javascript scheme' => ['javascript:alert(1)'],
        ];
    }

    public function testIsHttpUrl(): void {
        $this->assertTrue(WebLinkHelper::isHttpUrl('https://example.com'));
        $this->assertTrue(WebLinkHelper::isHttpUrl('http://example.com'));
        $this->assertFalse(WebLinkHelper::isHttpUrl('ftp://ftp.example.com'));
        $this->assertFalse(WebLinkHelper::isHttpUrl(null));
    }

    public function testIsSecure(): void {
        $this->assertTrue(WebLinkHelper::isSecure('https://example.com'));
        $this->assertFalse(WebLinkHelper::isSecure('http://example.com'));
        $this->assertFalse(WebLinkHelper::isSecure('ftp://ftp.example.com'));
    }

    public function testValidateUrl(): void {
        $this->assertTrue(WebLinkHelper::validateUrl('https://example.com'));
        $this->assertTrue(WebLinkHelper::validateUrl('http://example.com'));

        // Mit eingeschränkten Schemas
        $this->assertTrue(WebLinkHelper::validateUrl('https://example.com', false, ['https']));
        $this->assertFalse(WebLinkHelper::validateUrl('http://example.com', false, ['https']));
    }

    public function testNormalize(): void {
        // Standard-Port entfernen
        $this->assertEquals(
            'https://example.com/',
            WebLinkHelper::normalize('https://example.com:443/')
        );

        $this->assertEquals(
            'http://example.com/',
            WebLinkHelper::normalize('http://example.com:80/')
        );

        // Nicht-Standard-Port beibehalten
        $this->assertEquals(
            'https://example.com:8080/',
            WebLinkHelper::normalize('https://example.com:8080/')
        );

        // Host zu Kleinbuchstaben
        $this->assertEquals(
            'https://example.com/Path',
            WebLinkHelper::normalize('https://EXAMPLE.COM/Path')
        );

        // Query-Parameter sortieren
        $this->assertEquals(
            'https://example.com/path?a=1&b=2',
            WebLinkHelper::normalize('https://example.com/path?b=2&a=1')
        );

        // Ungültige URL
        $this->assertNull(WebLinkHelper::normalize('not a url'));
    }

    public function testGetScheme(): void {
        $this->assertEquals('https', WebLinkHelper::getScheme('https://example.com'));
        $this->assertEquals('http', WebLinkHelper::getScheme('http://example.com'));
        $this->assertEquals('ftp', WebLinkHelper::getScheme('ftp://ftp.example.com'));
        $this->assertNull(WebLinkHelper::getScheme(null));
    }

    public function testGetHost(): void {
        $this->assertEquals('example.com', WebLinkHelper::getHost('https://example.com/path'));
        $this->assertEquals('sub.example.com', WebLinkHelper::getHost('https://sub.example.com'));
        $this->assertEquals('192.168.1.1', WebLinkHelper::getHost('http://192.168.1.1:8080'));
        $this->assertNull(WebLinkHelper::getHost(null));
    }

    public function testGetDomain(): void {
        $this->assertEquals('example.com', WebLinkHelper::getDomain('https://example.com'));
        $this->assertEquals('example.com', WebLinkHelper::getDomain('https://www.example.com'));
        $this->assertEquals('example.com', WebLinkHelper::getDomain('https://sub.domain.example.com'));
        $this->assertEquals('example.co.uk', WebLinkHelper::getDomain('https://www.example.co.uk'));
        $this->assertEquals('192.168.1.1', WebLinkHelper::getDomain('http://192.168.1.1'));
    }

    public function testGetSubdomain(): void {
        $this->assertNull(WebLinkHelper::getSubdomain('https://example.com'));
        $this->assertEquals('www', WebLinkHelper::getSubdomain('https://www.example.com'));
        $this->assertEquals('sub.domain', WebLinkHelper::getSubdomain('https://sub.domain.example.com'));
    }

    public function testGetPort(): void {
        $this->assertNull(WebLinkHelper::getPort('https://example.com'));
        $this->assertEquals(8080, WebLinkHelper::getPort('https://example.com:8080'));
        $this->assertEquals(443, WebLinkHelper::getPort('https://example.com:443'));
    }

    public function testGetPath(): void {
        $this->assertEquals('/path/to/file', WebLinkHelper::getPath('https://example.com/path/to/file'));
        $this->assertEquals('/path', WebLinkHelper::getPath('https://example.com/path?query=value'));
        $this->assertNull(WebLinkHelper::getPath('https://example.com'));
    }

    public function testGetQueryString(): void {
        $this->assertEquals('param=value', WebLinkHelper::getQueryString('https://example.com?param=value'));
        $this->assertEquals('a=1&b=2', WebLinkHelper::getQueryString('https://example.com?a=1&b=2'));
        $this->assertNull(WebLinkHelper::getQueryString('https://example.com'));
    }

    public function testGetQueryParams(): void {
        $this->assertEquals(
            ['param' => 'value'],
            WebLinkHelper::getQueryParams('https://example.com?param=value')
        );
        $this->assertEquals(
            ['a' => '1', 'b' => '2'],
            WebLinkHelper::getQueryParams('https://example.com?a=1&b=2')
        );
        $this->assertEquals([], WebLinkHelper::getQueryParams('https://example.com'));
    }

    public function testGetQueryParam(): void {
        $url = 'https://example.com?a=1&b=2&c=3';
        $this->assertEquals('1', WebLinkHelper::getQueryParam($url, 'a'));
        $this->assertEquals('2', WebLinkHelper::getQueryParam($url, 'b'));
        $this->assertNull(WebLinkHelper::getQueryParam($url, 'nonexistent'));
    }

    public function testGetFragment(): void {
        $this->assertEquals('section', WebLinkHelper::getFragment('https://example.com#section'));
        $this->assertEquals('top', WebLinkHelper::getFragment('https://example.com/path?q=v#top'));
        $this->assertNull(WebLinkHelper::getFragment('https://example.com'));
    }

    public function testAddQueryParams(): void {
        // Parameter hinzufügen
        $this->assertEquals(
            'https://example.com?new=param',
            WebLinkHelper::addQueryParams('https://example.com', ['new' => 'param'])
        );

        // Parameter ersetzen
        $this->assertEquals(
            'https://example.com?existing=new',
            WebLinkHelper::addQueryParams('https://example.com?existing=old', ['existing' => 'new'])
        );

        // Fragment beibehalten
        $result = WebLinkHelper::addQueryParams('https://example.com#section', ['param' => 'value']);
        $this->assertStringContainsString('param=value', $result);
        $this->assertStringContainsString('#section', $result);
    }

    public function testRemoveQueryParams(): void {
        $this->assertEquals(
            'https://example.com?b=2',
            WebLinkHelper::removeQueryParams('https://example.com?a=1&b=2', ['a'])
        );

        $this->assertEquals(
            'https://example.com',
            WebLinkHelper::removeQueryParams('https://example.com?a=1', ['a'])
        );
    }

    public function testResolveRelative(): void {
        $baseUrl = 'https://example.com/path/to/page.html';

        // Absoluter Pfad
        $this->assertEquals(
            'https://example.com/other/path',
            WebLinkHelper::resolveRelative($baseUrl, '/other/path')
        );

        // Relativer Pfad
        $this->assertEquals(
            'https://example.com/path/to/file.html',
            WebLinkHelper::resolveRelative($baseUrl, 'file.html')
        );

        // Mit .. navigieren
        $this->assertEquals(
            'https://example.com/path/other.html',
            WebLinkHelper::resolveRelative($baseUrl, '../other.html')
        );

        // Absolute URL bleibt unverändert
        $this->assertEquals(
            'https://other.com/path',
            WebLinkHelper::resolveRelative($baseUrl, 'https://other.com/path')
        );
    }

    public function testExtractUrls(): void {
        $text = 'Besuche https://example.com und http://test.org für mehr Info.';
        $urls = WebLinkHelper::extractUrls($text);

        $this->assertCount(2, $urls);
        $this->assertContains('https://example.com', $urls);
        $this->assertContains('http://test.org', $urls);

        // Duplikate entfernen
        $textWithDupes = 'https://example.com und https://example.com nochmal';
        $this->assertCount(1, WebLinkHelper::extractUrls($textWithDupes, true));
        $this->assertCount(2, WebLinkHelper::extractUrls($textWithDupes, false));
    }

    public function testBelongsToDomain(): void {
        $this->assertTrue(WebLinkHelper::belongsToDomain('https://example.com', 'example.com'));
        $this->assertTrue(WebLinkHelper::belongsToDomain('https://www.example.com', 'example.com'));
        $this->assertTrue(WebLinkHelper::belongsToDomain('https://sub.example.com', 'example.com'));

        // Ohne Subdomains
        $this->assertFalse(WebLinkHelper::belongsToDomain('https://sub.example.com', 'example.com', false));
        $this->assertTrue(WebLinkHelper::belongsToDomain('https://example.com', 'example.com', false));

        // Andere Domain
        $this->assertFalse(WebLinkHelper::belongsToDomain('https://other.com', 'example.com'));
    }

    public function testSlugify(): void {
        $this->assertEquals('hello-world', WebLinkHelper::slugify('Hello World'));
        $this->assertEquals('das-ist-ein-test', WebLinkHelper::slugify('Das ist ein Test!'));
        $this->assertEquals('deutsche-umlaute-aeoeuess', WebLinkHelper::slugify('Deutsche Umlaute: äöüß'));
        $this->assertEquals('hello_world', WebLinkHelper::slugify('Hello World', '_'));
    }

    public function testGetFileExtension(): void {
        $this->assertEquals('html', WebLinkHelper::getFileExtension('https://example.com/page.html'));
        $this->assertEquals('pdf', WebLinkHelper::getFileExtension('https://example.com/doc.PDF'));
        $this->assertEquals('jpg', WebLinkHelper::getFileExtension('https://example.com/image.jpg?size=large'));
        $this->assertNull(WebLinkHelper::getFileExtension('https://example.com/noextension'));
        $this->assertNull(WebLinkHelper::getFileExtension('https://example.com/'));
    }

    public function testGetFilename(): void {
        $this->assertEquals('document.pdf', WebLinkHelper::getFilename('https://example.com/path/document.pdf'));
        $this->assertEquals('document', WebLinkHelper::getFilename('https://example.com/path/document.pdf', false));
        $this->assertEquals('file.tar.gz', WebLinkHelper::getFilename('https://example.com/file.tar.gz'));
        $this->assertEquals('file.tar', WebLinkHelper::getFilename('https://example.com/file.tar.gz', false));
    }

    public function testEncode(): void {
        $url = 'https://example.com/path with spaces/file name.html';
        $encoded = WebLinkHelper::encode($url);
        $this->assertStringContainsString('path%20with%20spaces', $encoded);
        $this->assertStringContainsString('file%20name.html', $encoded);
    }

    public function testDecode(): void {
        $encoded = 'https://example.com/path%20with%20spaces/file%20name.html';
        $decoded = WebLinkHelper::decode($encoded);
        $this->assertEquals('https://example.com/path with spaces/file name.html', $decoded);
    }
}
