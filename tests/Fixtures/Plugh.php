<?php

namespace Tests\Fixtures;

class Plugh
{
    public $first;

    public $last;

    public $number;

    public function __construct(string $first, string $last, int $number)
    {
        $this->first = $first;
        $this->last = $last;
        $this->number = $number;
    }

    public function dump()
    {
        return ['first' => $this->first, 'last' => $this->last, 'number' => $this->number];
    }
}
