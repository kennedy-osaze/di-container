<?php

namespace Tests\Fixtures;

class Quux
{
    public $foobar;

    public function __construct(FooBar $foobar)
    {
        $this->foobar = $foobar;
    }
}