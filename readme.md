# File based implementation of Laravel Queue Failer

## Installation

### Install through composer

```bash
$ composer require pmatseykanets/file-queue-failer
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

You can change that by setting `path` property in `failed` section of `config\queue.php` config file.

```php
    'failed' => [
        'path' => 'storage/custom_path',
    ],
```

## Usage

You can use all artisan commands to manage failed jobs

```bash
 queue
  queue:failed        List all of the failed queue jobs
  queue:flush         Flush all of the failed queue jobs
  queue:forget        Delete a failed queue job
  queue:retry         Retry a failed queue job
```


### License

The artisan-io is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
