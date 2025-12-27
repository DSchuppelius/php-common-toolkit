<?php
/*
 * Created on   : Tue Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ColumnWidthConfigTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class ColumnWidthConfigTest extends BaseTestCase {

    public function testConstructorWithDefaultWidth(): void {
        $config = new ColumnWidthConfig(20);
        $this->assertEquals(20, $config->getDefaultWidth());
        $this->assertEquals(20, $config->getColumnWidth('anyColumn'));
    }

    public function testConstructorWithoutDefaultWidth(): void {
        $config = new ColumnWidthConfig();
        $this->assertNull($config->getDefaultWidth());
        $this->assertNull($config->getColumnWidth('anyColumn'));
    }

    public function testSetColumnWidth(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidth('name', 10);
        $config->setColumnWidth(0, 15);

        $this->assertEquals(10, $config->getColumnWidth('name'));
        $this->assertEquals(15, $config->getColumnWidth(0));
        $this->assertNull($config->getColumnWidth('otherColumn'));
    }

    public function testSetColumnWidthInvalidThrowsException(): void {
        $config = new ColumnWidthConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Spaltenbreite muss mindestens 1 Zeichen betragen');
        $config->setColumnWidth('test', 0);
    }

    public function testSetColumnWidths(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidths([
            'name' => 10,
            'email' => 20,
            0 => 5
        ]);

        $this->assertEquals(10, $config->getColumnWidth('name'));
        $this->assertEquals(20, $config->getColumnWidth('email'));
        $this->assertEquals(5, $config->getColumnWidth(0));
    }

    public function testSetDefaultWidth(): void {
        $config = new ColumnWidthConfig();
        $config->setDefaultWidth(15);

        $this->assertEquals(15, $config->getDefaultWidth());
        $this->assertEquals(15, $config->getColumnWidth('anyColumn'));
    }

    public function testSetDefaultWidthInvalidThrowsException(): void {
        $config = new ColumnWidthConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Standardbreite muss mindestens 1 Zeichen betragen');
        $config->setDefaultWidth(0);
    }

    public function testSetTruncationStrategy(): void {
        $config = new ColumnWidthConfig();

        $config->setTruncationStrategy(TruncationStrategy::ELLIPSIS);
        $this->assertEquals(TruncationStrategy::ELLIPSIS, $config->getTruncationStrategy());

        $config->setTruncationStrategy(TruncationStrategy::TRUNCATE);
        $this->assertEquals(TruncationStrategy::TRUNCATE, $config->getTruncationStrategy());
    }

    public function testTruncateValueTruncateStrategy(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidth('test', 5);
        $config->setTruncationStrategy(TruncationStrategy::TRUNCATE);

        $result = $config->truncateValue('hello world', 'test');
        $this->assertEquals('hello', $result);
    }

    public function testTruncateValueEllipsisStrategy(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidth('test', 10);
        $config->setTruncationStrategy(TruncationStrategy::ELLIPSIS);

        $result = $config->truncateValue('hello world test', 'test');
        $this->assertEquals('hello w...', $result);
    }

    public function testTruncateValueNoTruncationNeeded(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidth('test', 10);

        $result = $config->truncateValue('hello', 'test');
        $this->assertEquals('hello', $result);
    }

    public function testTruncateValueEllipsisStrategyShortWidth(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidth('test', 3);
        $config->setTruncationStrategy(TruncationStrategy::ELLIPSIS);

        // Bei sehr kleiner Breite sollte truncate verwendet werden
        $result = $config->truncateValue('hello world', 'test');
        $this->assertEquals('hel', $result);
    }

    public function testHasWidthConfig(): void {
        $config = new ColumnWidthConfig(10);
        $config->setColumnWidth('name', 15);

        $this->assertTrue($config->hasWidthConfig('name'));
        $this->assertTrue($config->hasWidthConfig('otherColumn')); // wegen default width

        $config2 = new ColumnWidthConfig();
        $config2->setColumnWidth('name', 15);

        $this->assertTrue($config2->hasWidthConfig('name'));
        $this->assertFalse($config2->hasWidthConfig('otherColumn')); // keine default width
    }

    public function testGetAllColumnWidths(): void {
        $config = new ColumnWidthConfig();
        $config->setColumnWidths([
            'name' => 10,
            'email' => 20,
            0 => 5
        ]);

        $widths = $config->getAllColumnWidths();
        $expected = [
            'name' => 10,
            'email' => 20,
            0 => 5
        ];

        $this->assertEquals($expected, $widths);
    }
}
