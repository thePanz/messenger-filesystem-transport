<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FilesystemTransport implements TransportInterface
{
    private $encoder;
    private $decoder;
    private $connection;
    private $receiver;
    private $sender;

    public function __construct(SerializerInterface $encoder, SerializerInterface $decoder, Connection $connection)
    {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->connection = $connection;
    }

    public function receive(callable $handler): void
    {
        ($this->receiver ?? $this->getReceiver())->receive($handler);
    }

    public function stop(): void
    {
        ($this->receiver ?? $this->getReceiver())->stop();
    }

    public function send(Envelope $envelope): Envelope
    {
        return ($this->sender ?? $this->getSender())->send($envelope);
    }

    private function getReceiver()
    {
        return $this->receiver = new FilesystemReceiver($this->decoder, $this->connection);
    }

    private function getSender()
    {
        return $this->sender = new FilesystemSender($this->encoder, $this->connection);
    }
}
