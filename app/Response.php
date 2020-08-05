<?php

namespace BunnyCDN\Storage\PermaCache;

class Response
{
    private $app;

    public function __construct(Purge $app)
    {
        $this->app = $app;
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
