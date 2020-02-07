<?php

declare(strict_types=1);

namespace Crawr\Package;

interface Package
{
  /**
   * Add a file to the packager.
   *
   * @param string $name Basename of the file.
   * @param string $path Path to the local file.
   * @return bool
   */
  public function add(string $name, string $path): bool;

  /**
   * Encode the file to a string.
   *
   * @return string Blob.
   */
  public function echo(): void;

  /**
   * Return the file extension.
   *
   * @return string
   */
  public function extension(): string;

  /**
   * Return the content type.
   *
   * @return string
   */
  public function contentType(): string;
}
