<?php

declare(strict_types=1);

namespace Downloader;

use GuzzleHttp\ClientInterface;

interface Downloader
{
  /**
   * Return true if a Downloader feels capable of handling the given URL.
   *
   * @param string $url Request URL.
   * @return bool
   */
  public static function match(string $url): bool;

  /**
   * Return a list of URLs to images.
   *
   * @param ClientInterface $client The client used to request the list.
   * @param string $url Request URL.
   * @return ?array An array of URLs or null.
   */
  public static function files(ClientInterface $client, string $url): ?array;

  /**
   * Middleware used to modify the request according to the downloaders needs.
   *
   * @param string $url Request URL.
   * @return callable The middleware.
   */
  public static function middleware(string $url): callable;
}
