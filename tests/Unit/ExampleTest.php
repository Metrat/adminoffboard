<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 */

namespace OCA\AdminOffboard\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Example test class
 */
class ExampleTest extends TestCase
{
    public function testBasic(): void
    {
        $this->assertTrue(true);
    }

    public function testAddition(): void
    {
        $result = 1 + 1;
        $this->assertEquals(2, $result);
    }

    public function testString(): void
    {
        $string = 'Hello World';
        $this->assertStringContainsString('World', $string);
        $this->assertStringStartsWith('Hello', $string);
        $this->assertStringEndsWith('World', $string);
    }

    public function testArray(): void
    {
        $array = ['a', 'b', 'c'];
        $this->assertCount(3, $array);
        $this->assertContains('b', $array);
        $this->assertArrayHasKey(1, $array);
    }
}