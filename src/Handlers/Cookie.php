<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\Cart\Handlers;

use BlitzPHP\Traits\Support\InteractsWithTime;
use Dimtrovich\Cart\Contracts\StoreManager;

class Cookie extends BaseHandler implements StoreManager
{
    use InteractsWithTime;

    public function has(): bool
    {
        return isset($_COOKIE[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    public function read(): array
    {
        if (! $this->has()) {
            return [];
        }

        $value = $_COOKIE[$this->key()];

        return unserialize($value);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(): void
    {
        unset($_COOKIE[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $value): void
    {
        $_COOKIE[$name = $this->key()] = $value = serialize($value);

        if (headers_sent() === false) {
            setcookie($name, $value, $this->parseCookieOptions());
        }
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    private function parseCookieOptions(): array
    {
        $options = [
            'expires'  => $this->option('expires'),
            'path'     => $this->option('path', ''),
            'domain'   => $this->option('domain', ''),
            'secure'   => $this->option('secure', false),
            'httponly' => $this->option('httponly', true),
            'samesite' => $this->option('samesite', 'Lax'),
        ];

        if (! empty($options['expires'])) {
            if (is_numeric($options['expires'])) {
                $options['expires'] *= 60;
            }
            $options['expires'] = $this->availableAt($options['expires']);
        }

        return $options;
    }
}
