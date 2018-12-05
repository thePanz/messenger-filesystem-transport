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
    private $encoder;

    /**
     * @var SerializerInterface
     */
    private $decoder;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Factory
     */
    private $lockFactory;

    public function __construct(
        SerializerInterface $encoder,
        SerializerInterface $decoder,
        Filesystem $filesystem,
        Factory $lockFactory
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->filesystem = $filesystem;
        $this->lockFactory = $lockFactory;
    }

    public function createTransport(string $dsn, array $options): TransportInterface
    {
        return new FilesystemTransport(
            $this->encoder,
            $this->decoder,
            Connection::fromDsn($dsn, $this->filesystem, $this->lockFactory, $options)
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === \strpos($dsn, 'filesystem://');
    }
}
