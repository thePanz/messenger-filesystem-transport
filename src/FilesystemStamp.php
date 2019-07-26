<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Messenger\Stamp\StampInterface;

class FilesystemStamp implements StampInterface
{
    private $routingKey;
    private $attributes;

    public function __construct(string $routingKey = null, array $attributes = [])
    {
        $this->routingKey = $routingKey;
        $this->attributes = $attributes;
    }

    public function getRoutingKey(): ?string
    {
        return $this->routingKey;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
