<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class FilesystemReceiver implements ReceiverInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var bool
     */
    private $shouldStop;

    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
        $this->shouldStop = false;
    }

    public function receive(callable $handler): void
    {
        while (!$this->shouldStop) {
            $message = $this->connection->get();
            if (!$message) {
                $handler(null);

                \usleep($this->connection->getConnectionOptions()['loop_sleep']);
                if (\function_exists('pcntl_signal_dispatch')) {
                    \pcntl_signal_dispatch();
                }

                continue;
            }

            try {
                $handler($this->serializer->decode([
                    'body' => $message->body,
                    'headers' => $message->headers,
                ]));
            } finally {
                if (\function_exists('pcntl_signal_dispatch')) {
                    \pcntl_signal_dispatch();
                }
            }
        }
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }
}
