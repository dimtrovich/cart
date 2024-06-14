<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
