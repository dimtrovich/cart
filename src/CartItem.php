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

use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Contracts\Support\Jsonable;
use BlitzPHP\Utilities\Iterable\Arr;
use Dimtrovich\Cart\Contracts\Buyable;
use InvalidArgumentException;

/**
 * Representation of an item in a cart
 *
 * @property float|int $priceTax Price with TAX
 * @property float|int $subtotal Price for whole CartItem without TAX
 * @property float|int $tax      Applicable tax for one cart item
 * @property float|int $taxTotal Applicable tax for whole cart item
 * @property float|int $total    Price for whole CartItem with TAX
 */
class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     */
    public string $rowId;

    /**
     * The quantity for this cart item.
     */
    public float|int $qty;

    /**
     * The price without TAX of the cart item.
     */
    public float $price;

    /**
     * The options for this cart item.
     */
    public CartItemOptions $options;

    /**
     * The tax rate for the cart item.
     */
    private float|int $taxRate = 0;

    /**
     * CartItem constructor.
     *
     * @param int|string $id    The ID of the cart item.
     * @param string     $name  The name of the cart item.
     * @param float      $price
     */
    public function __construct(public int|string $id, public string $name, $price, array $options = [])
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Please supply a valid identifier.');
        }
        if (empty($name)) {
            throw new InvalidArgumentException('Please supply a valid name.');
        }
        if (strlen($price) < 0 || ! is_numeric($price)) {
            throw new InvalidArgumentException('Please supply a valid price.');
        }

        $this->price   = (float) $price;
        $this->options = new CartItemOptions($options);
        $this->rowId   = $this->generateRowId($id, $options);
    }

    /**
     * Returns the formatted price without TAX.
     *
     * @return float|string
     */
    public function price(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        return func_num_args() < 2 ? $this->price : $this->numberFormat($this->price, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price with TAX.
     *
     * @return float|string
     */
    public function priceTax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        return func_num_args() < 2 ? $this->priceTax : $this->numberFormat($this->priceTax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted subtotal.
     * Subtotal is price for whole CartItem without TAX
     *
     * @return float|string
     */
    public function subtotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        return func_num_args() < 2 ? $this->subtotal : $this->numberFormat($this->subtotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total.
     * Total is price for whole CartItem with TAX
     *
     * @return float|string
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        return func_num_args() < 2 ? $this->total : $this->numberFormat($this->total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted tax.
     *
     * @return float|string
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        return func_num_args() < 2 ? $this->tax : $this->numberFormat($this->tax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted tax.
     *
     * @return float|string
     */
    public function taxTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        return func_num_args() < 2 ? $this->taxTotal : $this->numberFormat($this->taxTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Set the quantity for this cart item.
     */
    public function setQuantity(float|int $qty)
    {
        $this->qty = $qty;
    }

    /**
     * Update the cart item from a Buyable.
     */
    public function updateFromBuyable(Buyable $item): void
    {
        $this->id    = $item->getBuyableIdentifier($this->options);
        $this->name  = $item->getBuyableDescription($this->options);
        $this->price = $item->getBuyablePrice($this->options);
    }

    /**
     * Update the cart item from an array.
     */
    public function updateFromArray(array $attributes): void
    {
        $this->id      = Arr::get($attributes, 'id', $this->id);
        $this->qty     = Arr::get($attributes, 'qty', $this->qty);
        $this->name    = Arr::get($attributes, 'name', $this->name);
        $this->price   = Arr::get($attributes, 'price', $this->price);
        $this->options = new CartItemOptions(Arr::get($attributes, 'options', $this->options->toArray()));

        $this->rowId = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Set the tax rate.
     */
    public function setTaxRate(float|int $taxRate): self
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param mixed $attribute
     */
    public function __get($attribute)
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if ($attribute === 'priceTax') {
            return $this->price + $this->tax;
        }

        if ($attribute === 'subtotal') {
            return $this->qty * $this->price;
        }

        if ($attribute === 'total') {
            return $this->qty * ($this->priceTax);
        }

        if ($attribute === 'tax') {
            return $this->price * ($this->taxRate / 100);
        }

        if ($attribute === 'taxTotal') {
            return $this->tax * $this->qty;
        }

        return null;
    }

    /**
     * Create a new instance from a Buyable.
     */
    public static function fromBuyable(Buyable $item, array $options = []): static
    {
        return new self($item->getBuyableIdentifier($options), $item->getBuyableDescription($options), $item->getBuyablePrice($options), $options);
    }

    /**
     * Create a new instance from the given array.
     */
    public static function fromArray(array $attributes): static
    {
        $options = Arr::get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $attributes['price'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     */
    public static function fromAttributes(int|string $id, string $name, float $price, array $options = []): static
    {
        return new self($id, $name, $price, $options);
    }

    /**
     * Generate a unique id for the cart item.
     */
    protected function generateRowId(int|string $id, array $options): string
    {
        ksort($options);

        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'rowId'    => $this->rowId,
            'id'       => $this->id,
            'name'     => $this->name,
            'qty'      => $this->qty,
            'price'    => $this->price,
            'options'  => $this->options->toArray(),
            'tax'      => $this->tax,
            'subtotal' => $this->subtotal,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the formatted number.
     *
     * @param float  $value
     * @param string $decimalPoint
     * @param string $thousandSeperator
     */
    private function numberFormat($value, int $decimals, $decimalPoint, $thousandSeperator): string
    {
        if (null === $decimalPoint) {
            $decimalPoint = '.';
        }

        if (null === $thousandSeperator) {
            $thousandSeperator = ',';
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }
}
