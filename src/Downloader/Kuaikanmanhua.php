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

  /**
   * Takes a letter from Kuaikans alphabet and returns its index. The alphabet
   * goes from a-z, A-Z and _ to $. It repeats with the next character
   * prepended (eg. aa to $$).
   * Note that this function only supports up to two letters.
   */
  private static function alphabetIndex(string $letters): int
  {
    $alphabet = [
      ...range('a', 'z'),
      ...range('A', 'Z'),
      '_', '$',
    ];
    if (strlen($letters) < 2) {
      $letters = substr('  ' . $letters, -2);
    }
    $index = array_search($letters[1], $alphabet);
    if (array_search($letters[0], $alphabet) !== false) {
      $index += (array_search($letters[0], $alphabet) + 1) * count($alphabet);
    }
    return $index;
  }

  public static function files(ClientInterface $client, string $url): ?array
  {
    $res = $client->request('GET', $url);
    if ($res->getStatusCode() !== 200) {
      return null;
    }
    $body = (string) $res->getBody();

    // Get the JavaScript element.
    $found = preg_match('/<script>window.__NUXT__=(.+?)<\/script>/', $body, $javascript);
    if (!$found) {
      return null;
    }
    $javascript = $javascript[1];

    // Get the image order.
    $found = preg_match('/comic_images:\[(.+?)\],/', $javascript, $images);
    if (!$found) {
      return null;
    }
    $found = preg_match_all('/url:([a-zA-Z_$]+)/', $images[1], $image_letters);
    if (!$found) {
      return null;
    }
    $image_letters = $image_letters[1];

    // Get the arguments.
    $found = preg_match('/}\((.+?)\)\);?$/', $javascript, $arguments);
    if (!$found) {
      return null;
    }
    $arguments = explode(',', $arguments[1]);

    // Get the arguments in image order.
    $image_urls = array_map(function ($image_letter) use ($arguments) {
      return $arguments[self::alphabetIndex($image_letter)];
    }, $image_letters);
    $image_urls = array_map('json_decode', $image_urls);

    return $image_urls;
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
