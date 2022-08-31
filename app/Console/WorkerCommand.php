<?php

namespace App\Console;

use Ahc\Cli\Input\Command;
use App\Controllers\Cache;
use App\Controllers\Config;
use App\Controllers\Log;
use App\Controllers\Purge;
use App\Controllers\Worker;
use App\Helpers\StatusMessage;
use QXS\WorkerPool\WorkerPool;

class WorkerCommand extends Command
{
    public $cache;
    public $cfg;
    public $log;

    private $workers;

    private $ratio = 0.8;
    private $poller = 0.25;

    public function __construct()
    {
        parent::__construct('worker', 'Process the worker queue');
        $this->setWorkers();
    }

    public function execute(): void
    {
        $this->cfg = new Config(require __DIR__ . '/../../config.php');
        $this->log = new Log($this);
        $this->cache = new Cache();

        $this->process();
    }

    private function process(): void
    {
        $this->log->ok("Spawning {$this->workers} workers...");

        $wp = new WorkerPool();

        $wp->setWorkerPoolSize($this->workers)
            ->respawnAutomatically()
            ->create(new Worker($this));

        while (true) {
            if (StatusMessage::expired()) {
                StatusMessage::touch();
                $pending = $this->cache->llen(Purge::QUEUE_NAME);
                $this->log->warn("There are {$pending} pending purges.");
            }

            if ($wp->getFreeWorkers() !== 0) {
                $wp->run(null);
            }

            usleep($this->poller * 1000 * 1000);
        }

        $wp->waitForAllWorkers();
    }

    private function setWorkers()
    {
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        $this->workers = ceil(count($matches[0]) * $this->ratio);
    }
}
