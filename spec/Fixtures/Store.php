<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\Cart\Spec;

use Dimtrovich\Cart\Contracts\StoreManager;
use Dimtrovich\Cart\Handlers\BaseHandler;

class Store extends BaseHandler implements StoreManager
{
    private array $items = [];

    /**
     * {@inheritDoc}
     */
    public function has(): bool
    {
        return isset($this->items[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    public function read(): array
    {
        return $this->items[$this->key()] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(): void
    {
        unset($this->items[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $value): void
    {
        $this->items[$this->key()] = $value;
    }
}
