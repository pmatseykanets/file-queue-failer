<?php

namespace Pvm\FileQueueFailer;

use Pvm\FileQueueFailer\Queue\FilesystemFailedJobProvider;

class FilesystemFailedJobProviderTest extends TestCase
{
    protected $path;

    public function setUp()
    {
        parent::setUp();

        $this->path = __DIR__.'/tmp';

        @mkdir($this->path);
    }

    public function tearDown()
    {
        parent::tearDown();

        @unlink("$this->path/failed.seq");
        @rmdir($this->path);
    }

    public function testCreateProvier()
    {
        $provider = $this->getProvider();
        $this->assertInstanceOf(FilesystemFailedJobProvider::class, $provider);
    }

    public function testItLogsFailedJob()
    {
        $provider = $this->getProvider();

        $timeStamp = date('YmdHis');
        $provider->log('foo', 'bar', json_encode(['job' => 'baz', 'data' => ['data']]));

        $file = "$this->path/foo/bar/1_$timeStamp";
        $this->assertFileExists($file);

        @unlink($file);
        @rmdir("$this->path/foo/bar");
        @rmdir("$this->path/foo");
    }

    public function testItReturnsAllJobs()
    {
        $jobs = $this->populateStorage();

        $provider = $this->getProvider();
        $all = $provider->all();

        $this->assertTrue(is_array($all));
        $this->assertCount(2, $all);
        $this->assertEquals($jobs, $all);

        $this->cleanStorage($jobs);
    }

    public function testItReturnsJobById()
    {
        $jobs = $this->populateStorage();

        $provider = $this->getProvider();
        $one = $provider->find(2);

        $this->assertTrue(is_array($one));
        $this->assertEquals($jobs[1], $one);

        $this->cleanStorage($jobs);
    }

    public function testItForgetsJobById()
    {
        $jobs = $this->populateStorage();

        $provider = $this->getProvider();
        $success = $provider->forget(2);

        $this->assertTrue($success);
        $this->assertFileNotExists($this->buildFilePathFromJob($jobs[1]));
        $this->assertFileExists($this->buildFilePathFromJob($jobs[0]));

        $this->cleanStorage($jobs);
    }

    public function testItFlushesAllJobs()
    {
        $jobs = $this->populateStorage();

        $provider = $this->getProvider();
        $provider->flush();

        $this->assertFileNotExists($this->buildFilePathFromJob($jobs[0]));
        $this->assertFileNotExists($this->buildFilePathFromJob($jobs[1]));

        $this->cleanStorage($jobs);
    }

    public function testItDoesntPickUpOtherFiles()
    {
        $jobs = $this->populateStorage();

        $file = $this->buildFilePathFromJob($jobs[0]) . 'ext';
        $file = dirname($file) . '/prefix' . basename($file) . 'ext';
        file_put_contents($file, 'data');

        $provider = $this->getProvider();
        $all = $provider->all();

        $this->assertCount(2, $all);
        $this->assertEquals($jobs, $all);

        @unlink($file);
        $this->cleanStorage($jobs);
    }

    /*
     *  Helper methods
     */

    private function getProvider()
    {
        return new FilesystemFailedJobProvider($this->path);
    }

    private function populateStorage()
    {
        $jobs = [
            [
                'id' => 1,
                'connection' => 'foo',
                'queue' => 'bar',
                'payload' => json_encode(['job' => 'job1', 'data' => ['data1']]),
                'failed_at' => '2015-08-01 12:30:00'
            ],
            [
                'id' => 2,
                'connection' => 'baz',
                'queue' => 'qux',
                'payload' => json_encode(['job' => 'job2', 'data' => ['data2']]),
                'failed_at' => '2015-08-02 22:55:00'
            ]
        ];

        foreach ($jobs as $job) {
            $this->createJob($job);
        }

        return $jobs;
    }

    private function cleanStorage($jobs)
    {
        foreach ($jobs as $job) {
            $this->removeJob($job);
        }

        foreach ($jobs as $job) {
            @rmdir("$this->path/{$job['connection']}/{$job['queue']}");
            @rmdir("$this->path/{$job['connection']}");
        }
    }

    private function buildFilePathFromJob($job)
    {
        $path = "$this->path/{$job['connection']}/{$job['queue']}";
        $basename = $job['id']. '_' . \DateTime::createFromFormat('Y-m-d H:i:s', $job['failed_at'])->format('YmdHis');

        return "$path/$basename";
    }

    private function createJob($job)
    {
        $file = $this->buildFilePathFromJob($job);
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, $job['payload']);
    }

    private function removeJob($job)
    {
        $file = $this->buildFilePathFromJob($job);
        @unlink($file);
    }
}