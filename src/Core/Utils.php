<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Core;

use Arnapou\SimpleSite\Exception\PathNotCreated;
use Arnapou\SimpleSite\Exception\PathNotWritable;

use function count;

use const PATHINFO_EXTENSION;

use Phar;

use function rtrim;

final class Utils
{
    private const EMOJIS_PASS1 = [
        '>:)' => '&#x1F620;', // mad
        '<:|' => '&#x1F633;', // flushed
    ];
    private const EMOJIS_PASS2 = [
        ':D' => '&#x1F604;', // 😄 biggrin
        ':d' => '&#x1F604;', // 😄 biggrin
        ':)' => '&#x1F60A;', // 😊 smile
        ':-)' => '&#x1F60A;', // 😊 smile
        ':s' => '&#x1F615;', // 😕 confused
        ':S' => '&#x1F615;', // 😕 confused
        ':(' => '&#x1F61E;', // 😞 sad
        ':-(' => '&#x1F61E;', // 😞 sad
        ':p' => '&#x1F601;', // 😁 razz
        ':P' => '&#x1F601;', // 😁 razz
        ':o' => '&#x1F62E;', // 😮 surprise
        ':O' => '&#x1F62E;', // 😮 surprise
        ':B' => '&#x1F60E;', // 😎 cool
        ':|' => '&#x1F610;', // 😐 neutral
        ':/' => '&#x1F614;', // 😔 rolleyes
        ';(' => '&#x1F62D;', // 😭 cry
        ';-(' => '&#x1F62D;', // 😭 cry
        ';)' => '&#x1F609;', // 😉 wink
        ';-)' => '&#x1F609;', // 😉 wink
        ':!:' => '&#x2757;',  // ❗ exclaim
        ':?:' => '&#x2753;',  // ❓ question
        ':lol:' => '&#x1F606;', // 😆 lol
        '^^' => '&#x1F606;', // 😆 lol
        '==>' => '&#x1F846;', // 🡆 arrow
        '=D' => '&#x1F603;', // 😃 mrgreen
        'oO' => '&#x1F632;', // 😲 eek
        'Oo' => '&#x1F632;', // 😲 eek
        'o_O' => '&#x1F632;', // 😲 eek
        '^(' => '&#x1F608;', // 😈 evil
        '(?)' => '&#x2753;',  // ❓ idea
        '^)' => '&#x1F608;', // 😈 twisted
        ':fear:' => '&#x1F631;', // 😱 fear
    ];
    private const UTF8_REMOVE_ACCENTS = [
        'à' => 'a',
        'ô' => 'o',
        'ď' => 'd',
        'ḟ' => 'f',
        'ë' => 'e',
        'š' => 's',
        'ơ' => 'o',
        'ß' => 'ss',
        'ă' => 'a',
        'ř' => 'r',
        'ț' => 't',
        'ň' => 'n',
        'ā' => 'a',
        'ķ' => 'k',
        'ŝ' => 's',
        'ỳ' => 'y',
        'ņ' => 'n',
        'ĺ' => 'l',
        'ħ' => 'h',
        'ṗ' => 'p',
        'ó' => 'o',
        'ú' => 'u',
        'ě' => 'e',
        'é' => 'e',
        'ç' => 'c',
        'ẁ' => 'w',
        'ċ' => 'c',
        'õ' => 'o',
        'ṡ' => 's',
        'ø' => 'o',
        'ģ' => 'g',
        'ŧ' => 't',
        'ș' => 's',
        'ė' => 'e',
        'ĉ' => 'c',
        'ś' => 's',
        'î' => 'i',
        'ű' => 'u',
        'ć' => 'c',
        'ę' => 'e',
        'ŵ' => 'w',
        'ṫ' => 't',
        'ū' => 'u',
        'č' => 'c',
        'ö' => 'oe',
        'è' => 'e',
        'ŷ' => 'y',
        'ą' => 'a',
        'ł' => 'l',
        'ų' => 'u',
        'ů' => 'u',
        'ş' => 's',
        'ğ' => 'g',
        'ļ' => 'l',
        'ƒ' => 'f',
        'ž' => 'z',
        'ẃ' => 'w',
        'ḃ' => 'b',
        'å' => 'a',
        'ì' => 'i',
        'ï' => 'i',
        'ḋ' => 'd',
        'ť' => 't',
        'ŗ' => 'r',
        'ä' => 'ae',
        'í' => 'i',
        'ŕ' => 'r',
        'ê' => 'e',
        'ü' => 'ue',
        'ò' => 'o',
        'ē' => 'e',
        'ñ' => 'n',
        'ń' => 'n',
        'ĥ' => 'h',
        'ĝ' => 'g',
        'đ' => 'd',
        'ĵ' => 'j',
        'ÿ' => 'y',
        'ũ' => 'u',
        'ŭ' => 'u',
        'ư' => 'u',
        'ţ' => 't',
        'ý' => 'y',
        'ő' => 'o',
        'â' => 'a',
        'ľ' => 'l',
        'ẅ' => 'w',
        'ż' => 'z',
        'ī' => 'i',
        'ã' => 'a',
        'ġ' => 'g',
        'ṁ' => 'm',
        'ō' => 'o',
        'ĩ' => 'i',
        'ù' => 'u',
        'į' => 'i',
        'ź' => 'z',
        'á' => 'a',
        'û' => 'u',
        'þ' => 'th',
        'ð' => 'dh',
        'æ' => 'ae',
        'µ' => 'u',
        'ĕ' => 'e',
        'À' => 'a',
        'Ô' => 'o',
        'Ď' => 'd',
        'Ḟ' => 'f',
        'Ë' => 'e',
        'Š' => 's',
        'Ơ' => 'o',
        'Ă' => 'a',
        'Ř' => 'r',
        'Ț' => 't',
        'Ň' => 'n',
        'Ā' => 'a',
        'Ķ' => 'k',
        'Ŝ' => 's',
        'Ỳ' => 'y',
        'Ņ' => 'n',
        'Ĺ' => 'l',
        'Ħ' => 'h',
        'Ṗ' => 'p',
        'Ó' => 'o',
        'Ú' => 'u',
        'Ě' => 'e',
        'É' => 'e',
        'Ç' => 'c',
        'Ẁ' => 'w',
        'Ċ' => 'c',
        'Õ' => 'o',
        'Ṡ' => 's',
        'Ø' => 'o',
        'Ģ' => 'g',
        'Ŧ' => 't',
        'Ș' => 's',
        'Ė' => 'e',
        'Ĉ' => 'c',
        'Ś' => 's',
        'Î' => 'i',
        'Ű' => 'u',
        'Ć' => 'c',
        'Ę' => 'e',
        'Ŵ' => 'w',
        'Ṫ' => 't',
        'Ū' => 'u',
        'Č' => 'c',
        'Ö' => 'o',
        'È' => 'e',
        'Ŷ' => 'y',
        'Ą' => 'a',
        'Ł' => 'l',
        'Ų' => 'u',
        'Ů' => 'u',
        'Ş' => 's',
        'Ğ' => 'g',
        'Ļ' => 'l',
        'Ƒ' => 'f',
        'Ž' => 'z',
        'Ẃ' => 'w',
        'Ḃ' => 'b',
        'Å' => 'a',
        'Ì' => 'i',
        'Ï' => 'i',
        'Ḋ' => 'f',
        'Ť' => 't',
        'Ŗ' => 'r',
        'Ä' => 'a',
        'Í' => 'i',
        'Ŕ' => 'r',
        'Ê' => 'e',
        'Ü' => 'u',
        'Ò' => 'o',
        'Ē' => 'e',
        'Ñ' => 'n',
        'Ń' => 'n',
        'Ĥ' => 'h',
        'Ĝ' => 'g',
        'Đ' => 'd',
        'Ĵ' => 'j',
        'Ÿ' => 'y',
        'Ũ' => 'u',
        'Ŭ' => 'u',
        'Ư' => 'u',
        'Ţ' => 't',
        'Ý' => 'y',
        'Ő' => 'o',
        'Â' => 'a',
        'Ľ' => 'l',
        'Ẅ' => 'w',
        'Ż' => 'z',
        'Ī' => 'i',
        'Ã' => 'a',
        'Ġ' => 'g',
        'Ṁ' => 'm',
        'Ō' => 'o',
        'Ĩ' => 'i',
        'Ù' => 'u',
        'Į' => 'i',
        'Ź' => 'z',
        'Á' => 'a',
        'Û' => 'u',
        'Þ' => 'th',
        'Ð' => 'dh',
        'Æ' => 'ae',
        'Ĕ' => 'e',
    ];

