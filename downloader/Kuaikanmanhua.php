<?php

declare(strict_types=1);

namespace Downloader;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Kuaikanmanhua implements Downloader
{
  public function __toString(): string
  {
    return 'Kuaikanmanhua';
  }

  public function match(string $url): bool
  {
    $url = parse_url($url);
    if ($url['host'] !== 'www.kuaikanmanhua.com') {
      return false;
    }
    return true;
  }

  public function files(ClientInterface $client, string $url): array
  {
    $res = $client->get($url);
    if ($res->getStatusCode() !== 200) {
      return null;
    }
    $body = (string) $res->getBody();
    $found = preg_match_all('/url:"(http.+?\?sign.+?)"/', $body, $files);
    if (!$found) {
      return null;
    }
    $files = array_map(function ($file) {
      return json_decode('"' . $file . '"');
    }, $files[1]);
    return $files;
  }

  public function middleware(string $url): callable
  {
    return function (callable $handler): callable {
      return function (RequestInterface $request, array $options) use ($handler) {
        return $handler($request, $options);
      };
    };
  }
}
