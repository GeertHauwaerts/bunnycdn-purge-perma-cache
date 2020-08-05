#!/bin/bash

if [ -d "vendor" ]; then
  composer update
else
  composer install
fi

composer run start
