<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pnz\Messenger\FilesystemTransport\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockInterface;

/**
 * @covers \Pnz\Messenger\FilesystemTransport\Connection
 */
class ConnectionTest extends TestCase
{
    private const QUEUE_DIR = __DIR__.'/queue-temp';

    private const DEFAULT_OPTIONS = ['compress' => false, 'loop_sleep' => 500000];

    /** @var Filesystem */
    private $filesystem;

    /** @var LockInterface|MockObject */
    private $lock;

    protected function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->lock = $this->createMock(LockInterface::class);
    }

    public function connectionOptionsDataprovider(): iterable
    {
        yield [self::DEFAULT_OPTIONS, []];
        yield [self::DEFAULT_OPTIONS, ['extra-option' => 1234]];
        yield [['compress' => true, 'loop_sleep' => 500000], ['compress' => true]];
        yield [['compress' => false, 'loop_sleep' => 800000], ['loop_sleep' => 800000]];

        // Handle conversions
        yield [['compress' => true, 'loop_sleep' => 500000], ['compress' => 1]];
        yield [['compress' => true, 'loop_sleep' => 500000], ['compress' => 'on']];
        yield [['compress' => true, 'loop_sleep' => 500000], ['compress' => 'true']];
        yield [['compress' => true, 'loop_sleep' => 500000], ['compress' => 'yes']];

        yield [self::DEFAULT_OPTIONS, ['compress' => 0]];
        yield [self::DEFAULT_OPTIONS, ['compress' => 'false']];
        yield [self::DEFAULT_OPTIONS, ['compress' => 'off']];
        yield [self::DEFAULT_OPTIONS, ['compress' => 'no']];

        yield [['compress' => false, 'loop_sleep' => 800000], ['loop_sleep' => '800000']];
    }

    /**
     * @dataProvider connectionOptionsDataprovider
     */
    public function testGetOptions(array $expected, array $input): void
    {
        $connection = $this->buildConnection(self::QUEUE_DIR, $input);
        $this->assertSame($expected, $connection->getConnectionOptions());
    }

    public function invalidDsnDataprovider()
    {
        yield [''];
        yield ['::'];
        yield ['filesystem://'];
        yield ['filesystem://'];
    }

    /**
     * @dataProvider invalidDsnDataprovider
     */
    public function testFromDsnWithInvalidSchemaFails(string $dsn): void
    {
        $this->expectException(\InvalidArgumentException::class);
        /** @var Factory|MockObject $lockFactory */
        $lockFactory = $this->createMock(Factory::class);
        $lockFactory->expects($this->never())
            ->method('createLock');

        Connection::fromDsn($dsn, $this->filesystem, $lockFactory);
    }

    public function validDsnDataprovider()
    {
        yield ['filesystem:///tmp', '/tmp', self::DEFAULT_OPTIONS];
        yield ['filesystem:///var/queue/', '/var/queue/', self::DEFAULT_OPTIONS];
        yield ['filesystem:///var/queue/?compress=true', '/var/queue/', ['compress' => true, 'loop_sleep' => 500000]];
        yield ['filesystem:///var/queue/?compress=false', '/var/queue/', ['compress' => false, 'loop_sleep' => 500000]];
        yield ['filesystem:///var/queue/?loop_sleep=800000', '/var/queue/', ['compress' => false, 'loop_sleep' => 800000]];
        yield ['filesystem:///var/queue/?loop_sleep=800000&compress=true', '/var/queue/', ['compress' => true, 'loop_sleep' => 800000]];
    }

    /**
     * @dataProvider validDsnDataprovider
     */
    public function testFromDsnSucceeds(string $dsn, string $expectedLock, array $expectedOptions): void
    {
        /** @var Factory|MockObject $lockFactory */
        $lockFactory = $this->createMock(Factory::class);
        $lockFactory->expects($this->once())
            ->method('createLock')
            ->with($expectedLock)
            ->willReturn($this->lock);

        $connection = Connection::fromDsn($dsn, $this->filesystem, $lockFactory);
        $this->assertSame($expectedOptions, $connection->getConnectionOptions());
    }

    private function buildConnection(string $path = self::QUEUE_DIR, array $options = []): Connection
    {
        return new Connection($path, $this->filesystem, $this->lock, $options);
    }
}
