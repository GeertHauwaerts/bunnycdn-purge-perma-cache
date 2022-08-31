<?php

namespace App\Controllers;

use QXS\WorkerPool\Semaphore;
use QXS\WorkerPool\WorkerInterface;

class Worker implements WorkerInterface
{
    protected $sem;

    public $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function onProcessCreate(Semaphore $semaphore)
    {
        $this->sem = $semaphore;
    }

    public function onProcessDestroy()
    {
    }

    public function run($input): bool
    {
        while (true) {
            $purge = $this->app->cache->lpop(Purge::QUEUE_NAME);

            if (!$purge) {
                sleep (5);
                continue;
            }

            (new Purge($this->app, $purge))->process();
            sleep(1);
        }
    }
}
