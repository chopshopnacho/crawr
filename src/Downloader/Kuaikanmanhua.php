<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Kuaikanmanhua implements Downloader
{
  public static function match(string $url): bool
  {
    $url = parse_url($url);
    if ($url['host'] !== 'www.kuaikanmanhua.com') {
      return false;
    }
    return true;
  }

  public static function files(ClientInterface $client, string $url): ?array
  {
    $res = $client->request('GET', $url);
    if ($res->getStatusCode() !== 200) {
      return null;
    }
    $body = (string) $res->getBody();
    $found = preg_match_all('/url:"(http.+?\?sign.+?)"/', $body, $files);
    if (!$found) {
      return null;
    }
    return array_map(function ($file) {
      return json_decode('"' . $file . '"');
    }, $files[1]);
  }

  public static function middleware(string $url): callable
  {
    return function (callable $handler): callable {
      return function (RequestInterface $request, array $options) use ($handler) {
        return $handler($request, $options);
      };
    };
  }
}
