<?php

namespace Abdulelahragih\QueryBuilder\Grammar;

class Expression
{
    public static function make(string $value): Expression
    {
        return new Expression($value);
    }

    public function __construct(private readonly string $value)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}