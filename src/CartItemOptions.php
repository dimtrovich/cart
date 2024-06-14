<?php

namespace Dimtrovich\Cart;

use BlitzPHP\Utilities\Iterable\Collection;

class CartItemOptions extends Collection
{
    /**
     * Get the option by the given key.
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }
}