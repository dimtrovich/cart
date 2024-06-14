<?php

namespace Dimtrovich\Cart\Handlers;

use Dimtrovich\Cart\Contracts\StoreManager;

class Session extends BaseHandler implements StoreManager
{
    /**
     * {@inheritDoc}
     */
    public function init(string $cartId): bool
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            return false;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return parent::init($cartId);
    }

    /**
     * {@inheritDoc}
     */
    public function has(): bool
    {
        return isset($_SESSION[$this->key()]);    
    }

    /**
     * {@inheritDoc}
     */
    public function read(): array
    {
        return $_SESSION[$this->key()] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(): void
    {
        unset($_SESSION[$this->key()]);     
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $value): void
    {
        $_SESSION[$this->key()] = $value;
    }
}
