<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Helpers;

use Abdulelahragih\QueryBuilder\Grammar\Expression;

class BindingsManager
{
    protected int $counter = 1;
    protected array $placeholders = [];

    public function add(mixed $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }
        $placeholderKey = ':v' . $this->counter;
        $this->placeholders[substr($placeholderKey, 1)] = $value;

        ++$this->counter;
        return $placeholderKey;
    }

    public function reset(): static
    {
        $this->counter = 1;
        $this->placeholders = [];

        return $this;
    }

    public function getBindings(): array
    {
        return $this->placeholders;
    }

    public function getBindingsOrNull(): ?array
    {
        return empty($this->placeholders) ? null : $this->placeholders;
    }
}
