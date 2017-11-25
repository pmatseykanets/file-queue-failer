# File based implementation of Laravel Queue Failer

[![Laravel 5.X](https://img.shields.io/badge/Laravel-5.X-orange.svg)](http://laravel.com)
[![StyleCI](https://styleci.io/repos/40268759/shield)](https://styleci.io/repos/40268759)
[![Build Status](https://travis-ci.org/pmatseykanets/file-queue-failer.svg)](https://travis-ci.org/pmatseykanets/file-queue-failer)
[![Latest Stable Version](https://poser.pugx.org/pmatseykanets/file-queue-failer/v/stable)](https://packagist.org/packages/pmatseykanets/file-queue-failer)
[![License](https://poser.pugx.org/pmatseykanets/file-queue-failer/license)](https://packagist.org/packages/pmatseykanets/file-queue-failer)

If you use job queues in your [Laravel](http://laravel.com) or [Lumen](http://lumen.laravel.com) project 
but don't want to store failed jobs in the database, especially if you're not using a database 
in the project itself (i.e. an API proxi) this file based failer is to rescue.

## Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Testing](#testing)
- [Security](#security)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Installation

### Install through composer

Laravel 5.3+

```bash
$ composer require pmatseykanets/file-queue-failer
```

If you're using Laravel < 5.5 or if you have package auto-discovery turned off you have to manually register the service provider:

```php
// config/app.php
'providers' => [
    /*
     * Package Service Providers...
     */
    Pvm\FileQueueFailer\Queue\QueueServiceProvider::class,
],
```

Laravel 5.0 to 5.2

```bash
$ composer require pmatseykanets/file-queue-failer:0.1.0
```

Substitute original `QueueServiceProvider` implementation in `config\app.php`  

```php
// config/app.php
'providers' => [
    // Illuminate\Queue\QueueServiceProvider::class,
    Pvm\FileQueueFailer\Queue\QueueServiceProvider::class,
];
```

## Configuration

By default failed jobs will be stored in `storage\failed_jobs` directory.

You can change the location by changing the `path` property in `failed` section of `config\queue.php` config file.

```php
// config\queue.php
'failed' => [
    'path' => '/some/other/path',
],
```

## Usage

You can use all artisan `queue` commands as usual to manage failed jobs

```bash
 queue
  queue:failed        List all of the failed queue jobs
  queue:flush         Flush all of the failed queue jobs
  queue:forget        Delete a failed queue job
  queue:retry         Retry a failed queue job
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Security

If you discover any security related issues, please email pmatseykanets@gmail.com instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Peter Matseykanets](https://github.com/pmatseykanets)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
