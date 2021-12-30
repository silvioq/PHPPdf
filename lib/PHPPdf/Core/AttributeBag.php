<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

/**
 * Bag of attributes
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class AttributeBag implements \Countable
{
    private array $elements = [];

    public function __construct(array $values = [])
    {
        foreach ($values as $name => $value) {
            $this->add($name, $value);
        }
    }

    public function add($name, $value): static
    {
        $name = (string) $name;

        if ($this->has($name) && is_array($value) && is_array($this->get($name))) {
            $value = $value + $this->get($name);
        }

        $this->elements[$name] = $value;

        return $this;
    }

    public function count(): int
    {
        return count($this->elements);
    }

    public function get($name)
    {
        return $this->has($name) ? $this->elements[$name] : null;
    }

    public function getAll(): array
    {
        return $this->elements;
    }

    public function has($name): bool
    {
        return isset($this->elements[$name]);
    }

    /**
     * Merge couple of bags into one.
     *
     * Type of return object depends on invocation context. Return object is as same
     * type as class used in invocation (late state binding).
     *
     * @param array $bags Array of Bag objects
     *
     * @return Bag Single Bag object contains merged data
     */
    public static function merge(array $bags)
    {
        $mergedBag = new static();

        foreach ($bags as $bag) {
            foreach ($bag->getAll() as $name => $value) {
                $mergedBag->add($name, $value);
            }
        }

        return $mergedBag;
    }

    public function __serialize(): array
    {
        return $this->elements;
    }

    public function __unserialize($serialized): void
    {
        $this->elements = $serialized;
    }
}
