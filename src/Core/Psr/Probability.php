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

namespace Arnapou\SimpleSite\Core\Psr;

use ValueError;

/**
 * Probability: probability / divisor.
 *
 * By default, it is never triggered: probability = 0
 */
final class Probability
{
    public function __construct(
        private int $probability = 0,
        private int $divisor = 1,
    ) {
    }

    public function isTriggered(): bool
    {
        try {
            return random_int(1, $this->divisor) <= $this->probability;
        } catch (\Exception) {
            return false;
        }
    }

    /** @return $this */
    public function setProbability(int $probability): self
    {
        $this->probability = $probability >= 0 ? $probability
            : throw new ValueError('probability cannot be negative.');

        return $this;
    }

    /** @return $this */
    public function setDivisor(int $divisor): self
    {
        $this->divisor = $divisor >= 1 ? $divisor
            : throw new ValueError('divisor should be strictly > 0.');

        return $this;
    }

    public function getDivisor(): int
    {
        return $this->divisor;
    }

    public function getProbability(): int
    {
        return $this->probability;
    }
}
