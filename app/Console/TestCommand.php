<?php

namespace App\Console;

use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Input\Command;
use App\Controllers\Config;
use App\Controllers\Log;
use App\Controllers\Signature;
use Ramsey\Uuid\Uuid;
use Unirest\Request;
use Unirest\Request\Body;

class TestCommand extends Command
{
    public function __construct()
    {
        parent::__construct('test', 'API purge tests');
    }

    public function execute(): void
    {
        $this->cfg = new Config(require __DIR__ . '/../../config.php');
        $this->log = new Log($this);

        if (!$this->cfg->checkCfg()) {
            $this->log->error('Invalid configuration.');
        }

        if (!isset($this->cfg->cfg['test_command'])) {
            $this->log->comment('No tests are configured.');
        }

        $this->sig = new Signature($this);

        foreach ($this->cfg->cfg['test_command']['tests'] as $test) {
            foreach ($test as $zone => $path) {
                $uuid = Uuid::uuid4();

                $body = Body::json([
                    'storagezone_name' => $zone,
                    'path' => $path,
                    'uuid' => $uuid,
                    'signature' => hash_hmac('sha256', "{$uuid}:{$zone}:{$path}", $this->cfg->cfg['app_signing_key']),
                ]);

                $res = @Request::post($this->cfg->cfg['test_command']['service_url'], self::headers(), $body);

                if (isset($res->body) && isset($res->body->error)) {
                    $this->log->error($res->body->error);
                }

                $this->log->ok("Queued the purge for '{$path}' in '{$zone}'.");
            }
        }
    }

    private static function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
