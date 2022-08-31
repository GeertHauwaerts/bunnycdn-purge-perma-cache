<?php

namespace App\Controllers;

class Config
{
    public $cfg;

    public function __construct($cfg = [])
    {
        $this->cfg = $cfg;
    }

    public function checkCfg(): bool
    {
        $required = [
            'app_signing_key' => 'string',
            'bunny_api_key' => 'string',
            'storage_zones' => 'array',
        ];

        $storage_zones = [
            'api_key' => 'string',
            'region' => 'string',
        ];

        foreach (array_keys($required) as $r) {
            if (!isset($this->cfg[$r])) {
                return false;
            }

            if (gettype($this->cfg[$r]) !== $required[$r]) {
                return false;
            }

            if ($r === 'storage_zones') {
                foreach ($this->cfg[$r] as $z => $d) {
                    foreach (array_keys($storage_zones) as $s) {
                        if (!isset($d[$s])) {
                            return false;
                        }

                        if (gettype($d[$s]) !== $storage_zones[$s]) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }
}
