<?php

namespace Pnz\Messenger\FilesystemTransport;

class FileQueueBlock
{
    public $body = '';
    public $headers = [];

    /**
     * Data class to store the Message data into the filesystem queue
     *
     * @param string    $body
     * @param string[]  $headers
     */
    public function __construct(string $body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }
}