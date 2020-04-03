<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Crawr\Downloader\Generic;
use Crawr\Package\Image;
use Crawr\Package\Zip;
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

switch ($_POST['format']) {
  case 'image':
    $package = new Image;
    break;
  case 'zip':
    $package = new Zip;
    break;
  default:
    exit('format not available');
    break;
}

$downloader = new Generic;
if (!$downloader::match($url)) {
  exit('url not supported');
}

$stack = new HandlerStack;
$stack->setHandler(new CurlHandler);
$stack->push($downloader::middleware($url));
$client = new Client(['handler' => $stack]);

$urls = $downloader::files($client, $url);
if ($urls === null) {
  exit('pages not found');
}

foreach ($urls as $i => $url) {
  $file = tempnam(sys_get_temp_dir(), '');
  defer($_, fn () => unlink($file));
  $client->request('GET', $url, ['sink' => $file]);
  // TODO: We shouldn't just assume its PNG.
  $package->add(sprintf('%03d.png', $i), $file);
}

header('Content-Type: ' . $package->contentType());
header('Content-disposition: inline; filename=chapter.' . $package->extension());
$package->echo();
