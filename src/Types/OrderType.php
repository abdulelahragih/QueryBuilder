<?php

namespace Abdulelahragih\QueryBuilder\Types;

enum OrderType: string
{
    case Descending = 'DESC';
    case Ascending = 'ASC';

    public static function contains(string $value): bool
    {
        $value = strtoupper($value);
        $cases = OrderType::cases();
        foreach ($cases as $case) {
            if ($value === $case->value) {
                return true;
            }
        }
        return false;
    }
}