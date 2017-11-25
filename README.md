# File based implementation of Laravel Queue Failer

[![Laravel 5.X](https://img.shields.io/badge/Laravel-5.X-orange.svg)](http://laravel.com)
[![StyleCI](https://styleci.io/repos/40268759/shield)](https://styleci.io/repos/40268759)
[![Build Status](https://travis-ci.org/pmatseykanets/file-queue-failer.svg)](https://travis-ci.org/pmatseykanets/file-queue-failer)
[![Latest Stable Version](https://poser.pugx.org/pmatseykanets/file-queue-failer/v/stable)](https://packagist.org/packages/pmatseykanets/file-queue-failer)
[![License](https://poser.pugx.org/pmatseykanets/file-queue-failer/license)](https://packagist.org/packages/pmatseykanets/file-queue-failer)

If you use job queues in your [Laravel](http://laravel.com) or [Lumen](http://lumen.laravel.com) project 
but don't want to store failed jobs in the database, especially if you're not using a database 
in the project itself (i.e. an API proxi) this file based failer is to rescue.

## Installation

### Install through composer

For Laravel 5.3+

```bash
$ composer require pmatseykanets/file-queue-failer
```

For Laravel < 5.3

```bash
$ composer require pmatseykanets/file-queue-failer:0.1.0
```


### Register the service provider

Open `config\app.php` in the editor of your choice and swap the `QueueServiceProvider` implementation 

```php
    'providers' => [
        ...
        Illuminate\Queue\QueueServiceProvider::class,
        ...
    ];
```

with

```php
    'providers' => [
        ...
        Pvm\FileQueueFailer\Queue\QueueServiceProvider::class,
        ...
    ];
```

### Set the path for the directory that will hold failed jobs

By default failed jobs will be stored in `storage\failed_jobs` directory.

You can change the location by changing the `path` property in `failed` section of `config\queue.php` config file.

```php
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


### License

The artisan-io is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)