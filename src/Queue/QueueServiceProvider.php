<?php

namespace Pvm\FileQueueFailer\Queue;

use Pvm\FileQueueFailer\Queue\FilesystemFailedJobProvider;
use Illuminate\Queue\QueueServiceProvider as OriginalQueueServiceProvider;

class QueueServiceProvider extends OriginalQueueServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->app->singleton('queue.failer', function ($app) {
            $config = $app['config']['queue.failed'];

            return new FilesystemFailedJobProvider(
                isset($config['path']) ? $config['path'] : storage_path('failed_jobs'),
                $app['files']
            );
        });
    }
}
