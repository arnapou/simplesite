<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Admin;

use Arnapou\Ensure\Enforce;
use Arnapou\Ensure\Ensure;
use Arnapou\SimpleSite\Core;
use Random\Randomizer;

final class AdminConfig
{
    public const string TABLE = 'admin.config';

    public string $passwordHash {
        get {
            return Ensure::string($this->get('password_hash') ?? '');
        }
        set {
            $this->set('password_hash', $this->hash($value));
        }
    }

    public function __construct(private readonly Core\Container $container)
    {
    }

    /**
     * Avoids brute-force attacks by time-gating them: not bothersome for humans.
     */
    public function isPasswordOk(#[\SensitiveParameter] mixed $password): bool
    {
        usleep(random_int(50_000, 200_000)); // 50 to 200 ms

        return $this->passwordHash === $this->hash(Enforce::string($password));
    }

    /**
     * @return list<array{from: string, link: string}>
     */
    public function getRedirects(): array
    {
        return $this->checkRedirects($this->get('redirects') ?? []);
    }

    /**
     * @param array<mixed> $value
     */
    public function setRedirects(array $value): void
    {
        $this->set('redirects', $this->checkRedirects($value));
    }

    /**
     * @return list<array{from: string, link: string}>
     */
    private function checkRedirects(mixed $items): array
    {
        $redirects = [];
        foreach (Ensure::array($items) as $value) {
            if (!\is_array($value)) {
                throw new \TypeError('The redirect item is not an array.');
            }
            if (!isset($value['from'])) {
                throw new \TypeError('Missing "from".');
            }
            if (!isset($value['link'])) {
                throw new \TypeError('Missing "link".');
            }
            $from = ltrim(Enforce::string($value['from']), '/');
            if (isset($redirects[$from])) {
                throw new \TypeError(\sprintf('Duplicate from: "%s".', $from));
            }

            $redirects[$from] = ['from' => $from, 'link' => Enforce::string($value['link'])];
        }

        ksort($redirects);

        return array_values($redirects);
    }

    /**
     * @return int|float|string|bool|array<mixed>|null
     */
    private function get(string $name): int|float|string|bool|array|null
    {
        $value = $this->db()->getTable(self::TABLE)->get($name);
        $value = null === $value ? null : $value['value'] ?? null;

        return \is_int($value) || \is_float($value) || \is_string($value) || \is_bool($value) || \is_array($value) ? $value : null;
    }

    /**
     * @template T of int|float|string|bool|array<mixed>|null
     *
     * @param T $value
     *
     * @return T
     */
    private function set(string $name, int|float|string|bool|array|null $value): int|float|string|bool|array|null
    {
        $table = $this->db()->getTable(self::TABLE);
        $table->upsert(['id' => $name, 'value' => $value]);
        $table->flush();

        return $value;
    }

    private function hash(#[\SensitiveParameter] string $string): string
    {
        return hash_hmac('sha1', $string, $this->secretKey());
    }

    /**
     * @return non-empty-string
     */
    private function secretKey(): string
    {
        $value = Ensure::string($this->get('secret_key') ?? '');

        if ('' === $value) {
            $value = sha1(new Randomizer()->getBytes(5));
            $this->set('secret_key', $value);
        }

        return $value;
    }

    private function db(): Core\Db
    {
        return $this->container->get(Core\Db::class);
    }
}
