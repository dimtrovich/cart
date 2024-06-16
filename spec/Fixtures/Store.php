<?php

namespace Dimtrovich\Cart\Spec;

use Dimtrovich\Cart\Contracts\StoreManager;
use Dimtrovich\Cart\Handlers\BaseHandler;

class Store extends BaseHandler implements StoreManager
{
	private array $items = [];

	/**
     * {@inheritDoc}
     */
    public function has(): bool
    {
        return isset($this->items[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    public function read(): array
    {
        return $this->items[$this->key()] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(): void
    {
        unset($this->items[$this->key()]);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $value): void
    {
        $this->items[$this->key()] = $value;
    }
}
