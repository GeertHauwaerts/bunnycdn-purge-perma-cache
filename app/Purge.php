<?php

namespace BunnyCDN\Storage\PermaCache;

use BunnyCDN\Storage\BunnyCDNStorage;
use BunnyCDN\Storage\Exceptions\BunnyCDNStorageAuthenticationException;
use BunnyCDN\Storage\Exceptions\BunnyCDNStorageException;

class Purge
{
    public $cfg;
    public $req;
    public $res;
    public $sig;

    private $bcdn;

    public $path = '/__bcdn_perma_cache__/';

    public function __construct($cfg = [])
    {
        $this->cfg = new Config($this, $cfg);
        $this->sig = new Signature($this);

        if (php_sapi_name() !== 'cli') {
            $this->res = new Response($this);
            $this->req = new Request($this);
            $this->process();
        }
    }

    private function process()
    {
        $this->checkAuth();
        $this->checkStorage();

        if (!$this->has($this->pcpath("{$this->path}/{$this->req->params->path}/"))) {
            $this->purged();
        }

        if (!$this->delete($this->pcpath("{$this->path}/{$this->req->params->path}/"))) {
            $this->res->error(
                "Failed to purge '{$this->req->params->path}' from '{$this->req->params->storagezone_name}'.",
                502
            );
        }

        $this->purged();
    }

    private function checkAuth()
    {
        $this->bcdn = new BunnyCDNStorage(
            $this->req->params->storagezone_name,
            $this->cfg->cfg['storage_zones'][$this->req->params->storagezone_name]['api_key'],
            $this->cfg->cfg['storage_zones'][$this->req->params->storagezone_name]['region']
        );

        try {
            $this->bcdn->getStorageObjects("/{$this->req->params->storagezone_name}/");
        } catch (BunnyCDNStorageAuthenticationException $e) {
            $this->res->error("Unable to authenticate the storage zone '{$this->req->params->storagezone_name}'.");
        }
    }

    private function checkStorage()
    {
        if (!$this->has($this->path)) {
            $this->res->error(
                "Unable to access the perma-cache storage repository in '{$this->req->params->storagezone_name}'."
            );
        }

        foreach ($this->get($this->path) as $f) {
            if (strpos($f->ObjectName, "pullzone__{$this->req->params->storagezone_name}__") !== false) {
                $this->path .= $f->ObjectName;
                return;
            }
        }

        $this->res->error(
            "Unable to access the perma-cache storage repository for '{$this->req->params->storagezone_name}'."
        );
    }

    private function has($path)
    {
        if (empty($this->bcdn->getStorageObjects("/{$this->req->params->storagezone_name}/{$path}"))) {
            return false;
        }

        return true;
    }

    private function get($path)
    {
        return $this->bcdn->getStorageObjects("/{$this->req->params->storagezone_name}/{$path}");
    }

    private function delete($path)
    {
        try {
            $this->bcdn->deleteObject("/{$this->req->params->storagezone_name}/{$path}/");
        } catch (BunnyCDNStorageException $e) {
            return false;
        }

        return true;
    }

    private function pcpath($path)
    {
        $path = rtrim($path, '/');
        $path = str_replace('//', '/', $path);
        $tmp = explode('/', $path);
        $file = array_pop($tmp);

        return implode('/', $tmp) . "/___{$file}___";
    }

    private function purged()
    {
        $uuid = $this->sig->uuid();

        $this->res->json([
            'storagezone_name' => $this->req->params->storagezone_name,
            'path' => $this->req->params->path,
            'object' => $this->pcpath("{$this->path}/{$this->req->params->path}/"),
            'uuid' => $uuid,
            'signature' => $this->sig->sign($uuid, $this->req->params->storagezone_name, $this->req->params->path),
        ]);
    }
}
