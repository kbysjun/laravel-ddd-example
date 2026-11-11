<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class PostBody
{
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 500) {
            throw new InvalidArgumentException('Post body must contain 1 to 500 characters.');
        }
        $this->value = $value;
    }
}
