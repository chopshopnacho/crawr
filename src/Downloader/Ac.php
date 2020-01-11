<?php

declare(strict_types=1);

namespace Crawr\Downloader;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Ac implements Downloader
{
  public static function match(string $url): bool
  {
    $url = parse_url($url);
    return $url['host'] === 'ac.qq.com';
  }

  /**
   * Decrypt an "encrypted" base64 string.
   *
   * @param string $data The cyphertext.
   * @param string $nonce The key.
   * @return string The plaintext.
   */
  private static function decrypt(string $data, string $nonce): string
  {
    $data = str_split($data);
    // A nonce in this case consists of groups of numbers and letters. For
    // example `123abc45def789gh`. These parts are split into, in this case,
    // three, pairs: `123abc`, `45def` and `789gh`.
    preg_match_all('/\d+[a-zA-Z]+/', $nonce, $nonce);
    $nonce = $nonce[0];
    for ($i = count($nonce) - 1; $i >= 0; $i--) {
      // Extract the number and the letter part of the current nonce. The
      // letters are basically a way to encode a second number, making the
      // alphanumeric nonce just a list of tuple of numbers. The example above
      // would result in `[[123, 3], [45, 3], [789, 2]]`.
      $offset = intval(preg_replace('/\D+/', '', $nonce[$i])) & 255;
      $length = strlen(preg_replace('/\d+/', '', $nonce[$i]));
      array_splice($data, $offset, $length);
    }
    $data = implode('', $data);
    return base64_decode($data);
  }

  /**
   * Evaluate using eval in some cases.
   *
   * @param string $nonce
   * @return null|string The evaluated nonce.
   */
  private static function processNonce(string $nonce): ?string
  {
    $found = preg_match_all('/"([a-z0-9]+)"|\(\+eval\("(.+?)"\)\)\.toString\(\)/i', $nonce, $matches);
    if (!$found || count($matches[1]) != count($matches[2])) {
      return '';
    }
    $parts = [];
    foreach ($matches[1] as $i => $_) {
      $parts[] = $matches[1][$i] ?: $matches[2][$i];
    }
    foreach ($parts as $i => $_) {
      $parts[$i] = preg_replace('/\s/', '', $parts[$i]);

      // The current part is already at its final form.
      if (preg_match('/^[a-z0-9]+$/i', $parts[$i])) {
        continue;
      }

      // Replace strings that will be evaluated to a number.
      $parts[$i] = preg_replace('/(!+)window\.Array/i', '\1 1', $parts[$i]);
      $parts[$i] = preg_replace('/(!+)document\.children/i', '\1 1', $parts[$i]);
      $parts[$i] = preg_replace('/(!+)document\.getElementsByTagName\(\'html\'\)/i', '\1 1', $parts[$i]);
      $parts[$i] = preg_replace('/\s/', '', $parts[$i]);
      // Floor numbers prefixed with `~~`.
      $parts[$i] = preg_replace('/~~(\d+)\.\d/', '\1', $parts[$i]);
      // Evaluate a negated number. Basically count the number of negations. For
      // example, `!1` is equal to `!2` in that both are "not falsy". `!!1` on
      // the other hand is "not not falsy" making it `0`.
      if (preg_match_all('/!+[0-9]+/', $parts[$i], $matches)) {
        foreach ($matches[0] as $match) {
          $parts[$i] = str_replace($match, (substr_count($match, '!') % 2 + 1) % 2, $parts[$i]);
        }
      }
      // Remove the `parseInt` function call since all numbers are casted to
      // integers.
      if (preg_match('/^parseInt\(([0-9+\-*\/]+)\)$/', $parts[$i], $matches)) {
        $parts[$i] = $matches[1];
      }
      // Round numbers.
      if (preg_match_all('/Math\.round\(([0-9\.]+)\)/', $parts[$i], $matches)) {
        for ($j = 0; $j < count($matches[0]); $j++) {
          $parts[$i] = str_replace($matches[0][$j], round($matches[1][$j]), $parts[$i]);
        }
      }
      // Pow.
      if (preg_match_all('/Math\.pow\((\d+),(\d+)\)/', $parts[$i], $matches)) {
        for ($j = 0; $j < count($matches[0]); $j++) {
          $parts[$i] = str_replace($matches[0][$j], pow($matches[1][$j], $matches[2][$j]), $parts[$i]);
        }
      }
      // This part can be evaluated.
      if (preg_match('/^[0-9+\-*\/\(\)]+$/', $parts[$i])) {
        $parts[$i] = (string) eval('return intval(' . $parts[$i] . ');');
        continue;
      }

      // Substrings.
      if (preg_match('/^\'(\d+)\'.substring\((\d+)\)$/i', $parts[$i], $matches)) {
        $parts[$i] = substr($matches[1], (int) $matches[2]);
        continue;
      }

      // Ternary operations.
      if (preg_match('/(?P<left>[0-9]+)(?P<op>[<=>]{1,2})(?P<right>[0-9]+)\?(?P<a>[0-9]+):(?P<b>[0-9]+)/', $parts[$i], $matches)) {
        $result = eval(sprintf('return %d %s %d;', $matches['left'], $matches['op'], $matches['right']));
        $parts[$i] = $result ? $matches['a'] : $matches['b'];
        continue;
      }

      // ASCII.
      if (preg_match('/^\'(\d+)\'\.charCodeAt\(\)([\-\+])(\d+)$/i', $parts[$i], $matches)) {
        switch ($matches[2]) {
          case '+':
            $parts[$i] = (string) ord($matches[1]) + $matches[3];
          case '-':
            $parts[$i] = (string) ord($matches[1]) - $matches[3];
        }
        continue;
      }
      return null;
    }
    return implode('', $parts);
  }

  public static function files(ClientInterface $client, string $url): ?array
  {
    $res = $client->request('GET', $url);
    if ($res->getStatusCode() !== 200) {
      return null;
    }
    $body = (string) $res->getBody();

    // Extract the nonce used as a decryption key.
    if (!preg_match('/window\[[nonce\'"+\s]{7,}\]\s*=(.+?);/i', $body, $nonce)) {
      return null;
    }
    $nonce = $nonce[1];
    $nonce = self::processNonce($nonce);
    if ($nonce === null) {
      return null;
    }

    // Extract the encrypted data.
    if (!preg_match('/DATA\s*=(.+?)[,;]/i', $body, $data)) {
      return null;
    }
    $data = trim($data[1], ' \'');

    $data = self::decrypt($data, $nonce);
    $data = json_decode($data);
    return array_map(function ($picture) {
      return $picture->url;
    }, $data->picture);
  }

  public static function middleware(string $url): callable
  {
    return function (callable $handler): callable {
      return function (RequestInterface $req, array $options) use ($handler) {
        return $handler($req, $options);
      };
    };
  }
}
