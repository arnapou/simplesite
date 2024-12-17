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

use Arnapou\Encoder\Base64\Base64UrlSafeEncoder;
use Arnapou\Encoder\Book\Book;
use Arnapou\Encoder\Book\OneByteBookEncoder;
use Arnapou\Encoder\Encoder;
use Arnapou\Encoder\PipelineEncoder;

final readonly class UrlEncoder implements Encoder
{
    private PipelineEncoder $pipeline;

    public function __construct()
    {
        $this->pipeline = new PipelineEncoder(
            new OneByteBookEncoder($this->createBook()),
            new Base64UrlSafeEncoder(),
        );
    }

    /**
     * @see https://www3.nd.edu/~busiforc/handouts/cryptography/Letter%20Frequencies.html
     * @see http://practicalcryptography.com/cryptanalysis/letter-frequencies-various-languages/english-letter-frequencies/
     */
    private function createBook(): Book
    {
        return new class implements Book {
            public function getWords(): array
            {
                return [
                    // scopes: 4
                    ...array_map(static fn (Scope $scope) => $scope->toString(), Scope::cases()),
                    // special: 16
                    ...str_split('\' ~+-*/,.!#@&=:_'),
                    // digits: 62
                    ...str_split('0123456789'),
                    ...str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                    ...str_split('abcdefghijklmnopqrstuvwxyz'),
                    // extension: 20
                    ...explode(' ', '.php .css .js .json .twig .html .md .yaml .gif .png .jpg .svg .mp4 .mov .avi .mkv .mp3 .wav .ogg .aac'),
                    // trigrams: 20
                    ...explode(' ', 'the and ing her hat his tha ere for ent ion ter was you ith ver all wit thi tio'),
                    // bigrams: 134
                    ...explode(' ', 'th he in er an re on at en nd ti es or te of ed is it al ar'),
                    ...explode(' ', 'st to nt ng se ha as ou io le ve co me de hi ri ro ic ne ea'),
                    ...explode(' ', 'ra ce li ch ll be ma si om ur ca el ta la ns di fo ho pe ec'),
                    ...explode(' ', 'pr no ct us ac ot il tr ly nc et ut ss so rs un lo wa ge ie'),
                    ...explode(' ', 'wh ee wi em ad ol rt po we na ul ni ts mo ow pa im mi ai sh'),
                    ...explode(' ', 'ir su id os iv ia am fi ci vi pl ig tu ev ld ry mp fe bl ab'),
                    ...explode(' ', 'gh ty op wo sa ay ex ke fr oo av ag if'),
                ];
            }
        };
    }

    public function encode(string $string): string
    {
        return $this->pipeline->encode($string);
    }

    public function decode(string $string): string
    {
        return $this->pipeline->decode($string);
    }
}
