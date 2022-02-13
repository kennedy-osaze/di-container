<?php

namespace Tests\Fixtures;

class Baz
{
    public $foo;

    public function __construct(FooInterface $foo)
    {
        $this->foo = $foo;
    }
}
