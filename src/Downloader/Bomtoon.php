<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;
use Psr\Http\Message\RequestInterface;

class Bomtoon implements Downloader
{
  public static function match(string $url): bool
  {
    $url = parse_url($url);
    if ($url['host'] !== 'www.bomtoon.com') {
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
    $dom = new Dom;
    $dom->load((string) $res->getBody());
    $images = $dom->find('#bt-webtoon-image .document_img')->toArray();
    $images = array_filter($images, function (HtmlNode $image) {
      return $image->hasAttribute('data-img-ele');
    });
    $images = array_map(function (HtmlNode $image) {
      return $image->getAttribute('data-img-ele');
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
