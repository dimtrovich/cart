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

use Dimtrovich\Cart\Contracts\Buyable;

class BuyableProduct implements Buyable
{
    /**
     * BuyableProduct constructor.
     */
    public function __construct(private int|string $id = 1, private string $name = 'Item name', private float $price = 10.00)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getBuyableIdentifier($options = null)
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getBuyableDescription($options = null): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBuyablePrice($options = null): float
    {
        return $this->price;
    }
}
