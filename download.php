<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Downloader\Generic;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

if (!isset($_POST['url'])) {
  exit('no url provided');
}
if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
  exit('invalid url provided');
}
$url = $_POST['url'];

$downloader = new Generic;
if (!$downloader::match($url)) {
  exit('url not supported');
}

$stack = new HandlerStack;
$stack->setHandler(new CurlHandler);
$stack->push($downloader::middleware($url));
$client = new Client(['handler' => $stack]);

$images = $downloader::files($client, $url);
if ($images === null) {
  exit('pages not found');
}
$images = array_map(function ($image) use ($client) {
  $file = tempnam(sys_get_temp_dir(), '');
  $client->request('GET', $image, ['sink' => $file]);
  return $file;
}, $images);
$images = array_map(function ($image) {
  return new Imagick($image);
}, $images);

$chapter = new Imagick;
array_walk($images, function ($image) use ($chapter) {
  $chapter->addImage($image);
}, $images);
$chapter->resetIterator();
$chapter = $chapter->appendImages(true);
$format = $chapter->getImageHeight() > 65500 ? 'png' : 'jpeg';
$chapter->setImageFormat($format);

header('Content-Type: image/' . $format);
header('Content-disposition: inline; filename=chapter.' . $format);
exit($chapter->getImagesBlob());
