<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class FilesystemReceivedStamp implements NonSendableStampInterface
{
    private $fileEnvelope;
    private $queueName;

    public function __construct(FileQueueBlock $fileEnvelope, string $queueName)
    {
        $this->fileEnvelope = $fileEnvelope;
        $this->queueName = $queueName;
    }

    public function getFileEnvelope(): FileQueueBlock
    {
        return $this->fileEnvelope;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }
}
