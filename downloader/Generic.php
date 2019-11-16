<?php

declare(strict_types=1);

namespace Downloader;

use GuzzleHttp\ClientInterface;

class Generic implements Downloader
{
  private $downloaders = [];

  public function __construct()
  {
    $this->downloaders = [
      new Bomtoon,
      new Dongmanmanhua,
      new Kuaikanmanhua,
    ];
  }

  public function __toString(): string
  {
    switch (count($this->downloaders)) {
      case 0:
        return 'Generic';
      case 1:
        return (string) reset($this->downloaders);
      default:
        $head = $this->downloaders;
        $tail = array_pop($head);
        return implode(', ', $head) . ' and ' . $tail;
    }
  }

  private function getDownloader(string $url): ?Downloader
  {
    $downloaders = array_filter($this->getDownloaders(), function ($downloader) use ($url) {
      return $downloader->match($url);
    });
    return reset($downloaders) ?: null;
  }

  public function getDownloaders(): array
  {
    return $this->downloaders;
  }

  public function match(string $url): bool
  {
    return !empty($this->getDownloader($url));
  }

  public function files(ClientInterface $client, string $url): array
  {
    return $this->getDownloader($url)->files($client, $url);
  }

  public function middleware(string $url): callable
  {
    return $this->getDownloader($url)->middleware($url);
  }
}
