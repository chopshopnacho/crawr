<?php

declare(strict_types=1);

namespace Crawr\Package;

class Zip implements Package
{
  /** @var string */
  private $file;
  /** @var \ZipArchive */
  private $archive;

  public function __construct()
  {
    $this->archive = new \ZipArchive;
    $this->file = tempnam(sys_get_temp_dir(), '');
    $this->archive->open($this->file, \ZipArchive::CREATE);
  }

  public function add(string $name, string $path): bool
  {
    return $this->archive->addFile($path, $name);
  }

  public function get(): string
  {
    $this->archive->close();
    return file_get_contents($this->file);
  }

  public function extension(): string
  {
    return 'zip';
  }

  public function contentType(): string
  {
    return 'application/zip';
  }
}
