# Filesystem Transport for Symfony Messenger

[![Latest Stable Version](https://poser.pugx.org/pnz/messenger-filesystem-transport/version)](https://packagist.org/packages/pnz/messenger-filesystem-transport)
[![Total Downloads](https://poser.pugx.org/pnz/messenger-filesystem-transport/downloads)](https://packagist.org/packages/pnz/messenger-filesystem-transport)
[![License](https://poser.pugx.org/pnz/messenger-filesystem-transport/license)](https://packagist.org/packages/pnz/messenger-filesystem-transport)
[![Latest Unstable Version](https://poser.pugx.org/pnz/messenger-filesystem-transport/v/unstable)](//packagist.org/packages/pnz/messenger-filesystem-transport)
[![Build Status](https://travis-ci.com/thePanz/messenger-filesystem-transport.svg?branch=master)](https://travis-ci.com/thePanz/messenger-filesystem-transport)

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

Symfony configuration: use the [Filesystem Transport Bundle](https://packagist.org/packages/pnz/messenger-filesystem-transport-bundle) Bundle.


### Install without the Symfony Bundle:
1. Register the transport factory:

```yaml
#  config/services.yaml
Pnz\Messenger\FilesystemTransport\FilesystemTransportFactory:
    # The following lines are not needed if "autowire" is enabled
    arguments:
        $filesystem: '@filesystem'
        $lockFactory: '@lock.factory'
    # Enable the `filesystem://` transport to be auto-discovered, this is not needed when "autoconfigure" is enabled
    tags: ['messenger.transport_factory']
```

2. Configure the Filesystem transport:
```yaml
#  config/packages/messenger.yaml
parameters:
  # The path *MUST* specify an absolute path of the directory where the queue will be stored
  # Example1: the queue messages will be stored in the project's `var/queue` directory
  env(MESSENGER_TRANSPORT_DSN): "filesystem://%kernel.project_dir%/var/queue"
  # Example2: use the `/tmp/queue` directory (note the triple `/`)
  env(MESSENGER_TRANSPORT_DSN): "filesystem:///tmp/queue"

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

# Enable compression, and sleep for 0.8 seconds during loops if the queue is empty
MESSENGER_TRANSPORT_DSN="filesystem://%kernel.project_dir%/var/queue/default?compress=true&loop_sleep=800000"
```
