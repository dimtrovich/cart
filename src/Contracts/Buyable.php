<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\Cart\Contracts;

interface Buyable
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @param mixed|null $options
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = null);

    /**
     * Get the description or title of the Buyable item.
     *
     * @param mixed|null $options
     */
    public function getBuyableDescription($options = null): string;

    /**
     * Get the price of the Buyable item.
     *
     * @param mixed|null $options
     */
    public function getBuyablePrice($options = null): float;
}
