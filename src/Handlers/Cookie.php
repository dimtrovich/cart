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

use Dimtrovich\Cart\Contracts\StoreManager;

class Cookie extends BaseHandler implements StoreManager
{
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
		$_COOKIE[$name  = $this->key()] = $value = serialize($value);

		if (headers_sent() === false) {
			setcookie(name: $name, value: $value, httponly: true);
		}
    }
}
