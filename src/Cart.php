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

use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\Iterable\Collection;
use Closure;
use Dimtrovich\Cart\Contracts\Buyable;
use Dimtrovich\Cart\Contracts\StoreManager;
use Dimtrovich\Cart\Exceptions\InvalidRowIDException;
use Dimtrovich\Cart\Handlers\Session;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cart processor
 *
 * @property float|int $total Total price of the items in the cart.
 * @property float|int $subtotal Subtotal (total - tax) of the items in the cart.
 * @property float|int $tax Total tax of the items in the cart.
 */
class Cart
{
    public const DEFAULT_INSTANCE = 'default';

    /**
     * Holds the current cart instance.
     */
    private string $instance;

    /**
     * Cart constructor.
     *
     * @param array                 $config Configuration of cart instance
	 * @param ?StoreManager 		$store  Instance of the session manager.
     * @param EventManagerInterface $event  Instance of the event manager
     */
    public function __construct(private array $config = [], private ?StoreManager $store = null, private ?EventManagerInterface $event = null)
    {
        $this->config += [
            'handler' => Session::class,
            'tax'     => 20,
        ];

        $this->instance(self::DEFAULT_INSTANCE);
    }

    /**
     * Set the current cart instance.
     */
    public function instance(?string $instance = null): self
    {
        $instance = $instance ?: self::DEFAULT_INSTANCE;

        $this->instance = sprintf('%s.%s', 'cart', $instance);

		$this->initStore();

        return $this;
    }

    /**
     * Get the current cart instance.
     */
    public function currentInstance(): string
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * @return CartItem|CartItem[]
     */
    public function add(mixed $id, mixed $name = null, array|float|int|null $qty = null, ?float $price = null, array $options = [])
    {
        if ($this->isMulti($id)) {
            return array_map(fn ($item) => $this->add($item), $id);
        }

        $cartItem = $this->createCartItem($id, $name, $qty, $price, $options);

        $content = $this->getContent();

        if ($content->has($cartItem->rowId)) {
            $cartItem->qty += $content->get($cartItem->rowId)->qty;
        }

        $content->put($cartItem->rowId, $cartItem);

        $this->emit('cart.added', $cartItem);

        $this->store->put($content);

        return $cartItem;
    }

    /**
     * Update the cart item with the given rowId.
     */
    public function update(string $rowId, mixed $qty): ?CartItem
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof Buyable) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();

        if ($rowId !== $cartItem->rowId) {
            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);

            return null;
        }
        $content->put($cartItem->rowId, $cartItem);

        $this->emit('cart.updated', $cartItem);

        $this->store->put($content);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     */
    public function remove(string $rowId): void
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        $this->emit('cart.removed', $cartItem);

        $this->store->put($content);
    }

    /**
     * Get a cart item from the cart by its rowId.
     */
    public function get(string $rowId): CartItem
    {
        $content = $this->getContent();

        if (! $content->has($rowId)) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     */
    public function destroy(): void
    {
        $this->store->remove();
    }

    /**
     * Get the content of the cart.
     */
    public function content(): Collection
    {
        return $this->getContent();
    }

    /**
     * Get the number of items in the cart.
     *
     * @return float|int
     */
    public function count()
    {
        $content = $this->getContent();

        return $content->sum('qty');
    }

    /**
     * Get the total price of the items in the cart.
	 *
	 * @return float|string
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        $content = $this->getContent();

        $total = $content->reduce(fn ($total, CartItem $cartItem) => $total + ($cartItem->qty * $cartItem->priceTax), 0);

        return func_num_args() < 2 ? $total : $this->numberFormat($total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total tax of the items in the cart.
	 *
	 * @return float|string
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        $content = $this->getContent();

        $tax = $content->reduce(fn ($tax, CartItem $cartItem) => $tax + ($cartItem->qty * $cartItem->tax), 0);

        return func_num_args() < 2 ? $tax : $this->numberFormat($tax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
	 *
	 * @return float|string
     */
    public function subtotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeperator = null)
    {
        $content = $this->getContent();

        $subTotal = $content->reduce(fn ($subTotal, CartItem $cartItem) => $subTotal + ($cartItem->qty * $cartItem->price), 0);

		return func_num_args() < 2 ? $subTotal : $this->numberFormat($subTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     */
    public function search(Closure $search): Collection
    {
        $content = $this->getContent();

        return $content->filter($search);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     */
    public function setTax(string $rowId, float|int $taxRate): void
    {
        $cartItem = $this->get($rowId);

        $cartItem->setTaxRate($taxRate);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->store->put($content);
    }

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @param string $attribute
     *
     * @return float|null
     */
    public function __get($attribute)
    {
        if ($attribute === 'total') {
            return $this->total();
        }

        if ($attribute === 'tax') {
            return $this->tax();
        }

        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }

        return null;
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection
     */
    protected function getContent(): Collection
    {
        $content = $this->store->has()
            ? $this->store->get()
            : new Collection();

		return $content->map(fn($attribute) => $this->createCartItem($attribute, null, null, null, []));
    }

    /**
     * Create a new CartItem from the supplied attributes.
     */
    private function createCartItem(mixed $id, mixed $name, array|float|int|null $qty, ?float $price, array $options): CartItem
    {
		$taxRate = null;

        if ($id instanceof Buyable) {
            $cartItem = CartItem::fromBuyable($id, $qty ?: []);
            $cartItem->setQuantity($name ?: 1);
        } elseif (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
			if (isset($id['tax'])) {
				$taxRate = (100 * $id['tax']) / $id['price'];
			}
        } else {
            $cartItem = CartItem::fromAttributes($id, $name, $price, $options);
            $cartItem->setQuantity($qty);
        }

        $cartItem->setTaxRate($taxRate ?? $this->config('tax'));

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     */
    private function isMulti(mixed $item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        $head = reset($item);

        return is_array($head) || $head instanceof Buyable;
    }

    /**
     * Get the Formated number
     */
    private function numberFormat(float $value, ?int $decimals, ?string $decimalPoint, ?string $thousandSeperator): string
    {
		if (null === $decimals) {
            $decimals = $this->config('format.decimals', 2);
        }
        if (null === $decimalPoint) {
            $decimalPoint = $this->config('format.decimal_point');
        }
        if (null === $thousandSeperator) {
            $thousandSeperator = $this->config('format.thousand_seperator');
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }

	/**
	 * Get a specific configuration for card
	 */
    private function config(string $key, mixed $default = null): mixed
    {
        return Arr::dataGet($this->config, $key, $default);
    }

	/**
	 * Emit event
	 */
    private function emit(string $event, mixed $target = null): void
    {
        if (null !== $this->event) {
            $this->event->trigger($event, $target);
        }
    }

	/**
	 * Initialize cart store manager
	 */
    private function initStore(): self
    {
		if (null === $this->store) {
            /** @var class-string<StoreManager> */
            $handler = $this->config('handler', Session::class);
            if (! class_exists($handler) || ! is_a($handler, StoreManager::class, true)) {
                throw new InvalidArgumentException(sprintf('handler must be an class that implements %s', StoreManager::class));
            }

            $this->store = new $handler();
        }

		if (! $this->store->init($this->currentInstance())) {
			throw new RuntimeException(sprintf('Handler %s could not be initialize', $handler));
		}

        return $this;
    }
}
