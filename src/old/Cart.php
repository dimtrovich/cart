<?php

namespace Dimtrovich\Old\Cart;

use BadMethodCallException;
use Dimtrovich\Cart\Handlers\BaseHandler;
use InvalidArgumentException;

/**
 * @method CartItem[] all()
 * @method array<string, CartItem[]> items()
 */
class Cart
{
    const OPTIONS_MAX_ITEMS = 1;
    const OPTIONS_MAX_QTY   = 2;

    private string $id;

    private BaseHandler $instance;

    private array $options = [
        self::OPTIONS_MAX_QTY   => 0,
        self::OPTIONS_MAX_ITEMS => 0,
    ];

    public function __construct($id, private string|BaseHandler $handler) 
    {
        $this->id = md5($id) . '_cart';

        if ($handler instanceof BaseHandler) {
            $this->instance = $handler;
        }
    }

    public static function isCartHash($key): bool
    {
        if (!is_string($key)) {
            return false;
        }

        return preg_match('/^hash:[a-z0-9]{32}$/', $key) !== false;
    }

    

    /**
     * Undocumented function
     *
     * @param int|string|CartItem $id
     */
    public function add($id, int $qty = 1, array $attributes = []): CartItem
    {
        if ($id instanceof CartItem) {
            $item = $id;
        } else {
            $item = new CartItem($id, attributes: $attributes, qty: $qty);
        }

        return $this->factory()->add($item);
    }

    public function remove($id): bool
    {
        $id = $this->id($id);
        $key = self::isCartHash($id) ? 'hash' : 'id';

        foreach ($this->findBy([$key => $id]) as $item) {
            if (false === $this->factory()->remove($item)) {
                return false;
            }
        }

        return true;
    }   

    public function update(string $hash, array|string $key, mixed $value = null): ?CartItem
    {
        if (null === $item = $this->first(compact('hash'))) {
            return null;
        }

        if (is_string($key)) {
            if ($value === null) {
                throw new InvalidArgumentException();
            }

            $key = [$key => $value];
        }

        foreach ($key as $k => $v) {
            if (! in_array($k, ['id', 'hash'], true)) {
                $item->{$k} = $v;
            }
        }

        return $this->factory()->update($item);
    }

    public function has(int|string|CartItem $item): bool
    {
        return $this->factory()->has($this->id($item));
    }

    public function exist(int|string|CartItem $item): bool
    {
        return $this->has($item);
    }

    public function missing(int|string|CartItem $item): bool
    {
        return ! $this->has($item);
    }


    /**
     * @return CartItem[]
     */
    public function findBy(array $criteria): array
    {
        $items = [];

        foreach ($this->all() as $item) {
            foreach ($criteria as $key => $value) {
                if ($item->has($key, $value)) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    public function find($id): ?CartItem
    {
        $id = $this->id($id);

        $criteria = self::isCartHash($id) ? ['hash' => $id] : ['id' => $id];
            
        return $this->first($criteria);
    }

    public function item(int|string $id): array
    {
        return $this->findBy(compact('id'));
    }

    public function first(array $criteria): ?CartItem
    {
        if (!empty($items = $this->findBy($criteria))) {
            return $items[0];
        }

        return null;
    }

    /**
     * @return array<string, CartItem[]>
     */
    public function items(): array
    {
        return $this->factory()->items();
    }

    public function amount(): float
    {
        return $this->qty() * $this->price();
    }
    
    public function price(): float
    {
        return $this->totalOf('price');
    }   

    public function qty(): float
    {
        return $this->totalOf('qty');
    }

    public function tax(): float
    {
        return $this->totalOf('tax');
    }

    public function count(bool $distinct = false): int
    {
        if ($distinct) {
            return count($this->items());
        }

        return count($this->all());
    }


    public function totalOf(string $attribute = 'price'): float
    {
        $total = 0.0;

        foreach ($this->all() as $item) {
            if (is_numeric($item->{$attribute})) {
                $total += $item->{$attribute};
            }
        }

        return $total;
    }

    /**
     * @return int|string
     */
    private function id(int|string|CartItem $id)
    {
        if ($id instanceof CartItem) {
            return $id->id;
        }

        return $id;
    }

    /**
	 * Check if the cart is empty.
	 */
	public function isEmpty() : bool
	{
		return empty(array_filter($this->items()));
	}

    public function __call($name, $arguments)
    {
        $factory = $this->factory();

        if (method_exists($factory, $name)) {
            return call_user_func_array([$factory, $name], $arguments);
        }

        throw new BadMethodCallException();
    }

    private function factory(): BaseHandler
    {
        if (null === $this->instance) {
            if (is_string($this->handler) && is_a($this->handler, BaseHandler::class, true)) {
                $handler        = $this->handler;
                $this->instance = new $handler($this->id);
            } else {
                throw new InvalidArgumentException();
            }
        }

        return $this->instance;
    }
}
