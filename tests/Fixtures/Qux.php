<?php

namespace Tests\Fixtures;

class Qux
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
