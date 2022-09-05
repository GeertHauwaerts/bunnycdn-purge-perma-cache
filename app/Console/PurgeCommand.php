<?php

namespace App\Console;

use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Input\Command;
use App\Controllers\Cache;
use App\Controllers\Config;
use App\Controllers\Log;
use App\Controllers\Purge;
use App\Controllers\Signature;
use Ramsey\Uuid\Uuid;

class PurgeCommand extends Command
{
    private $cache;
    private $log;
    private $sig;

    public $cfg;

    public function __construct()
    {
        parent::__construct('purge', 'Purge an URI');
        $this->argument('<storagezone>', 'The URI to purge');
        $this->argument('<uri>', 'The URI to purge');
    }

    public function execute(): void
    {
        $this->cfg = new Config(require __DIR__ . '/../../config.php');
        $this->log = new Log($this);
        $this->cache = new Cache();
        $this->sig = new Signature($this);

        if (!$this->cfg->checkCfg()) {
            $this->log->error('Invalid configuration.');
            return;
        }

        if (!isset($this->cfg->cfg['storage_zones'][$this->storagezone])) {
            $this->log->error('Invalid storage zone.');
            return;
        }

        if (!filter_var($this->uri, FILTER_VALIDATE_URL)) {
            $this->log->error('Invalid URI.');
            return;
        }

        $path = ltrim(parse_url($this->uri, PHP_URL_PATH), '/');

        if (!$path) {
            $this->log->error('Invalid path in the URI.');
            return;
        }

        $uuid = Uuid::uuid4();

        $data = [
            'storagezone_name' => $this->storagezone,
            'path' => $path,
            'object' => Purge::pcpath(Purge::API_PATH . "/{$path}/"),
            'uuid' => (string) $uuid,
            'signature' => $this->sig->sign($uuid, $this->storagezone, $path),
        ];

        $this->cache->rpush(Purge::QUEUE_NAME, $data);
        $this->log->ok("Queued the purge for '{$path}' in '{$this->storagezone}'.");
    }

    private static function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
