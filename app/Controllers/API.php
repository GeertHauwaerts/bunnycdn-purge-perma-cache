<?php

namespace App\Controllers;

class API
{
    public $params;
    public $cfg;
    public $sig;
    public $cache;

    public function __construct($cfg = [])
    {
        $this->cfg = new Config($cfg);

        if (!$this->cfg->checkCfg()) {
            $this->error('Invalid configuration.');
        }

        $this->sig = new Signature($this);
        $this->cache = new Cache($this);

        $this->checkPostData();
        $this->checkSignature();
        $this->checkStorageZone();
        $this->process();
    }

    private function process()
    {
        $uuid = $this->sig->uuid();

        $data = [
            'storagezone_name' => $this->params->storagezone_name,
            'path' => $this->params->path,
            'object' => Purge::pcpath(Purge::API_PATH . "/{$this->params->path}/"),
            'uuid' => (string) $uuid,
            'signature' => $this->sig->sign($uuid, $this->params->storagezone_name, $this->params->path),
        ];

        $this->cache->rpush(Purge::QUEUE_NAME, $data);
        $this->json($data);
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
            $this->error('Unsupported request type.', 405);
        }

        if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
            $this->error('Unsupported content type.', 406);
        }

        $this->params = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== 0) {
            $this->error('Invalid JSON input.');
        }

        foreach ($required as $r) {
            if (!isset($this->params->$r)) {
                $this->error("Missing the query parameter '{$r}'.");
            }
        }
    }

    private function checkSignature()
    {
        if (!$this->sig->verify(
            $this->params->signature,
            $this->params->uuid,
            $this->params->storagezone_name,
            $this->params->path
        )) {
            $this->error('Invalid API signature.', 401);
        }
    }

    private function checkStorageZone()
    {
        if (!isset($this->cfg->cfg['storage_zones'][$this->params->storagezone_name])) {
            $this->error(
                "Missing the configuration for storage zone '{$this->params->storagezone_name}'."
            );
        }
    }

    public function error($msg, $code = 500)
    {
        error_log($msg);

        self::json([
            'error' => $msg,
        ], $code);
    }

    public function json($res = [], $code = 200)
    {
        if (empty($res)) {
            $code = 204;
        }

        header('Access-Control-Allow-Origin: *');
        header('Content-type: application/json');
        http_response_code($code);

        if (!empty($res)) {
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        exit();
    }
}
