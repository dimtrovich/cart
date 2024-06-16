<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\Cart\CartItem;

describe('CartItem', function () {
    it('can be cast to an array', function () {
        $item = new CartItem(1, 'Some item', 10.00, ['size' => 'XL', 'color' => 'red']);
        $item->setQuantity(2);

        expect($item->toArray())->toEqual([
            'rowId'   => '07d5da5550494c62daf9993cf954303f',
            'id'      => 1,
            'name'    => 'Some item',
            'qty'     => 2,
            'price'   => 10,
            'options' => [
                'size'  => 'XL',
                'color' => 'red',
            ],
            'tax'      => 0,
            'subtotal' => 20,
        ]);
    });

    it('can be cast to a json', function () {
        $item = new CartItem(1, 'Some item', 10.00, ['size' => 'XL', 'color' => 'red']);
        $item->setQuantity(2);

        $value    = $item->toJson();
        $expected = '{"rowId":"07d5da5550494c62daf9993cf954303f","id":1,"name":"Some item","qty":2,"price":10,"options":{"size":"XL","color":"red"},"tax":0,"subtotal":20}';

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($value)->toBe($expected);
    });

    it('can get magic attributes', function () {
        $item = new CartItem(1, 'Some item', 10.00, ['size' => 'XL', 'color' => 'red']);
        $item->setQuantity(2);

        expect([
            'total'        => $item->total,
            'priceTax'     => $item->priceTax,
            'subtotal'     => $item->subtotal,
            'tax'          => $item->tax,
            'taxTotal'     => $item->taxTotal,
            'taxRate'      => $item->taxRate,
            'notAttribute' => $item->notAttribute,
        ])->toEqual([
            'total'        => 20,
            'priceTax'     => 10,
            'subtotal'     => 20,
            'tax'          => 0,
            'taxTotal'     => 0,
            'taxRate'      => 0,
            'notAttribute' => null,
        ]);
    });
});
