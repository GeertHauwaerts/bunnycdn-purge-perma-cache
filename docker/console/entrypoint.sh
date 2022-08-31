#!/bin/bash

if [ ! -f "config.php" ]; then
  cp config.php.default config.php
fi

if [ -d "vendor" ]; then
  composer update
else
  composer install
fi

until nc -z -v -w 30 bppc-redis 6379; do
  echo "Waiting for Redis..."
  sleep 1
done

tail -f /dev/null
