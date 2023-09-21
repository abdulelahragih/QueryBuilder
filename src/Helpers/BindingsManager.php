<?php
declare(strict_types=1);

namespace Abdulelahragih\QueryBuilder\Helpers;

class BindingsManager
{
    protected int $counter = 1;
    protected array $placeholders = [];

    public function add(mixed $value): string
    {
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
