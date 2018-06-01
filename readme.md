# Filesystem Transport for Symfony Messenger

[![Latest Stable Version](https://poser.pugx.org/pnz/messenger-filesystem-transport/version)](https://packagist.org/packages/pnz/messenger-filesystem-transport)
[![Total Downloads](https://poser.pugx.org/pnz/messenger-filesystem-transport/downloads)](https://packagist.org/packages/pnz/messenger-filesystem-transport)
[![Latest Unstable Version](https://poser.pugx.org/pnz/messenger-filesystem-transport/v/unstable)](//packagist.org/packages/pnz/messenger-filesystem-transport)
[![License](https://poser.pugx.org/pnz/messenger-filesystem-transport/license)](https://packagist.org/packages/pnz/messenger-filesystem-transport)

Extends the [Symfony Messenger](https://symfony.com/doc/master/components/messenger.html) component to
handle the filesystem transport.
Queues are processed locally by storing and retrieving messages from the filesystem.

The queuing is implemented as a *LIFO* (Last-In, First-Out) list, this to optimize the filesystem
usage and the r/w operations.

## Install

```bash
composer require pnz/messenger-filesystem-transport
```

This transport handles the `filesystem://` schema, use the `FilesystemTransportFactory`
to create the transport.

Symfony configuration:

1. Register the transport factory:

```yaml
#  config/services.yaml
Pnz\Messenger\FilesystemTransport\FilesystemTransportFactory:
    arguments:
        $encoder: '@messenger.transport.encoder'
        $decoder: '@messenger.transport.decoder'
        # Both the "filesystem" and "lock.factory" services can be auto-wired by Symfony
        $filesystem: '@filesystem'
        $lockFactory: '@lock.factory'
    # Configure the tags: this enables the `filesystem://` transport to be auto-discovered
    tags: ['messenger.transport_factory']
```

2. Configure the Filesystem transport:
```yaml
#  config/packages/messenger.yaml
parameters:
  # Default ENV value: the queue messages will be stored in the `var/queue` folder,
  # The trailing `/` is required for match the `filesystem://` schema
  env(MESSENGER_TRANSPORT_DSN): "filesystem:/%kernel.project_dir%/var/queue"

framework:
    messenger:
        transports:
            filesystem: '%env(resolve:MESSENGER_TRANSPORT_DSN)%'

        routing:
            App\Message\MyMessage: filesystem
```

## Configuration

The DSN includes the following query parameters:

- `compress`: Enable/Disable compression of messages storage (gzinflate/gzdeflate), use `compress=true` (default: false)
- `loop_sleep`: Define the sleep interval between loops in micro-seconds, use `loop_sleep=MICRO-SECONDS` (default: 500000)

Example:
```bash
# .env

# Enable compression, and sleep for 0.8 secods during loops if the queue is empty
MESSENGER_TRANSPORT_DSN="filesystem:/%kernel.project_dir%/var/queue/default?compress=true&loop_sleep=800000"
```

## Todo
- Add PHP CS and static analysis
- Add tests
- Implement a bundle to auto-register the `filesystem://` transport
