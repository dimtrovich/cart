<?php

namespace Dimtrovich\Cart;

use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Contracts\Support\Jsonable;
use BlitzPHP\Utilities\Iterable\Arr;
use Dimtrovich\Cart\Contracts\Buyable;
use InvalidArgumentException;

class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     */
    public string $rowId;

    /**
     * The quantity for this cart item.
     */
    public int|float $qty;

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
    private int|float $taxRate = 0;
    
    /**
     * The tax price for the cart item.
     */
    private int|float $priceTax = 0;

    /**
     * CartItem constructor.
     *
     * @param int|string $id The ID of the cart item.
     * @param string     $name The name of the cart item.
     * @param float      $price
     */
    public function __construct(public int|string $id, public string $name, $price, array $options = [])
    {
        if(empty($id)) {
            throw new InvalidArgumentException('Please supply a valid identifier.');
        }
        if(empty($name)) {
            throw new InvalidArgumentException('Please supply a valid name.');
        }
        if(strlen($price) < 0 || ! is_numeric($price)) {
            throw new InvalidArgumentException('Please supply a valid price.');
        }

        $this->price    = floatval($price);
        $this->options  = new CartItemOptions($options);
        $this->rowId = $this->generateRowId($id, $options);
    }

    /**
     * Returns the formatted price without TAX.
     */
    public function price(int $decimals = null, string $decimalPoint = null, string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->price, $decimals, $decimalPoint, $thousandSeperator);
    }
    
    /**
     * Returns the formatted price with TAX.
     */
    public function priceTax(int $decimals = null, string $decimalPoint = null, string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->priceTax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted subtotal.
     * Subtotal is price for whole CartItem without TAX
     */
    public function subtotal(int $decimals = null, string $decimalPoint = null, string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->subtotal, $decimals, $decimalPoint, $thousandSeperator);
    }
    
    /**
     * Returns the formatted total.
     * Total is price for whole CartItem with TAX
     */
    public function total(int $decimals = null, string $decimalPoint = null, string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted tax.
     */
    public function tax(int $decimals = null, string $decimalPoint = null, string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->tax, $decimals, $decimalPoint, $thousandSeperator);
    }
    
    /**
     * Returns the formatted tax.
     */
    public function taxTotal(int $decimals = null, string $decimalPoint = null, string $thousandSeperator = null): string
    {
        return $this->numberFormat($this->taxTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Set the quantity for this cart item.
     */
    public function setQuantity(int|float $qty)
    {
        $this->qty = $qty;
    }

    /**
     * Update the cart item from a Buyable.
     */
    public function updateFromBuyable(Buyable $item): void
    {
        $this->id       = $item->getBuyableIdentifier($this->options);
        $this->name     = $item->getBuyableDescription($this->options);
        $this->price    = $item->getBuyablePrice($this->options);
        $this->priceTax = $this->price + $this->tax;
    }

    /**
     * Update the cart item from an array.
     */
    public function updateFromArray(array $attributes): void
    {
        $this->id       = Arr::get($attributes, 'id', $this->id);
        $this->qty      = Arr::get($attributes, 'qty', $this->qty);
        $this->name     = Arr::get($attributes, 'name', $this->name);
        $this->price    = Arr::get($attributes, 'price', $this->price);
        $this->priceTax = $this->price + $this->tax;
        $this->options  = new CartItemOptions(Arr::get($attributes, 'options', $this->options->toArray()));

        $this->rowId = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Set the tax rate.
     */
    public function setTaxRate(int|float $taxRate): self
    {
        $this->taxRate = $taxRate;
        
        return $this;
    }

    /**
     * Get an attribute from the cart item or get the associated model.
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
            'subtotal' => $this->subtotal
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
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     */
    private function numberFormat($value, $decimals, $decimalPoint, $thousandSeperator): string
    {
        if (is_null($decimals)){
            $decimals = 2;
        }

        if (is_null($decimalPoint)){
            $decimalPoint = '.';
        }

        if (is_null($thousandSeperator)){
            $thousandSeperator = ',';
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }
}