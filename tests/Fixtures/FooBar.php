<?php

namespace Tests\Fixtures;

class FooBar
{
    public $foo;

    public $bar;

    public function __construct(FooInterface $foo, Bar $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
