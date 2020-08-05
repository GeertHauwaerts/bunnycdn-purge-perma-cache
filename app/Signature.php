<?php

namespace BunnyCDN\Storage\PermaCache;

use Ramsey\Uuid\Uuid;

class Signature
{
    private $app;

    public function __construct(Purge $app)
    {
        $this->app = $app;
    }

    public function sign($uuid, $zone, $path)
    {
        return hash_hmac('sha256', "{$uuid}:{$zone}:{$path}", $this->app->cfg->cfg['app_signing_key']);
    }

    public function verify($signature, $uuid, $zone, $path)
    {
        if ($signature === $this->sign($uuid, $zone, $path)) {
            return true;
        }

        return false;
    }

    public function uuid()
    {
        return Uuid::uuid4();
    }
}
