<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;
use Psr\Http\Message\RequestInterface;

class Manman implements Downloader
{
  public static function match(string $url): bool
  {
    $url = parse_url($url);
    $domains = ['m.manmanapp.com', 'www.manmanapp.com'];
    if (!in_array($url['host'], $domains)) {
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
    $dom = (new Dom)->load((string) $res->getBody());
    $images = $dom->find('.cartoon img')->toArray();
    $images = array_map(function (HtmlNode $image) {
      return $image->getAttribute('src');
    }, $images);
    return $images;
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
