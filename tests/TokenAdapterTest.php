<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Tests;

use OCA\AdminOffboard\Adapter\TokenAdapter;
use PHPUnit\Framework\TestCase;

class TokenAdapterTest extends TestCase
{
    private TokenAdapter $tokenAdapter;

    protected function setUp(): void
    {
        $this->tokenAdapter = new TokenAdapter(
            \OCP\Server::get(\OCP\Security\ISecureRandom::class),
            \OCP\Server::get(\OCP\IDBConnection::class)
        );
    }

    public function testCountUserTokens(): void
    {
        $count = $this->tokenAdapter->countUserTokens('nonexistent_user_123');
        $this->assertEquals(0, $count);
    }

    public function testGetUserTokens(): void
    {
        $tokens = $this->tokenAdapter->getUserTokens('nonexistent_user_123');
        $this->assertIsArray($tokens);
        $this->assertEmpty($tokens);
    }
}
