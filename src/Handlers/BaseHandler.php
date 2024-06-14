<?php

namespace Dimtrovich\Cart\Handlers;

use BlitzPHP\Utilities\Iterable\Collection;
use Dimtrovich\Cart\Contracts\StoreManager;

abstract class BaseHandler implements StoreManager
{
    /**
     * Identifiant du panier
     */
    protected string $cartId;

    /**
     * Liste des elements du panier
     */
    protected array $items;

    /**
     * {@inheritDoc}
     */
    public function init(string $cartId): bool
    {
        $this->cartId = $cartId;

        if (!$this->has()) {
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
     */
    abstract protected function read(): array;

    /**
     * Set raw value of cart items in store manager.
     */
    abstract protected function write(array $value): void;

    /**
     * Get key of card in store manager
     */
    protected function key(): string
    {
        return 'card:'. $this->cartId;
    }
}
