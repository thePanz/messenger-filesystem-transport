<?php

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FilesystemTransportFactory implements TransportFactoryInterface
{
    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var DecoderInterface
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
        EncoderInterface $encoder,
        DecoderInterface $decoder,
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
            Connection::fromDsn($dsn, $options, $this->filesystem, $this->lockFactory)
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'filesystem://');
    }
}