    public static function findPhpFiles(string $path): array
    {
        // mandatory to use opendir family functions inside a Phar
        $files = [];
        if ($dh = opendir($path)) {
            while ($file = readdir($dh)) {
                if (str_ends_with($file, '.php')) {
                    $files[] = $path . '/' . $file;
                }
            }
            closedir($dh);
        }
        sort($files);

        return $files;
    }

    /**
     * @throws PathNotCreated
     * @throws PathNotWritable
     *
     * @return non-empty-string
     */
    public static function mkdir(string $path): string
    {
        if ('' === $path) {
            throw new PathNotCreated($path);
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0o777, true) && !is_dir($path)) {
                throw new PathNotCreated($path);
            }

            return $path;
        }

        if (!is_writable($path)) {
            throw new PathNotWritable($path);
        }

        return $path;
    }

    public static function extension(string $filename): string
    {
        return str_ends_with($filename, '.html.twig')
            ? 'html.twig'
            : pathinfo($filename, PATHINFO_EXTENSION);
    }

    public static function noSlash(string $path): string
    {
        return rtrim($path, '/');
    }

    public static function minifyHtml(string $source): string
    {
        $blocks = [];

        // protection
        $protection = static function (array $matches) use ($blocks): string {
            $num = count($blocks);
            $key = "@@PROTECTED:$num:@@";
            $blocks[$key] = $matches[0];

            return $key;
        };

        $source = (string) preg_replace_callback('!<script[^>]*?>.*?</script>!si', $protection, $source);
        $source = (string) preg_replace_callback('!<pre[^>]*?>.*?</pre>!is', $protection, $source);
        $source = (string) preg_replace_callback('!<textarea[^>]*?>.*?</textarea>!is', $protection, $source);

        // minify
        $source = trim((string) preg_replace('/((?<!\?>)\n)[\s]+/m', '\1', $source));
        $source = (string) preg_replace('#<!---.*?--->#si', '', $source);
        $source = str_replace(["\t", "\n", "\r"], '', $source);

        // restoration before return
        return strtr($source, $blocks);
    }

    public static function emojis(string $text): string
    {
        $text = str_replace(['http://', 'https://'], ['http$$//', 'https$$//'], $text);
        $text = strtr($text, self::EMOJIS_PASS1);
        $text = strtr($text, self::EMOJIS_PASS2);
        $text = str_replace(['http$$//', 'https$$//'], ['http://', 'https://'], $text);

        return $text;
    }

    public static function slugify(string $text): string
    {
        $text = trim($text);
        $text = strtr($text, self::UTF8_REMOVE_ACCENTS);
        $text = (string) preg_replace('![^a-z0-9-]!', '-', $text);
        $text = (string) preg_replace('!--+!', '-', $text);

        return trim($text, '-');
    }
}
