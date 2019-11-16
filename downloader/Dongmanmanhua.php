<?php

declare(strict_types=1);

namespace Downloader;

use GuzzleHttp\ClientInterface;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;
use Psr\Http\Message\RequestInterface;

class Dongmanmanhua implements Downloader
{
  public function __toString(): string
  {
    return 'Dongmanmanhua';
  }

  public function match(string $url): bool
  {
    $url = parse_url($url);
    if ($url['host'] !== 'www.dongmanmanhua.cn') {
      return false;
    }
    $params = [];
    parse_str($url['query'], $params);
    if (!isset($params['title_no']) || !isset($params['episode_no'])) {
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
    $dom = new Dom;
    $dom->load((string) $res->getBody());
    $images = $dom->find('img._images')->toArray();
    $images = array_filter($images, function (HtmlNode $image) {
      return $image->hasAttribute('data-url');
    });
    $images = array_map(function (HtmlNode $image) {
      return $image->getAttribute('data-url');
    }, $images);
    return $images;
  }

  public function middleware(string $url): callable
  {
    return function (callable $handler): callable {
      return function (RequestInterface $req, array $options) use ($handler) {
        $req = $req->withHeader('Referer', 'https://www.dongmanmanhua.cn/');
        return $handler($req, $options);
      };
    };
  }
}
