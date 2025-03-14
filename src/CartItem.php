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
use Closure;
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
     * Custom rowId generator
     */
    private static ?Closure $rowIdGenerator = null;

    /**
     * CartItem constructor.
     *
     * @param int|string           $id      The ID of the cart item.
     * @param string               $name    The name of the cart item.
     * @param array<string, mixed> $options
     */
    public function __construct(public int|string $id, public string $name, float|int $price, array $options = [])
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Please supply a valid identifier.');
        }
        if (empty($name)) {
            throw new InvalidArgumentException('Please supply a valid name.');
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
    public function setQuantity(float|int $qty): self
    {
        $this->qty = $qty;

        return $this;
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
     *
     * @param array<string, mixed> $attributes
     */
    public function updateFromArray(array $attributes): void
    {
        $this->id      = Arr::get($attributes, 'id', $this->id);
        $this->qty     = Arr::get($attributes, 'qty', $this->qty);
        $this->name    = Arr::get($attributes, 'name', $this->name);
        $this->price   = Arr::get($attributes, 'price', $this->price);
        $this->options = new CartItemOptions(array_merge($options = $this->options->toArray(), Arr::get($attributes, 'options', $options)));

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
     */
    public function __get(string $attribute): mixed
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
     *
     * @param array<string, mixed> $options
     */
    public static function fromBuyable(Buyable $item, array $options = []): static
    {
        return new static($item->getBuyableIdentifier($options), $item->getBuyableDescription($options), $item->getBuyablePrice($options), $options);
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): static
    {
        $options = Arr::get($attributes, 'options', []);

        return new static($attributes['id'], $attributes['name'], $attributes['price'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param array<string, mixed> $options
     */
    public static function fromAttributes(int|string $id, string $name, float $price, array $options = []): static
    {
        return new static($id, $name, $price, $options);
    }

    /**
     * Sets a custom row ID generator for cart items.
     *
     * This method allows you to provide a custom closure that generates a unique row ID for each cart item.
     * The closure should accept two parameters: $id (the item's identifier) and $options (an array of item options).
     * It should return a string representing the unique row ID.
     *
     * If no custom generator is provided, the default row ID generator will be used, which generates a unique MD5 hash
     * based on the item's identifier and serialized options.
     *
     * @param Closure(int|string $id, array<string,mixed> $options): string|null $generator The custom row ID generator closure or null to reset to default.
     */
    public static function setRowIdGenerator(?Closure $generator): void
    {
        self::$rowIdGenerator = $generator;
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param array<string, mixed> $options
     */
    protected function generateRowId(int|string $id, array $options): string
    {
        if (null !== self::$rowIdGenerator) {
            return call_user_func(self::$rowIdGenerator, $id, $options);
        }

        ksort($options);

        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
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
     * @param float $value
     */
    private function numberFormat($value, int $decimals, ?string $decimalPoint, ?string $thousandSeperator): string
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
