<?php

namespace Pvm\FileQueueFailer\Queue;

use Illuminate\Queue\Failed\FailedJobProviderInterface;

class FilesystemFailedJobProvider implements FailedJobProviderInterface
{
    protected $path;

    protected $sequence = 'failed.seq';

    public function __construct($path)
    {
        $this->makePath($path);

        $this->path = realpath($path);
    }

    /**
     * Log a failed job into storage.
     *
     * @param string     $connection
     * @param string     $queue
     * @param string     $payload
     * @param \Exception $exception
     *
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $path = "$this->path/$connection/$queue";

        $this->makePath($path);

        $id = $this->getNewId();

        $filename = $id.'_'.date('YmdHis');

        file_put_contents("$path/$filename", $payload);

        return $id;
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
     * @param mixed $id
     *
     * @return array
     */
    public function find($id)
    {
        return $this->getJob((int) $id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function forget($id)
    {
        return $this->deleteJob((int) $id);
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
     * Returns a new id.
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
     * Returns the absolute path to the sequence file.
     *
     * @return string
     */
    private function getSequenceFilename()
    {
        return "$this->path/$this->sequence";
    }

    /**
     * Traverses failed jobs storage and execute a callable
     * for each entry (file).
     *
     * @param \Closure $callable
     */
    protected function traverseStorage(\Closure $callable)
    {
        foreach ($this->directories($this->path) as $connection) {
            foreach ($this->directories($connection) as $queue) {
                foreach ($this->files($queue) as $job) {
                    if (!$callable($job, $connection, $queue)) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Returns all failed jobs.
     *
     * @return array
     */
    protected function getAllJobs()
    {
        $all = [];

        $this->traverseStorage(function ($job, $connection, $queue) use (&$all) {
            $job = $this->getJobFromFile($job, $connection, $queue);
            $all[$job['id']] = $job;

            return true;
        });

        ksort($all);

        return array_values($all);
    }

    /**
     * Returns a failed job given the id.
     *
     * @param $jobId
     *
     * @return null
     */
    protected function getJob($jobId)
    {
        $job = null;

        $this->traverseStorage(function ($filename, $connection, $queue) use ($jobId, &$job) {
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
     * Delete a failed job given the id.
     *
     * @param $jobId
     *
     * @return bool
     */
    protected function deleteJob($jobId)
    {
        $success = false;

        $this->traverseStorage(function ($filename, $connection, $queue) use ($jobId, &$success) {
            list($id, $ts) = explode('_', basename($filename));

            if ($jobId == (int) $id) {
                $success = @unlink($filename);

                return false;
            }

            return true;
        });

        return $success;
    }

    /**
     * Delete all failed jobs.
     */
    protected function deleteAllJobs()
    {
        $this->traverseStorage(function ($filename, $connection, $queue) {
            @unlink($filename);

            return true;
        });
    }

    /**
     * Retrieve a failed job from the file.
     *
     * @param $filename
     * @param $connection
     * @param $queue
     *
     * @return array
     */
    protected function getJobFromFile($filename, $connection, $queue)
    {
        list($id, $ts) = explode('_', basename($filename));

        return [
            'id'         => (int) $id,
            'connection' => basename($connection),
            'queue'      => basename($queue),
            'payload'    => file_get_contents($filename),
            'failed_at'  => \DateTime::createFromFormat('YmdHis', $ts)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create a path if it doesn't exist.
     *
     * @param $path
     */
    protected function makePath($path)
    {
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }
    }

    protected function files($path)
    {
        $files = [];

        foreach (new \DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isFile() && preg_match('/^\d{1,}_\d{14}$/', $fileInfo->getBasename())) {
                $files[] = $fileInfo->getRealPath();
            }
        }

        return $files;
    }

    protected function directories($path)
    {
        $directories = [];

        foreach (new \DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $directories[] = $fileInfo->getRealPath();
            }
        }

        return $directories;
    }
}
