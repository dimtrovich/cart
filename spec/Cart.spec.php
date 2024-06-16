<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Iterable\Collection;
use Dimtrovich\Cart\Cart;
use Dimtrovich\Cart\CartItem;
use Dimtrovich\Cart\Exceptions\InvalidRowIDException;
use Dimtrovich\Cart\Handlers\Cookie;
use Dimtrovich\Cart\Handlers\Session;
use Dimtrovich\Cart\Spec\BuyableProduct;
use Dimtrovich\Cart\Spec\Store;

/**
 * Get an instance of the cart.
 */
function getCart(string $handler = Store::class): Cart
{
    return new Cart(['handler' => $handler, 'tax' => 21]);
}

describe('Cart', function () {
    it('has a default instance', function () {
        expect(getCart()->currentInstance())->toBe(Cart::DEFAULT_INSTANCE);
    });

    it('can add item', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        expect(1)->toBe($cart->count());
    });

    it('can have multiple instances', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'First item'));
        $cart->instance('wishlist')->add(new BuyableProduct(2, 'Second item'));

        expect(1)->toBe($cart->instance(Cart::DEFAULT_INSTANCE)->count());
        expect(1)->toBe($cart->instance('wishlist')->count());
    });

    it('will return the cart item of the added item', function () {
        $cart = getCart();

        $item = $cart->add(new BuyableProduct());

        expect($item)->toBeAnInstanceOf(CartItem::class);
        expect($item->rowId)->toBe('027c91341fd5cf4d2579b49c4b6a90da');
    });

    it('can add multiple buyable items at once', function () {
        $cart = getCart();

        $cart->add([new BuyableProduct(1), new BuyableProduct(2)]);

        expect($cart->count())->toBe(2);
    });

    it('will return an array of cartitems when you add multiple items at once', function () {
        $cart = getCart();

        $items = $cart->add([new BuyableProduct(1), new BuyableProduct(2)]);

        expect($items)->toBeA('array');
        expect(count($items))->toBe(2);

        foreach ($items as $item) {
            expect($item)->toBeAnInstanceOf(CartItem::class);
        }
    });

    it('can add an item from attributes', function () {
        $cart = getCart();

        $cart->add(1, 'Test item', 1, 10.00);

        expect($cart->count())->toBe(1);
    });

    it('can add an item from an array', function () {
        $cart = getCart();

        $cart->add(['id' => 1, 'name' => 'Test item', 'qty' => 1, 'price' => 10.00]);

        expect($cart->count())->toBe(1);
    });

    it('can add multiple array of items at once', function () {
        $cart = getCart();

        $cart->add([
            ['id' => 1, 'name' => 'Test item 1', 'qty' => 1, 'price' => 10.00],
            ['id' => 2, 'name' => 'Test item 2', 'qty' => 1, 'price' => 10.00],
        ]);

        expect($cart->count())->toBe(2);
    });

    it('can add an item with options', function () {
        $cart = getCart();

        $options = ['size' => 'XL', 'color' => 'red'];

        $cart->add(new BuyableProduct(), 1, $options);

        $item = $cart->get('07d5da5550494c62daf9993cf954303f');

        expect($item)->toBeAnInstanceOf(CartItem::class);
        expect($item->options->size)->toBe('XL');
        expect($item->options->color)->toBe('red');
    });

    it('will update the cart if the item already exists in the cart', function () {
        $cart = getCart();

        $item = new BuyableProduct();

        $cart->add($item);
        $cart->add($item);

        expect($cart->count())->toBe(2);
        expect($cart->content()->count())->toBe(1);
    });

    it('will keep updating the quantity when an item is added multiple times', function () {
        $cart = getCart();

        $item = new BuyableProduct();

        $cart->add($item);
        $cart->add($item);
        $cart->add($item);

        expect($cart->count())->toBe(3);
        expect($cart->content()->count())->toBe(1);
    });

    it('can update the quantity of and existing item in the cart', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());
        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 2);

        expect($cart->count())->toBe(2);
        expect($cart->content()->count())->toBe(1);
    });

    it('can update and existing item in the cart from a buyable', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());
        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', new BuyableProduct(1, 'Different description'));

        expect($cart->count())->toBe(1);
        expect($cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name)->toBe('Different description');
    });

    it('can update and existing item in the cart from an array', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());
        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', ['name' => 'Different description']);

        expect($cart->count())->toBe(1);
        expect($cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name)->toBe('Different description');
    });

    it('will throw an exception if a rowid was not found', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        expect(fn () => $cart->update('none-existing-rowid', new BuyableProduct(1, 'Different description')))
            ->toThrow(new InvalidRowIDException());
    });

    it('will regenerate the rowid if the options changed', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(), 1, ['color' => 'red']);
        $cart->update('ea65e0bdcd1967c4b3149e9e780177c0', ['options' => ['color' => 'blue']]);

        expect($cart->count())->toBe(1);
        expect($cart->content()->first()->rowId)->toBe('7e70a1e9aaadd18c72921a07aae5d011');
        expect($cart->get('7e70a1e9aaadd18c72921a07aae5d011')->options->color)->toBe('blue');
    });

    it('will add the item to an existing row if the options changed to an existing rowid', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(), 1, ['color' => 'red']);
        $cart->add(new BuyableProduct(), 1, ['color' => 'blue']);

        $cart->update('7e70a1e9aaadd18c72921a07aae5d011', ['options' => ['color' => 'red']]);

        expect($cart->count())->toBe(2);
        expect($cart->content()->count())->toBe(1);
    });

    it('can remove an item from the cart', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        expect($cart->count())->toBe(1);
        expect($cart->content()->count())->toBe(1);

        $cart->remove('027c91341fd5cf4d2579b49c4b6a90da');

        expect($cart->count())->toBe(0);
        expect($cart->content()->count())->toBe(0);
    });

    it('will remove the item if its quantity was set to zero', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 0);

        expect($cart->count())->toBe(0);
        expect($cart->content()->count())->toBe(0);
    });

    it('will remove the item if its quantity was set negative', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', -1);

        expect($cart->count())->toBe(0);
        expect($cart->content()->count())->toBe(0);
    });

    it('can get an item from the cart by its rowid', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item)->toBeAnInstanceOf(CartItem::class);
    });

    it('can get the content of the cart', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1));
        $cart->add(new BuyableProduct(2));

        $content = $cart->content();

        expect($content)->toBeAnInstanceOf(Collection::class);
        expect(count($content))->toBe(2);
    });

    it('will return an empty collection if the cart is empty', function () {
        $cart = getCart();

        $content = $cart->content();

        expect($content)->toBeAnInstanceOf(Collection::class);
        expect(count($content))->toBe(0);
    });

    it('will include the tax and subtotal when converted to an array', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1));
        $cart->add(new BuyableProduct(2));

        $content = $cart->content();

        expect($content)->toBeAnInstanceOf(Collection::class);
        expect($content->toArray())->toBe([
            '027c91341fd5cf4d2579b49c4b6a90da' => [
                'rowId'    => '027c91341fd5cf4d2579b49c4b6a90da',
                'id'       => 1,
                'name'     => 'Item name',
                'qty'      => 1,
                'price'    => 10.00,
                'options'  => [],
                'tax'      => 2.10,
                'subtotal' => 10.0,
            ],
            '370d08585360f5c568b18d1f2e4ca1df' => [
                'rowId'    => '370d08585360f5c568b18d1f2e4ca1df',
                'id'       => 2,
                'name'     => 'Item name',
                'qty'      => 1,
                'price'    => 10.00,
                'options'  => [],
                'tax'      => 2.10,
                'subtotal' => 10.0,
            ],
        ]);
    });

    it('can destroy a cart', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct());

        expect($cart->count())->toBe(1);

        $cart->destroy();

        expect($cart->count())->toBe(0);
    });

    it('can get the total price of the cart content', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'First item', 10.00));
        $cart->add(new BuyableProduct(2, 'Second item', 25.00), 2);

        expect($cart->count())->toBe(3);
        expect($cart->subtotal())->toBe(60.00);
    });

    it('can returned a formatted total', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'First item', 1000.00));
        $cart->add(new BuyableProduct(2, 'Second item', 2500.00), 2);

        expect($cart->count())->toBe(3);
        expect($cart->subtotal(2, ',', '.'))->toBe('6.000,00');
    });

    it('can search the cart for a specific item', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some item'));
        $cart->add(new BuyableProduct(2, 'Another item'));

        $items = $cart->search(fn ($cartItem, $rowId) => $cartItem->name === 'Some item');

        expect($items)->toBeAnInstanceOf(Collection::class);
        expect(count($items))->toBe(1);
        expect($items->first())->toBeAnInstanceOf(CartItem::class);
        expect($items->first()->id)->toBe(1);
    });

    it('can search the cart for multiple item', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some item'));
        $cart->add(new BuyableProduct(3, 'Some item'));
        $cart->add(new BuyableProduct(2, 'Another item'));

        $items = $cart->search(fn ($cartItem, $rowId) => $cartItem->name === 'Some item');

        expect($items)->toBeAnInstanceOf(Collection::class);
        expect(count($items))->toBe(2);
        expect($items->last()->id)->toBe(3);
    });

    it('can search the cart for a specific item with options', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some item'), 1, ['color' => 'red']);
        $cart->add(new BuyableProduct(2, 'Another item'), 1, ['color' => 'blue']);

        $items = $cart->search(fn ($cartItem, $rowId) => $cartItem->options->color === 'red');

        expect($items)->toBeAnInstanceOf(Collection::class);
        expect(count($items))->toBe(1);
        expect($items->first())->toBeAnInstanceOf(CartItem::class);
        expect($items->first()->id)->toBe(1);
    });

    it('can calculate the subtotal of a cart item', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 9.99), 3);

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item->subtotal)->toBe(29.97);
    });

    it('can return a formatted subtotal', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 500), 3);

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item->subtotal(2, ',', '.'))->toBe('1.500,00');
    });

    it('can calculate tax based on the default tax rate in the config', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item->tax)->toBe(2.10);
    });

    it('can calculate tax based on the specified tax', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);

        $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item->tax)->toBe(1.90);
    });

    it('can return the calculated tax formatted', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 10000.00), 1);

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item->tax(2, ',', '.'))->toBe('2.100,00');
    });

    it('can calculate the total tax for all cart items', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 20.00), 2);

        expect($cart->tax)->toBe(10.50);
    });

    it('can return formatted total tax', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 1000.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 2000.00), 2);

        expect($cart->tax(2, ',', '.'))->toBe('1.050,00');
    });

    it('can return the subtotal', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 20.00), 2);

        expect($cart->subtotal)->toBe(50.00);
    });

    it('can return formatted subtotal', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'Some title', 1000.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 2000.00), 2);

        expect($cart->subtotal(2, ',', ''))->toBe('5000,00');
    });

    it('can calculate all values', function () {
        $cart = getCart();

        $cart->add(new BuyableProduct(1, 'First item', 10.00), 2);

        $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);

        $item = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        expect($item->price(2))->toBe(10.00);
        expect($item->priceTax(2))->toBe(11.90);
        expect($item->subtotal(2))->toBe(20.00);
        expect($item->total(2))->toBe(23.80);
        expect($item->tax(2))->toBe(1.90);
        expect($item->taxTotal(2))->toBe(3.80);

        expect($cart->subtotal(2))->toBe(20.00);
        expect($cart->total(2))->toBe(23.80);
        expect($cart->tax(2))->toBe(3.80);
        expect($cart->notAttribute)->toBeNull();
    });

    describe('Session and cookie handlers stubs', function () {
        it('session handler work', function () {
            allow('session_start')->toBeCalled()->andReturn(true);
            $_SESSION = [];

            $cart = getCart(Session::class);

            $cart->add(1, 'Test item', 1, 10.00);
            expect($cart->count())->toBe(1);

            $cart->destroy();
            expect($cart->count())->toBe(0);
        });

        it('session is disabled', function () {
            allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_DISABLED);

            expect(fn () => getCart(Session::class))
                ->toThrow(new RuntimeException(sprintf('Handler %s could not be initialize', Session::class)));
        });

        it('cookie handler work', function () {
            $cart = getCart(Cookie::class);

            $cart->add(1, 'Test item', 1, 10.00);
            expect($cart->count())->toBe(1);

            $cart->destroy();
            expect($cart->count())->toBe(0);
        });
    });
});
