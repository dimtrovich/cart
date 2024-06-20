<?php

/**
 * This file is part of dimtrovich/cart".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\Cart\Handlers\Cookie;

return [
    /**
     * Default handler to use for manage cart storage
     *
     * @var class-string<\Dimtrovich\Cart\Contracts\StoreManager>
     *
     * - Dimtrovich\Cart\Handlers\Cookie
     * - Dimtrovich\Cart\Handlers\Session
     */
    'handler' => Cookie::class,

    /**
     * Default tax rate
     *
     * @var int
     */
    'tax' => 21,

    /*
    |--------------------------------------------------------------------------
    | Default number format
    |--------------------------------------------------------------------------
    |
    | This defaults will be used for the formated numbers if you don't set them in the method call.
    */
    'format' => [
        'decimals' => 2,

        'decimal_point' => '.',

        'thousand_seperator' => ',',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration for specific storage handler
    |--------------------------------------------------------------------------
    */
    'options' => [
        /**
         * Options for cookie handler
         */
        Cookie::class => [
            /**
             * --------------------------------------------------------------------------
             * Cookie Expires Timestamp
             * --------------------------------------------------------------------------
             *
             * Default expires timestamp for cookies. Setting this to `0` will mean the
             * cookie will not have the `Expires` attribute and will behave as a session
             * cookie.
             *
             * @var DateInterval|DateTimeInterface|int
             */
            'expires' => env('cookie.expires', 60),

            /**
             * --------------------------------------------------------------------------
             * Cookie Path
             * --------------------------------------------------------------------------
             *
             * Typically will be a forward slash.
             *
             * @var string
             */
            'path' => env('cookie.path', '/'),

            /**
             * --------------------------------------------------------------------------
             * Cookie Domain
             * --------------------------------------------------------------------------
             *
             * Set to `.your-domain.com` for site-wide cookies.
             *
             * @var string
             */
            'domain' => env('cookie.domain', ''),

            /**
             * --------------------------------------------------------------------------
             * Cookie Secure
             * --------------------------------------------------------------------------
             *
             * Cookie will only be set if a secure HTTPS connection exists.
             *
             * @var bool
             */
            'secure' => env('cookie.secure', false),

            /**
             * --------------------------------------------------------------------------
             * Cookie HTTPOnly
             * --------------------------------------------------------------------------
             *
             * Cookie will only be accessible via HTTP(S) (no JavaScript).
             *
             * @var bool
             */
            'httponly' => env('cookie.httponly', true),

            /**
             * --------------------------------------------------------------------------
             * Cookie SameSite
             * --------------------------------------------------------------------------
             *
             * Configure cookie SameSite setting. Allowed values are:
             * - None
             * - Lax
             * - Strict
             * - ''
             *
             * Defaults to `Lax` for compatibility with modern browsers. Setting `''`
             * (empty string) means default SameSite attribute set by browsers (`Lax`)
             * will be set on cookies. If set to `None`, `$secure` must also be set.
             *
             * @phpstan-var 'None'|'Lax'|'Strict'|''
             * @var string
             */
            'samesite' => env('cookie.samesite', 'Lax'),
        ],
    ],
];
