<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FilesystemTransportFactory implements TransportFactoryInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Factory
     */
    private $lockFactory;

    public function __construct(
        Filesystem $filesystem,
        Factory $lockFactory
    ) {
        $this->filesystem = $filesystem;
        $this->lockFactory = $lockFactory;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        return new FilesystemTransport(
            Connection::fromDsn($dsn, $this->filesystem, $this->lockFactory, $options),
            $serializer
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === \strpos($dsn, 'filesystem://');
    }
}
