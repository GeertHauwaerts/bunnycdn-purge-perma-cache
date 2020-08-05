<?php

namespace BunnyCDN\Storage\PermaCache;

class Request
{
    public $params;

    private $app;

    public function __construct(Purge $app)
    {
        $this->app = $app;

        $this->checkPostData();
        $this->checkSignature();
        $this->checkStorageZone();
    }

    private function checkPostData()
    {
        $required = [
            'storagezone_name',
            'path',
            'uuid',
            'signature',
        ];

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->app->res->error('Unsupported request type.', 405);
        }

        if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
            $this->app->res->error('Unsupported content type.', 406);
        }

        $this->params = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== 0) {
            $this->app->res->error('Invalid JSON input.');
        }

        foreach ($required as $r) {
            if (!isset($this->params->$r)) {
                $this->app->res->error("Missing the query parameter '{$r}'.");
            }
        }
    }

    private function checkSignature()
    {
        if (!$this->app->sig->verify(
            $this->params->signature,
            $this->params->uuid,
            $this->params->storagezone_name,
            $this->params->path
        )) {
            $this->app->res->error('Invalid API signature.', 401);
        }
    }

    private function checkStorageZone()
    {
        if (!isset($this->app->cfg->cfg['storage_zones'][$this->params->storagezone_name])) {
            $this->app->res->error(
                "Missing the configuration for storage zone '{$this->params->storagezone_name}'."
            );
        }
    }
}
