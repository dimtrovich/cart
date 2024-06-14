<?php

namespace Dimtrovich\Cart\Contracts;

use BlitzPHP\Utilities\Iterable\Collection;

interface StoreManager
{
    /**
     * Initializes the store manager
     */
    public function init(string $cartId): bool;

    /**
     * Checks if store content the cart item
     */
    public function has(): bool;

    /**
     * Get cart content from the store.
     */
    public function get(): Collection;

    /**
     * Put a cart content in the store.
     */
    public function put(Collection $value): void;

    /**
     * Remove a cart content from the store.
     */
    public function remove(): void;
}
