<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;

class FilesystemSender implements SenderInterface
{
    private $encoder;
    private $connection;

    public function __construct(EncoderInterface $encoder, Connection $connection)
    {
        $this->encoder = $encoder;
        $this->connection = $connection;
    }

    public function send(Envelope $envelope)
    {
        $encodedMessage = $this->encoder->encode($envelope);

        $this->connection->publish($encodedMessage['body'], $encodedMessage['headers']);
    }
}
