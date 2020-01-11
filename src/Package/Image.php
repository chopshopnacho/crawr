<?php

declare(strict_types=1);

namespace Crawr\Package;

class Image implements Package
{
  /** @var \Imagick */
  private $image;

  public function __construct()
  {
    $this->image = new \Imagick;
  }

  public function add(string $name, string $path): bool
  {
    $image = new \Imagick($path);
    return $this->image->addImage($image);
  }

  public function get(): string
  {
    $this->image->resetIterator();
    $this->image = $this->image->appendImages(true);
    $this->image->setImageFormat($this->extension());
    return $this->image->getImagesBlob();
  }

  public function extension(): string
  {
    return $this->image->getImageHeight() > 65500 ? 'png' : 'jpeg';
  }

  public function contentType(): string
  {
    return 'image/' . $this->extension();
  }
}
