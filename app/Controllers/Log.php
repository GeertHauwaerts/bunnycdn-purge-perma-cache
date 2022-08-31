<?php

namespace App\Controllers;

class Log
{
    private $app;
    private $io;

    const LOG_DIR = __DIR__ . '/../../logs';

    public function __construct($app)
    {
        $this->app = $app;
        $this->io = $app->app()->io();
    }

    public function ok($msg, $type = 'app'): void
    {
        $msg = $this->format($msg);

        $this->io->ok($msg, true);
        $this->file($type, $msg);
    }

    public function comment($msg, $type = 'app'): void
    {
        $msg = $this->format($msg);

        $this->io->comment($msg, true);
        $this->file($type, $msg);
    }

    public function warn($msg, $type = 'app'): void
    {
        $msg = $this->format($msg);

        $this->io->warn($msg, true);
        $this->file($type, $msg);
    }

    public function error($msg, $type = 'app'): void
    {
        $msg = $this->format($msg);

        $this->io->error($msg, true);
        $this->file($type, $msg);
    }

    private function format($msg)
    {
        return '[' . date('Y-m-d H:i:s') . "] {$msg}";
    }

    private function file($file, $msg)
    {
        $file = date('Ymd') . "_{$file}";
        file_put_contents(self::LOG_DIR . "/{$file}.log", "{$msg}\n", FILE_APPEND);
    }
}
