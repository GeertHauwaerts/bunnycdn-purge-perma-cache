## About

This app is a REST API to purge files from the [BunnyCDN](https://bunnycdn.com) Perma-Cache.

## Installation

The installation is very easy and straightforward:

  * Create a `config.php` file with your settings.
  * Point Apache2 or NGINX to the `public/` directory.
  * Run `composer install` to install the dependencies.

```console
$ cp config.default.php config.php
$ composer install
```

## Development & Testing

To verify the integrity of the codebase you can run the PHP linter:

```console
$ composer install
$ composer phpcs
```

## API Usage

### Requests

All API requests must be sent:

* with `application/json` as `Content-Type`
* as a `POST` request with a `JSON` body

For example:

```bash
curl -d '{...}'  -H 'Content-Type: application/json' '...'
```

### Responses

All API responses will be sent:

* with `application/json` as `Content-Type`
* with `Access-Control-Allow-Origin` set to `*`

### Parameters

* `storagezone_name` - the name of the BunnyCDN *Storage Zone* and *Pull Zone*
* `path` - the path to purge
* `uuid` - a random string used to sign and verify the API signature
* `signature` the API request signature

Example:

```json
{
    "storagezone_name": "myzone",
    "path": "/kittens/meow.jpg",
    "uuid": "09d1e486-88e4-4c38-a7e0-e66eb8664a0e",
    "signature": "892b2c5c49a3d55e7b84178e5b4948ca9f4a177ccf4c7422afda3d2a6d4ae71b"
}
````

### Authentication & Signing

The signature is calculated with the following method:

```php
$signature = hash_hmac('sha256', "{$uuid}:{$storagezone_name}:{$path}", $app_signing_key);
```

## Library Usage

You can load the `BunnyCDN\Storage\PermaCache\Purge` class to create signed purge requests to submit to the REST API. For example:

```php
<?php

use BunnyCDN\Storage\PermaCache\Purge;

require __DIR__ . '/vendor/autoload.php';

$app = new Purge(require __DIR__ . '/config.php');
$uuid = $app->sig->uuid();

$res = json_encode([
    'storagezone_name' => 'myzone',
    'path' => '/kittens/meow.jpg',
    'uuid' => $uuid,
    'signature' => $app->sig->sign($uuid, 'myzone', '/kittens/meow.jpg'),
], JSON_UNESCAPED_SLASHES);

echo "curl -d '{$res}'  -H 'Content-Type: application/json' 'http://localhost:8000'\n";
```

## Configuration of the Stack

### BunnyCDN Configuration

* Create a *Storage Zone* with an **identical name** as the *Pull Zone*
* Go to the *Perma-Cache* in your *Pull Zone* settings
* Enable the *Perma-Cache* feature and link it to the *Storage Zone*

> Repeat these steps for each of the *Pull Zones* you want to deploy the *Perma-Cache* feature on.

BunnyCDN will now permanently store files fetched from the origin into the *Perma-Cache* located in the *Storage Zone*.

Use this REST API if you need to programatically purge files from the *Perma-Cache*, files will not be removed from the *Perma-Cache* when you issue purge requests through the BunnyCDN Dashboard or API.

### NGINX Sample Configuration

#### Sample Configuration Parameters

* Hostname: `purge-bcdn-pc.example.com`
* Root Path: `/opt/bunnycdn_purge_perma_cache`
* App Path: `${ROOT_PATH}/repo`

#### Configuration Snippet

```
server {
    listen 8080;
    server_name purge-bcdn-pc.example.com;
    server_tokens off;
    root /opt/bunnycdn_purge_perma_cache/repo/public;
    index index.php;
    location / {
        try_files $uri /index.php$is_args$args;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.2-fpm.sock;
    }
    location ~ /\.ht {
        deny all;
    }
}
```

## Collaboration

The GitHub repository is used to keep track of all the bugs and feature requests; I prefer to work exclusively via GitHib and Twitter.

If you have a patch to contribute:

  * Fork this repository on GitHub.
  * Create a feature branch for your set of patches.
  * Commit your changes to Git and push them to GitHub.
  * Submit a pull request.

Shout to [@GeertHauwaerts](https://twitter.com/GeertHauwaerts) on Twitter at any time :)

## Donations

If you like this project and you want to support the development, please consider to [donate](https://commerce.coinbase.com/checkout/45c6916d-19ae-40c9-8ef7-7fb7ad30f8e2); all donations are greatly appreciated.

* **[Coinbase Commerce](https://commerce.coinbase.com/checkout/45c6916d-19ae-40c9-8ef7-7fb7ad30f8e2)**: *BTC, BCH, DAI, ETH, LTC, USDC*
* **BTC**: *bc1q654z85zv6sujsjqk750sf4j4eahcckdtq0cqrp*
* **ETH**: *0x4d38b4EB5b0726Dc6bd5770F69348e7472954b41*
* **LTC**: *MBEaP6e4zwro6oNP54yjfC29fVqZ881wdF*
* **DOGE**: *D8LypNzP6GayEBWUKCw3KVc7gwbGBaXynT*
