<?php

namespace Pvm\FileQueueFailer\Queue;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class FilesystemFailedJobProvider implements FailedJobProviderInterface
{
    /** @var  Filesystem */
    protected $filesystem;

    protected $path;

    protected $sequenseFile = 'failed.seq';

    public function __construct($path, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        if (! $this->filesystem->exists($path)) {
            throw new \RuntimeException("Path '$path' doesn't exist.");
        }

        $this->path = realpath($path); var_dump($this->path);

    }

    /**
     * Log a failed job into storage.
     *
     * @param  string $connection
     * @param  string $queue
     * @param  string $payload
     * @return void
     */
    public function log($connection, $queue, $payload)
    {
        $path = "$this->path/$connection/$queue";

        $this->makePath($path);

        $filename = $this->getNewId() . '_' . date('YmdHis');

        $this->filesystem->put("$path\/$filename", $payload);
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        return $this->getAllJobs();
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed $id
     * @return array
     */
    public function find($id)
    {
        return $this->getJob((int) $id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed $id
     * @return bool
     */
    public function forget($id)
    {
        $this->deleteJob((int) $id);
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush()
    {
        $this->deleteAllJobs();
    }

    /**
     * Returns a new id
     *
     * @return int
     */
    protected function getNewId()
    {
        $f = $this->getSequenceFilename();

        file_put_contents($f, $newId = (int) @file_get_contents($f) + 1, LOCK_EX);

        return $newId;
    }

    /**
     * Returns the absolute path to the sequence file
     *
     * @return string
     */
    private function getSequenceFilename()
    {
        return "$this->path\/$this->sequenseFile";
    }

    /**
     * Traverses failed jobs storage and execute a callable
     * for each entry (file)
     *
     * @param \Closure $callable
     */
    protected function traverseStorage(\Closure $callable)
    {
        foreach ($this->filesystem->directories($this->path) as $connection) { var_dump($connection);
            foreach ($this->filesystem->directories($connection) as $queue) { var_dump($queue);
                foreach ($this->filesystem->files($queue) as $job) { var_dump($job);
                    if (! $callable($job, $connection, $queue)) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Returns all failed jobs
     *
     * @return array
     */
    protected function getAllJobs()
    {
        $all = [];

        $this->traverseStorage(function($job, $connection, $queue) use (&$all) {
            $all[] = $this->getJobFromFile($job, $connection, $queue);
            return true;
        });

        return $all;
    }

    /**
     * Returns a failed job given the id
     *
     * @param $jobId
     * @return null
     */
    protected function getJob($jobId)
    {
        $job = null;

        $this->traverseStorage(function($filename, $connection, $queue) use ($jobId, &$job) {
            list($id, $ts) = explode('_', basename($filename));

            if ($jobId == (int) $id) {
                $job = $this->getJobFromFile($filename, $connection, $queue);
                return false;
            }

            return true;
        });

        return $job;
    }

    /**
     * Delete a failed job given the id
     *
     * @param $jobId
     * @return bool
     */
    protected function deleteJob($jobId)
    {
        $success = false;

        $this->traverseStorage(function($filename, $connection, $queue) use ($jobId, &$success) {
            list($id, $ts) = explode('_', basename($filename));

            if ($jobId == (int) $id) {
                $success = $this->filesystem->delete($filename);
                return false;
            }

            return true;
        });

        return $success;
    }

    /**
     * Delete all failed jobs
     */
    protected function deleteAllJobs()
    {
        $this->traverseStorage(function($filename, $connection, $queue) {
            $this->filesystem->delete($filename);
            return true;
        });
    }

    /**
     * Retrieve a failed job from the file
     *
     * @param $filename
     * @param $connection
     * @param $queue
     * @return array
     */
    protected function getJobFromFile($filename, $connection, $queue)
    {
        list($id, $ts) = explode('_', basename($filename));

        return [
            'id' => (int)$id,
            'connection' => basename($connection),
            'queue' => basename($queue),
            'payload' => file_get_contents($filename),
            'failed_at' => \DateTime::createFromFormat('YmdHis', $ts)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create a path if it doesn't exist
     *
     * @param $path
     */
    protected function makePath($path)
    {
        if (! $this->filesystem->exists($path)) {
            $this->filesystem->makeDirectory($path, 0777, true);
        }
    }
}