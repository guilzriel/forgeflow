<?php

declare(strict_types=1);

namespace ForgeFlow\Tests;

use ForgeFlow\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('FORGEFLOW_VERSION');
    }

    public function testHealthReturnsExpectedApplicationIdentity(): void
    {
        putenv('FORGEFLOW_VERSION=v0.1.0');

        $health = (new Application())->health();

        self::assertSame('forgeflow', $health['service']);
        self::assertSame('php', $health['runtime']);
        self::assertSame('healthy', $health['status']);
        self::assertSame('v0.1.0', $health['version']);
    }

    public function testHealthUsesDevelopmentVersionByDefault(): void
    {
        putenv('FORGEFLOW_VERSION');

        $health = (new Application())->health();

        self::assertSame('development', $health['version']);
    }
}
