<?php

declare(strict_types=1);

namespace Downloader;

use GuzzleHttp\ClientInterface;

interface Downloader
{
  public function __toString(): string;

  /**
   * Return true if a Downloader feels capable of handling the given URL.
   * 
   * @param string $url Request URL.
   * @return bool
   */
  public function match(string $url): bool;

  /**
   * Return a list of URLs to images.
   *
   * @param ClientInterface $client The client used to request the list.
   * @param string $url Request URL.
   * @return ?array An array of URLs.
   */
  public function files(ClientInterface $client, string $url): array;

  /**
   * Middleware used to modify the request according to the downloaders needs.
   *
   * @param string $url Request URL.
   * @return callable The middleware.
   */
  public function middleware(string $url): callable;
}

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
        return 'General';
      case 1:
        return (string) reset($this->downloaders);
      default:
        $head = $this->downloaders;
        $tail = array_pop($head);
        return sprintf('%s and %s', implode(', ', $head), $tail);
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
