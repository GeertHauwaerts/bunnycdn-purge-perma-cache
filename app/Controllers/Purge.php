<?php

namespace App\Controllers;

use BunnyCDN\Storage\BunnyCDNStorage;
use BunnyCDN\Storage\Exceptions\BunnyCDNStorageAuthenticationException;
use BunnyCDN\Storage\Exceptions\BunnyCDNStorageException;
use GuzzleHttp\Client;

class Purge
{
    private $app;
    private $bcdn;
    private $data;
    private $path;
    private $client;

    const API_URL = 'https://bunnycdn.com/api';
    const API_PATH = '/__bcdn_perma_cache__/';
    const API_TIMEOUT = 30;

    const QUEUE_NAME = 'BPPC';

    public function __construct($app, $data)
    {
        $this->app = $app;
        $this->path = self::API_PATH;
        $this->data = $data;
        $this->client = new Client();
    }

    public function process()
    {
        $this->app->log->comment("[{$this->data['uuid']}] Purging '{$this->data['path']}' in '{$this->data['storagezone_name']}'.");

        if (!$this->checkAuth() || !$this->checkStorage()) {
            return;
        }

        if (!$this->has(self::pcpath("{$this->path}/{$this->data['path']}/"))) {
            $this->purge();
            return;
        }

        if (!$this->delete(self::pcpath("{$this->path}/{$this->data['path']}/"))) {
            $this->app->log->error("[{$this->data['uuid']}] Failed to purge '{$this->data['path']}' from '{$this->data['storagezone_name']}'.");
        }

        $this->purge();
    }

    private function checkAuth(): bool
    {
        $this->bcdn = new BunnyCDNStorage(
            $this->data['storagezone_name'],
            $this->app->cfg->cfg['storage_zones'][$this->data['storagezone_name']]['api_key'],
            $this->app->cfg->cfg['storage_zones'][$this->data['storagezone_name']]['region']
        );

        try {
            $this->bcdn->getStorageObjects("/{$this->data['storagezone_name']}/");
        } catch (BunnyCDNStorageAuthenticationException $e) {
            $this->app->log->error("[{$this->data['uuid']}] Unable to authenticate the storage zone '{$this->data['storagezone_name']}'.");
            return false;
        }

        return true;
    }

    private function checkStorage(): bool
    {
        if (!$this->has($this->path)) {
            $this->app->log->error("[{$this->data['uuid']}] Unable to access the perma-cache storage repository in '{$this->data['storagezone_name']}'.");
            return false;
        }

        foreach ($this->get($this->path) as $f) {
            if (strpos($f->ObjectName, "pullzone__{$this->data['storagezone_name']}__") !== false) {
                $this->path .= $f->ObjectName;
                return true;
            }
        }

        $this->app->log->error("[{$this->data['uuid']}] Unable to access the perma-cache storage repository for '{$this->data['storagezone_name']}'.");
        return false;
    }

    private function has($path)
    {
        if (empty($this->bcdn->getStorageObjects("/{$this->data['storagezone_name']}/{$path}"))) {
            return false;
        }

        return true;
    }

    private function get($path)
    {
        return $this->bcdn->getStorageObjects("/{$this->data['storagezone_name']}/{$path}");
    }

    private function delete($path)
    {
        try {
            $this->bcdn->deleteObject("/{$this->data['storagezone_name']}/{$path}/");
        } catch (BunnyCDNStorageException $e) {
            return false;
        }

        return true;
    }

    private function cdnpurge(): bool
    {
        $path = ltrim($this->data['path'], '/');

        $urls = [
            "https://{$this->data['storagezone_name']}.b-cdn.net/{$path}",
            "https://{$this->data['storagezone_name']}.b-cdn.net/{$path}*",
        ];

        foreach ($urls as $u) {
            $this->app->log->comment("[{$this->data['uuid']}] Purging '{$u}' from the CDN.");

            try {
                $res = $this->client->post(
                    self::API_URL . "/purge?url={$u}",
                    [
                        'allow_redirects' => false,
                        'headers' => $this->headers(),
                    ]
                );
            } catch (\Exception $e) {
                $this->app->log->error("[{$this->data['uuid']}] Failed to purge '{$u}' from the CDN.");
                return false;
            }

            $this->app->log->ok("[{$this->data['uuid']}] Purged '{$u}' from the CDN.");
        }

        return true;
    }

    public static function pcpath($path)
    {
        $path = rtrim($path, '/');
        $path = str_replace('//', '/', $path);
        $tmp = explode('/', $path);
        $file = array_pop($tmp);

        return implode('/', $tmp) . "/___{$file}___";
    }

    private function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'AccessKey' => $this->app->cfg->cfg['bunny_api_key'],
        ];
    }

    private function purge()
    {
        if (!$this->cdnpurge()) {
            $this->app->log->error("[{$this->data['uuid']}] Failed to purge '{$this->data['path']}' in '{$this->data['storagezone_name']}'.");
            return;
        }

        $this->app->log->ok("[{$this->data['uuid']}] Purged '{$this->data['path']}' in '{$this->data['storagezone_name']}'.");
    }
}
