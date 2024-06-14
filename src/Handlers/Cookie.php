<?php

namespace Dimtrovich\Cart\Handlers;

use Dimtrovich\Cart\Contracts\StoreManager;

class Cookie extends BaseHandler implements StoreManager
{
    public function has(): bool
    {
        return isset($_COOKIE[$this->key()]);    
    }

    /**
     * {@inheritdoc}
     */
    public function read(): array
    {
        if (! $this->has()) {
            return [];
        }

        $value = $_COOKIE[$this->key()];

        return unserialize($value);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(): void
    {
        unset($_COOKIE[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $value): void
    {
        setcookie(
            name: $this->key(), 
            value: serialize($value),
            httponly: true
        );
    }
}
