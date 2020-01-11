<?php

declare(strict_types=1);

use Crawr\Downloader\Generic;

require_once __DIR__ . '/vendor/autoload.php';
?>
<!doctype html>
<html>

<head>
  <title>Download</title>
  <link href="https://fonts.googleapis.com/css?family=Press+Start+2P" rel="stylesheet">
  <link href="https://unpkg.com/nes.css@2.2.1/css/nes.min.css" rel="stylesheet">
  <style>
    html,
    body,
    pre,
    code,
    kbd,
    samp {
      font-family: 'Press Start 2P', monospace;
    }

    body {
      min-height: 100vh;
    }

    body {
      padding: 1rem;
    }

    .container {
      max-width: 980px;
      margin: 0 auto;
    }

    .nes-field:not(:last-of-type) {
      margin-bottom: 1rem;
    }

    .is-light {
      background: white;
    }

    .is-right {
      text-align: right;
    }
  </style>
</head>

<body>
  <div class="container">
    <section class="topic">
      <h2 id="about">Download</h2>
      <p>Download raw Manhuas and Manhwas from <?php echo new Generic; ?>.</p>
    </section>

    <form method="post" action="/download.php">
      <div class="nes-container is-light is-rounded">
        <div class="nes-field">
          <label for="url">URL</label>
          <input type="text" class="nes-input" id="url" name="url" placeholder="https://">
        </div>

        <label>
          <input type="radio" class="nes-radio" name="format" value="image" checked>
          <span>Image</span>
        </label>
        <label>
          <input type="radio" class="nes-radio" name="format" value="zip">
          <span>ZIP</span>
        </label>

        <div class="nes-field is-right">
          <button type="submit" class="nes-btn is-primary">Download</button>
        </div>
      </div>
    </form>
  </div>
</body>

</html>