<?php

namespace Tests\Fixtures;

class Xyzzy
{
    public $bar;

    public $default;

    public function __construct(Bar $bar, string $default = 'test')
    {
        $this->bar = $bar;
        $this->default = $default;
    }
}
