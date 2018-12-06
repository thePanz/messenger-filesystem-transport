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
    private $serializer;
    private $filesystem;
    private $lockFactory;

    public function __construct(
        SerializerInterface $serializer,
        Filesystem $filesystem,
        Factory $lockFactory
    ) {
        $this->filesystem = $filesystem;
        $this->lockFactory = $lockFactory;
        $this->serializer = $serializer;
    }

    public function createTransport(string $dsn, array $options): TransportInterface
    {
        return new FilesystemTransport(
            Connection::fromDsn($dsn, $this->filesystem, $this->lockFactory, $options),
            $this->serializer
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === \strpos($dsn, 'filesystem://');
    }
}
