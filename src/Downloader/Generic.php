<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;

class Generic implements Downloader
{
  private static $downloaders = [
    Ac::class,
    Bomtoon::class,
    Dongmanmanhua::class,
    Kuaikanmanhua::class,
    Manman::class,
    MrBlue::class,
  ];

  public function __toString(): string
  {
    switch (count(self::$downloaders)) {
      case 0:
        return 'Generic';
      case 1:
        return (string) reset(self::$downloaders);
      default:
        $downloaders = array_map('strval', self::$downloaders);
        $downloaders = array_map(function ($downloader) {
          return explode('\\', $downloader);
        }, $downloaders);
        $downloaders = array_map('end', $downloaders);
        $last = array_pop($downloaders);
        return implode(', ', $downloaders) . ' and ' . $last;
    }
  }

  private static function getDownloader(string $url): ?Downloader
  {
    $downloaders = array_filter(self::getDownloaders(), function ($downloader) use ($url) {
      return $downloader::match($url);
    });
    $downloader = reset($downloaders);
    return new $downloader ?: null;
  }

  public static function getDownloaders(): array
  {
    return self::$downloaders;
  }

  public static function match(string $url): bool
  {
    return !empty(self::getDownloader($url));
  }

  public static function files(ClientInterface $client, string $url): ?array
  {
    return self::getDownloader($url)->files($client, $url);
  }

  public static function middleware(string $url): callable
  {
    return self::getDownloader($url)->middleware($url);
  }
}
