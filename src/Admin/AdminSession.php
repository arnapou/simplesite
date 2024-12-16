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

namespace Arnapou\SimpleSite\Admin;

use Arnapou\Ensure\Ensure;
use Random\Randomizer;

final class AdminSession
{
    private const int CSRF_TTL = 1800; // 30 minutes

    public bool $isAuthenticated = false {
        get => Ensure::bool($_SESSION['authenticated'] ?? false);
        set => $_SESSION['authenticated'] = $value;
    }
    public string $flashMessage = '' {
        get {
            $value = Ensure::string($_SESSION['flash_message'] ?? '');
            $_SESSION['flash_message'] = '';

            return $value;
        }
        set => $_SESSION['flash_message'] = $value;
    }
    public int $csrfTime = 0 {
        get => Ensure::int($_SESSION['csrf_time'] ?? 0);
        set => $_SESSION['csrf_time'] = $value;
    }

    /**
     * Avoids brute-force attacks by time-gating them: not bothersome for humans.
     */
    public function isCsrfTokenOk(#[\SensitiveParameter] mixed $token): bool
    {
        usleep(random_int(50_000, 200_000)); // 50 to 200 ms

        return $this->csrfToken() === Ensure::string($token);
    }

    /**
     * @return array<mixed>
     */
    public function context(): array
    {
        return [
            'flash_message' => $this->flashMessage,
            'authenticated' => $this->isAuthenticated,
            'csrf_token' => $this->csrfToken(),
        ];
    }

    public function start(): void
    {
        if ('' === (string) session_id()) {
            session_start();
        }
    }

    /**
     * The token lives 1 hour before being refreshed.
     *
     * @return non-empty-string
     */
    private function csrfToken(): string
    {
        $token = Ensure::string($_SESSION['csrf_token'] ?? '');

        if ('' === $token || time() - $this->csrfTime > self::CSRF_TTL) {
            $token = $_SESSION['csrf_token'] = sha1(new Randomizer()->getBytes(5));
            $this->csrfTime = time();
        }

        return $token;
    }
}
