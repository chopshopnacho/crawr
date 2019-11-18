<?php

declare(strict_types=1);

namespace Downloader;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class MrBlue implements Downloader
{
  private static $regex = '/^https?:\/\/www\.mrblue\.com\/viewer\/view\/(.+?)\/([0-9]+)/';

  public static function match(string $url): bool
  {
    return preg_match(self::$regex, $url) !== false;
  }

  /**
   * Decrypt (or "shuffle") "encrypted" data found in MrBlues frontend. This
   * usually contains a Base64 encoded JSON object describing the manhwa and
   * its chapters. Algorithm and key can be found in JavaScript.
   *
   * TODO: The output might be at most strlen($input)-1 characters smaller than
   * the input. We need to remove the added garbage data to successfully decode
   * the underlying JSON object.
   *
   * @param string $input Encrypted input
   * @return string Decrypted output.
   */
  private static function shuffle(string $input): string
  {
    $output = $input;
    $key = [3, 6, 5, 7, 0, 2, 4, 1];
    for ($i = 0; $i < strlen($input); $i++) {
      $j = floor($i / count($key)) * count($key);
      $j += $key[$i % count($key)];
      $output[$j] = $input[$i];
    }
    return $output;
  }

  /**
   * Return a list of image URLs of the given volume.
   *
   * @param object $json Object describing the manhwa. @see shuffle
   * @param int $volume Volume number.
   * @return ?array Image URLs.
   */
  private static function images(object $json, int $volume): ?array
  {
    $chapter = array_filter($json->volumeInfo, function ($chapter) use ($volume) {
      return $chapter->volume === $volume;
    });
    if (empty($chapter)) {
      return null;
    }
    $chapter = reset($chapter);
    $pages = range($json->productInfo->firstPage, $chapter->pageCount);
    $pages = array_map(function ($page) use ($json, $volume) {
      return vsprintf('https://comics%s-c.mrblue.com/MrBlueComicsData_%s/%s/%s/%d/%03d.jpg', [
        $json->productInfo->hasHD ? 'hd' : '',
        $json->productInfo->hdd,
        $json->productInfo->dir,
        $json->pid,
        $volume,
        $page,
      ]);
    }, $pages);
    return $pages;
  }

  public static function files(ClientInterface $client, string $url): ?array
  {
    $found = preg_match(self::$regex, $url, $chapter);
    if (!$found) {
      return null;
    }
    list(, $pid,  $chapter) = $chapter;

    $res = $client->get($url);
    if ($res->getStatusCode() !== 200) {
      return null;
    }
    $body = (string) $res->getBody();
    $found = preg_match('/wv\.init\\(\'(.+?)\'\\)/', $body, $cyphertext);
    if (!$found) {
      return null;
    }
    $cyphertext = end($cyphertext);
    $plaintext = self::shuffle($cyphertext);
    $json = base64_decode($plaintext, true);
    if ($json === false) {
      return null;
    }
    $body = json_decode($json);
    if ($body === null) {
      return null;
    }
    return self::images($body, (int) $chapter);
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
