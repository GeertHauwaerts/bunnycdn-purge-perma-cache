<?php

namespace App\Controllers;

use Predis\Client;

class Cache
{
    const RETRY_TIMEOUT = 10;

    private $client;

    public function __construct()
    {
        $cfg = new Config(require __DIR__ . '/../../config.php');
        $this->client = new Client($cfg->cfg['redis']);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function has($key): bool
    {
        return $this->client->exists(hash('md5', $key));
    }

    public function get($key, $default = null)
    {
        $res = $this->client->get(hash('md5', $key));

        if ($res === null) {
            return $default;
        }

        return unserialize($res);
    }

    public function set($key, $value, $ttl = 1800): bool
    {
        $this->client->set(hash('md5', $key), serialize($value));

        if ($ttl) {
            $this->client->expire(hash('md5', $key), $ttl);
        }

        return true;
    }

    public function delete($key): bool
    {
        $this->client->del(hash('md5', $key));
        return true;
    }

    public function rpush($key, $value): bool
    {
        return $this->client->rpush($key, serialize($value));
    }

    public function lpop($key, $default = null)
    {
        $res = $this->client->lpop($key);

        if ($res === null) {
            return $default;
        }

        return unserialize($res);
    }

    public function llen($key): int
    {
        return $this->client->llen($key);
    }

    public function ping(): bool
    {
        try {
            return $this->set('ping', time());
        } catch (\Predis\Connection\ConnectionException $e) {
            return false;
        } catch (\Predis\Response\ServerException $e) {
            return false;
        }

        return false;
    }
}
