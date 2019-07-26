<?php

declare(strict_types=1);

namespace Pnz\Messenger\FilesystemTransport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockInterface;

class Connection
{
    private const QUEUE_INDEX_FILENAME = 'queue.index';
    private const QUEUE_DATA_FILENAME = 'queue.data';
    private const LONG_BYTE_LENGTH = 8;

    /**
     * @var string
     */
    private $path;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LockInterface
     */
    private $lock;

    /**
     * @var array
     */
    private $options;

    public function __construct(string $path, Filesystem $filesystem, LockInterface $lock, array $options)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->lock = $lock;
        $this->options = [
            'compress' => \filter_var($options['compress'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'loop_sleep' => \filter_var($options['loop_sleep'] ?? 500000, FILTER_VALIDATE_INT),
        ];
    }

    public function setup(): void
    {
        $this->filesystem->mkdir($this->path);
        $this->filesystem->touch($this->getQueueFiles());
    }

    public function ack(FileQueueBlock $message, string $queueName): void
    {
        //TODO: not implemented
    }

    public function nack(FileQueueBlock $message, string $queueName): void
    {
        //TODO: not implemented
    }

    /**
     * @return string[]
     */
    public function getQueueNames(): array
    {
        return [
            self::QUEUE_INDEX_FILENAME,
        ];
    }

    public static function fromDsn(string $dsn, Filesystem $filesystem, Factory $lockFactory, array $options = []): self
    {
        // Ensure the scheme is correct, plus the absolute path
        if (0 !== \strpos($dsn, 'filesystem:///')) {
            throw new \InvalidArgumentException(\sprintf('The given DSN "%s" is not valid for the FilesystemTransport, wrong scheme.', $dsn));
        }

        // Build an URI with the given path, so that we can use the parse_url function
        $uri = 'scheme://host/'.\substr($dsn, 14);

        if (false === $parsedUrl = \parse_url($uri)) {
            throw new \InvalidArgumentException(\sprintf('The given Filesystem DSN "%s" is invalid.', $dsn));
        }

        $path = $parsedUrl['path'] ?? null;
        if (!$path) {
            throw new \InvalidArgumentException(\sprintf('The given Filesystem DSN "%s" is invalid: path missing.', $dsn));
        }

        if (isset($parsedUrl['query'])) {
            \parse_str($parsedUrl['query'], $parsedQuery);
            $options = \array_replace_recursive($options, $parsedQuery);
        }

        return new self($path, $filesystem, $lockFactory->createLock($path), $options);
    }

    public function publish(string $body, array $headers = [], FilesystemStamp $filesystemStamp = null): void
    {
        $this->lock->acquire(true);
        if ($this->shouldSetup()) {
            $this->setup();
        }

        // TODO: $filesystemStamp is not used. Does this make sense?
        // $headers = array_merge($amqpStamp ? $filesystemStamp->getAttributes() : [], $headers)

        $block = new FileQueueBlock($body, $headers);

        // Write the block to the data file
        $dataFile = \fopen($this->getQueueFiles()[self::QUEUE_DATA_FILENAME], 'a+b');
        if (!$dataFile) {
            $this->lock->release();

            throw new \RuntimeException(\sprintf(
                'Filesystem queue: unable to open data-file %s',
                $this->getQueueFiles()[self::QUEUE_DATA_FILENAME]
            ));
        }

        $data = \serialize($block);
        $data = $this->options['compress'] ? \gzdeflate($data) : $data;
        \fwrite($dataFile, $data);
        \fclose($dataFile);

        // The index file contains the list of block sizes with a fixed-length structure
        // This allows a fast fetching of blocks with a direct seek on the data-file
        $indexFile = \fopen($this->getQueueFiles()[self::QUEUE_INDEX_FILENAME], 'a+b');

        if (!$indexFile) {
            $this->lock->release();

            throw new \RuntimeException(\sprintf(
                'Filesystem queue: unable to open index-file %s. Critical: the queue files are not in sync anymore!',
                $this->getQueueFiles()[self::QUEUE_DATA_FILENAME]
            ));
        }

        // The 'J': unsigned long long (always 64 bit, big endian byte order)
        \fwrite($indexFile, \pack('J', \strlen($data)));
        \fclose($indexFile);

        $this->lock->release();
    }

    /**
     * TODO: $queueName is not used.
     */
    public function get(string $queueName): ?FileQueueBlock
    {
        $this->lock->acquire(true);
        if ($this->shouldSetup()) {
            $this->setup();
        }

        $indexFile = \fopen($this->getQueueFiles()[self::QUEUE_INDEX_FILENAME], 'c+b');
        if (!$indexFile) {
            $this->lock->release();

            throw new \RuntimeException(\sprintf(
                'Filesystem queue: unable to open index-file %s',
                $this->getQueueFiles()[self::QUEUE_DATA_FILENAME]
            ));
        }

        $indexFileSize = (int) \fstat($indexFile)['size'];

        // If the index file is empty, there's nothing to do.
        if (!$indexFileSize) {
            \fclose($indexFile);
            $this->lock->release();

            return null;
        }

        \fseek($indexFile, -1 * self::LONG_BYTE_LENGTH, SEEK_END);
        $size = \current(\unpack('J', \fread($indexFile, self::LONG_BYTE_LENGTH)));
        \ftruncate($indexFile, $indexFileSize - self::LONG_BYTE_LENGTH);
        \fclose($indexFile);

        $dataFile = \fopen($this->getQueueFiles()[self::QUEUE_DATA_FILENAME], 'c+b');
        if (!$dataFile) {
            $this->lock->release();

            throw new \RuntimeException(\sprintf(
                'Filesystem queue: unable to open data-file %s. Critical: the data files are not in sync anymore!',
                $this->getQueueFiles()[self::QUEUE_DATA_FILENAME]
            ));
        }

        \fseek($dataFile, -1 * $size, SEEK_END);
        $data = \fread($dataFile, $size);
        $dataFileSize = (int) \fstat($dataFile)['size'];

        \ftruncate($dataFile, $dataFileSize - $size);
        \fclose($dataFile);
        $this->lock->release();

        $block = \unserialize(
            $this->options['compress'] ? \gzinflate($data) : $data,
            ['allowed_classes' => [FileQueueBlock::class]]
        );

        return $block;
    }

    public function getConnectionOptions(): array
    {
        return $this->options;
    }

    protected function shouldSetup(): bool
    {
        return !$this->filesystem->exists($this->getQueueFiles());
    }

    private function getQueueFiles(): array
    {
        return [
          self::QUEUE_DATA_FILENAME => $this->path.\DIRECTORY_SEPARATOR.self::QUEUE_DATA_FILENAME,
          self::QUEUE_INDEX_FILENAME => $this->path.\DIRECTORY_SEPARATOR.self::QUEUE_INDEX_FILENAME,
      ];
    }
}
