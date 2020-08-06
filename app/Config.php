<?php

namespace BunnyCDN\Storage\PermaCache;

class Config
{
    public $cfg;

    private $app;

    public function __construct(Purge $app, $cfg = [])
    {
        $this->app = $app;
        $this->cfg = $cfg;
        $this->checkCfg();
    }

    private function checkCfg()
    {
        $required = [
            'app_signing_key' => 'string',
            'storage_zones' => 'array',
        ];

        $storage_zones = [
            'api_key' => 'string',
            'region' => 'string',
        ];

        foreach (array_keys($required) as $r) {
            if (!isset($this->cfg[$r])) {
                $this->app->res->error("Missing the configuration setting '{$r}'.");
            }

            if (gettype($this->cfg[$r]) !== $required[$r]) {
                $this->app->res->error("Invalid type for the configuration setting '{$r}'.");
            }

            if ($r === 'storage_zones') {
                foreach ($this->cfg[$r] as $z => $d) {
                    foreach (array_keys($storage_zones) as $s) {
                        if (!isset($d[$s])) {
                            $this->app->res->error(
                                "Missing the configuration setting '{$s}' in pull zone '{$z}'."
                            );
                        }

                        if (gettype($d[$s]) !== $storage_zones[$s]) {
                            $this->app->res->error(
                                "Invalid type for the configuration setting '{$s}' in pull zone '{$z}'."
                            );
                        }
                    }
                }
            }
        }
    }
}
