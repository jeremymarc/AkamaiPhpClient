# Akamai PHP Client

## Install
```php
composer install jeremymarc/akamai-php-client
```

## Usage
```php

$browser = new \Buzz\Browser(new \Buzz\Client\Curl());

$client = new Client($browser, $clientToken, $clientSecret, $accessToken, $baseUrl);
$resp = $client->checkQueueLength();
echo $resp->queueLength;
```

## Supported methods
- checkQueueLength()
- getPurgeStatus($id)
- purgeRequest($object)


## Akamai Documentation
https://api.ccu.akamai.com/ccu/v2/docs/index.html


## Reporting an issue or a feature request
Issues and feature requests are tracked in the [Github issue tracker](https://github.com/jeremymarc/AkamaiPhpClient/issues).

