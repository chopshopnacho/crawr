<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Toonkor implements Downloader
{
  public static function match(string $url): bool
  {
    return parse_url($url, PHP_URL_HOST) === 'toonkor.krd';
  }

  public static function files(ClientInterface $client, string $url): ?array
  {
    $res = $client->request('GET', $url);
    if ($res->getStatusCode() !== 200) {
      return null;
    }
    $body = (string) $res->getBody();
    if (!preg_match('/var toon_img = \'([a-z0-9+\/=]+)\';/i', $body, $html)) {
      return null;
    }
    $html = base64_decode($html[1]);
    if (!preg_match_all('/src="([^"]+)"/i', $html, $urls)) {
      return null;
    }
    return array_map(function ($url) {
      return 'https://toonkor.krd/' . $url;
    }, $urls[1]);
  }

  public static function middleware(string $url): callable
  {
    return function (callable $handler): callable {
      return function (RequestInterface $req, array $options) use ($handler) {
        return $handler($req, $options);
      };
    };
  }
}
