<?php

namespace Tests\Fixtures;

class Waldo
{
    public $first;

    public $second;

    public $third;

    public function __construct($first, Bar $second, $third)
    {
        $this->first = $first;
        $this->second = $second;
        $this->third = $third;
    }
}
