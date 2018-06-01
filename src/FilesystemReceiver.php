<?php

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Transport\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;

class FilesystemReceiver implements ReceiverInterface
{
    private $decoder;
    private $connection;
    private $shouldStop;

    public function __construct(DecoderInterface $decoder, Connection $connection)
    {
        $this->decoder = $decoder;
        $this->connection = $connection;
    }

    public function receive(callable $handler): void
    {
        while (!$this->shouldStop) {
            $message = $this->connection->get();
            if (!$message) {
                $handler(null);

                usleep($this->connection->getConnectionOptions()['loop_sleep']);
                if (\function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }

                continue;
            }

            try {
                $handler($this->decoder->decode([
                    'body' => $message->body,
                    'headers' => $message->headers,
                ]));
            } finally {
                if (\function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
        }
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }
}
