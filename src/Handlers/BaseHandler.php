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

use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\Iterable\Collection;
use Dimtrovich\Cart\Contracts\StoreManager;

abstract class BaseHandler implements StoreManager
{
    /**
     * cart indentifier
     */
    protected string $cartId;

    /**
     * handler options
     *
     * @var array<string, mixed>
     */
    protected array $options;

    /**
     * {@inheritDoc}
     */
    public function init(string $cartId, array $options = []): bool
    {
        $this->cartId  = $cartId;
        $this->options = $options;

        if (! $this->has()) {
            $this->write([]);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get(): Collection
    {
        return new Collection($this->read());
    }

    /**
     * {@inheritDoc}
     */
    public function put(Collection $value): void
    {
        $this->write($value->toArray());
    }

    /**
     * Get raw value of cart items from store manager.
     *
     * @return array<string, mixed>
     */
    abstract protected function read(): array;

    /**
     * Set raw value of cart items in store manager.
     *
     * @param array<string, mixed> $value
     */
    abstract protected function write(array $value): void;

    /**
     * Get key of card in store manager
     */
    protected function key(): string
    {
        return 'card:' . $this->cartId;
    }

    /**
     * Get a specific option for handler
     */
    protected function option(string $key, mixed $default = null): mixed
    {
        return Arr::dataGet($this->options, $key, $default);
    }
}
