<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;
use Psr\Http\Message\RequestInterface;

class Dongmanmanhua implements Downloader
{
  public static function match(string $url): bool
  {
    $url = parse_url($url);
    if ($url['host'] !== 'www.dongmanmanhua.cn') {
      return false;
    }
    parse_str($url['query'], $params);
    if (!isset($params['title_no']) || !isset($params['episode_no'])) {
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
    $images = $dom->find('img._images')->toArray();
    $images = array_filter($images, function (HtmlNode $image) {
      return $image->hasAttribute('data-url');
    });
    $images = array_map(function (HtmlNode $image) {
      return $image->getAttribute('data-url');
    }, $images);
    return $images;
  }

  public static function middleware(string $url): callable
  {
    return function (callable $handler): callable {
      return function (RequestInterface $req, array $options) use ($handler) {
        $req = $req->withHeader('Referer', 'https://www.dongmanmanhua.cn/');
        return $handler($req, $options);
      };
    };
  }
}
