<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
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

    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    public function get(): iterable
    {
        foreach ($this->connection->getQueueNames() as $queueName) {
            yield from $this->getEnvelope($queueName);
        }
    }

    public function ack(Envelope $envelope): void
    {
        try {
            $stamp = $this->findFilesystemStamp($envelope);
            $this->connection->ack(
                $stamp->getFileEnvelope(),
                $stamp->getQueueName()
            );
        } catch (\RuntimeException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    public function reject(Envelope $envelope): void
    {
        $stamp = $this->findFilesystemStamp($envelope);
        $this->rejectFilesystemEnvelope(
            $stamp->getFileEnvelope(),
            $stamp->getQueueName()
        );
    }

    private function getEnvelope(string $queueName): iterable
    {
        try {
            $message = $this->connection->get($queueName);
        } catch (\RuntimeException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
        if (null === $message) {
            return;
        }
        try {
            $envelope = $this->serializer->decode([
                'body' => $message->body,
                'headers' => $message->headers,
            ]);
        } catch (MessageDecodingFailedException $exception) {
            // invalid message of some type
            $this->rejectFilesystemEnvelope($message, $queueName);
            throw $exception;
        }
        yield $envelope->with(new FilesystemReceivedStamp($message, $queueName));
    }

    private function rejectFilesystemEnvelope(FileQueueBlock $fileEnvelope, string $queueName): void
    {
        try {
            $this->connection->nack($fileEnvelope, $queueName);
        } catch (\RuntimeException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    private function findFilesystemStamp(Envelope $envelope): FilesystemReceivedStamp
    {
        /** @var FilesystemReceivedStamp|null $filesystemReceivedStamp */
        $filesystemReceivedStamp = $envelope->last(FilesystemReceivedStamp::class);
        if (null === $filesystemReceivedStamp) {
            throw new LogicException('No "FilesystemReceivedStamp" stamp found on the Envelope.');
        }

        return $filesystemReceivedStamp;
    }
}
