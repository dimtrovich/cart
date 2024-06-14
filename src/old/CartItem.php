<?php

namespace Dimtrovich\Old\Cart;

class CartItem
{
    private string $hash;

    public function __construct(public int|string $id, public float $price = 0.0, public int $qty = 1, public array $attributes = []) 
    {
        $attributes += [
            'tax' => 0,
        ];
        
        $this->hash = 'hash:' . md5(json_encode($attributes));
    }

    /**
     * Hash interne propre a l'element du panier
     */
    public function hash(): string
    {
        return $this->hash;
    }

    /**
     * Vérifie si l'élément actuel est identique à celui specifié
     *
     * @param string|self $item Hash de l'élément ou l'élément tout entier
     */
    public function isSame(string|self $item): bool 
    {
        if ($item instanceof self) {
            $item = $item->hash();
        }

        return $item === $this->hash();
    }

    public function has(string $key, mixed $value): bool
    {
        return $this->{$key} === $value;
    }

    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value)
    {
        $this->attributes[$name] = $value;
    }
}
