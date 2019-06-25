<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

class FileQueueBlock
{
    /**
     * @var string
     */
    public $body = '';

    /**
     * @var string[]
     */
    public $headers = [];

    /**
     * Data class to store the Message data into the filesystem queue.
     *
     * @param string   $body
     * @param string[] $headers
     */
    public function __construct(string $body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }
}
