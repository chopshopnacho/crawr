<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/downloader/downloader.php';
require_once __DIR__ . '/downloader/dongmanmanhua.php';

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

$file = sprintf('./cache/%s.jpg', hash('sha256', $url));
if (file_exists($file)) {
  header('Content-Type: image/jpeg');
  readfile($file);
  exit;
}

$downloader = new Generic;
if (!$downloader->match($url)) {
  exit('url not supported');
}

$stack = new HandlerStack();
$stack->setHandler(new CurlHandler);
$stack->push($downloader->middleware($url));
$client = new Client(['handler' => $stack]);

$images = $downloader->files($client, $url);
$images = array_map(function ($image) use ($client) {
  $file = tempnam(sys_get_temp_dir(), '');
  $client->request('GET', $image, ['sink' => $file]);
  return $file;
}, $images);
$images = array_map(function ($image) {
  return new Imagick($image);
}, $images);

$im = new Imagick;
array_walk($images, function ($image) use ($im) {
  $im->addImage($image);
}, $images);
$im->resetIterator();

$chapter = $im->appendImages(true);
$chapter->setImageFormat('jpeg');
$chapter->writeImage($file);

header('Content-Type: image/jpeg');
readfile($file);
exit;
