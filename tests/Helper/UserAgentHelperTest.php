<?php
/*
 * Created on   : Wed Jul 16 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : UserAgentHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\UserAgentHelper;
use Tests\Contracts\BaseTestCase;

class UserAgentHelperTest extends BaseTestCase {
    private const CHROME_WIN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';
    private const SAFARI_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15';
    private const FIREFOX_LINUX = 'Mozilla/5.0 (X11; Linux x86_64; rv:126.0) Gecko/20100101 Firefox/126.0';
    private const EDGE_WIN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0';
    private const SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1';
    private const CHROME_ANDROID = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36';
    private const ANDROID_TABLET = 'Mozilla/5.0 (Linux; Android 13; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
    private const IPAD = 'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1';
    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    private const CURL = 'curl/8.4.0';

    // ===== Browser =====

    public function test_browser_detects_common_desktop_browsers(): void {
        $this->assertSame('Chrome', UserAgentHelper::browser(self::CHROME_WIN));
        $this->assertSame('Safari', UserAgentHelper::browser(self::SAFARI_MAC));
        $this->assertSame('Firefox', UserAgentHelper::browser(self::FIREFOX_LINUX));
    }

    public function test_browser_prefers_edge_over_chrome(): void {
        $this->assertSame('Edge', UserAgentHelper::browser(self::EDGE_WIN));
    }

    public function test_browser_handles_null_and_empty(): void {
        $this->assertSame(UserAgentHelper::UNKNOWN, UserAgentHelper::browser(null));
        $this->assertSame(UserAgentHelper::UNKNOWN, UserAgentHelper::browser(''));
        $this->assertSame(UserAgentHelper::UNKNOWN, UserAgentHelper::browser('total garbage string'));
    }

    // ===== OS =====

    public function test_os_detection(): void {
        $this->assertSame('Windows', UserAgentHelper::os(self::CHROME_WIN));
        $this->assertSame('macOS', UserAgentHelper::os(self::SAFARI_MAC));
        $this->assertSame('Linux', UserAgentHelper::os(self::FIREFOX_LINUX));
        $this->assertSame('iOS', UserAgentHelper::os(self::SAFARI_IPHONE));
        $this->assertSame('Android', UserAgentHelper::os(self::CHROME_ANDROID));
    }

    public function test_ios_wins_over_macos_on_ipad(): void {
        // iPad meldet "Mac OS X" mit — darf nicht als macOS klassifiziert werden.
        $this->assertSame('iOS', UserAgentHelper::os(self::IPAD));
    }

    // ===== Gerätetyp =====

    public function test_device_type(): void {
        $this->assertSame(UserAgentHelper::DEVICE_DESKTOP, UserAgentHelper::deviceType(self::CHROME_WIN));
        $this->assertSame(UserAgentHelper::DEVICE_MOBILE, UserAgentHelper::deviceType(self::SAFARI_IPHONE));
        $this->assertSame(UserAgentHelper::DEVICE_MOBILE, UserAgentHelper::deviceType(self::CHROME_ANDROID));
        $this->assertSame(UserAgentHelper::DEVICE_TABLET, UserAgentHelper::deviceType(self::IPAD));
        $this->assertSame(UserAgentHelper::DEVICE_TABLET, UserAgentHelper::deviceType(self::ANDROID_TABLET));
        $this->assertSame(UserAgentHelper::DEVICE_BOT, UserAgentHelper::deviceType(self::GOOGLEBOT));
    }

    // ===== Bots =====

    public function test_bot_detection(): void {
        $this->assertTrue(UserAgentHelper::isBot(self::GOOGLEBOT));
        $this->assertTrue(UserAgentHelper::isBot(self::CURL));
        $this->assertFalse(UserAgentHelper::isBot(self::CHROME_WIN));
        $this->assertFalse(UserAgentHelper::isBot(null));
    }

    // ===== shortLabel =====

    public function test_short_label(): void {
        $this->assertSame('Chrome · Windows', UserAgentHelper::shortLabel(self::CHROME_WIN));
        $this->assertSame('Safari · iOS', UserAgentHelper::shortLabel(self::SAFARI_IPHONE));
        $this->assertSame(UserAgentHelper::UNKNOWN, UserAgentHelper::shortLabel(null));
    }

    public function test_short_label_for_bot_uses_name_or_generic(): void {
        $this->assertSame('Bot', UserAgentHelper::shortLabel(self::GOOGLEBOT));
        $this->assertSame('curl', UserAgentHelper::shortLabel(self::CURL));
    }

    // ===== parse =====

    public function test_parse_returns_full_shape(): void {
        $parsed = UserAgentHelper::parse(self::CHROME_WIN);
        $this->assertSame([
            'browser' => 'Chrome',
            'os' => 'Windows',
            'device' => UserAgentHelper::DEVICE_DESKTOP,
            'is_bot' => false,
        ], $parsed);
    }
}
