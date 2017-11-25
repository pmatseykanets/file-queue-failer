<?php

namespace Pvm\FileQueueFailer\Queue;

use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Get a default implementation to trigger a deferred binding
        $_ = $this->app['queue.failer'];

        // Swap the implementation
        $this->app->singleton('queue.failer', function ($app) {
            $config = $app['config']['queue.failed'];

            return new FilesystemFailedJobProvider(
                isset($config['path']) ? $config['path'] : storage_path('failed_jobs')
            );
        });
    }
}
