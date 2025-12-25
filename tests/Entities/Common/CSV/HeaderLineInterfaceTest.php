<?php

namespace Tests\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\HeaderLine;
use Tests\Contracts\BaseTestCase;

class HeaderLineInterfaceTest extends BaseTestCase {
    public function testGetColumnNames(): void {
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        $this->assertEquals(['Name', 'Age', 'Email'], $headerLine->getColumnNames());
    }

    public function testHasColumn(): void {
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        $this->assertTrue($headerLine->hasColumn('Name'));
        $this->assertTrue($headerLine->hasColumn('Age'));
        $this->assertTrue($headerLine->hasColumn('Email'));
        $this->assertFalse($headerLine->hasColumn('NonExistent'));
        $this->assertFalse($headerLine->hasColumn('name')); // Case sensitive
    }

    public function testGetColumnIndex(): void {
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        $this->assertEquals(0, $headerLine->getColumnIndex('Name'));
        $this->assertEquals(1, $headerLine->getColumnIndex('Age'));
        $this->assertEquals(2, $headerLine->getColumnIndex('Email'));
        $this->assertNull($headerLine->getColumnIndex('NonExistent'));
        $this->assertNull($headerLine->getColumnIndex('name')); // Case sensitive
    }

    public function testWithDuplicateColumnNames(): void {
        $headerLine = new HeaderLine(['Name', 'Name', 'Age']);

        $this->assertEquals(['Name', 'Name', 'Age'], $headerLine->getColumnNames());
        $this->assertTrue($headerLine->hasColumn('Name'));
        $this->assertEquals(0, $headerLine->getColumnIndex('Name')); // Returns first occurrence
    }

    public function testWithEmptyColumnNames(): void {
        $headerLine = new HeaderLine(['', 'Name', '']);

        $this->assertEquals(['', 'Name', ''], $headerLine->getColumnNames());
        $this->assertTrue($headerLine->hasColumn(''));
        $this->assertTrue($headerLine->hasColumn('Name'));
        $this->assertEquals(0, $headerLine->getColumnIndex('')); // Returns first occurrence
    }
}
