<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\PostBody;

final readonly class Post
{
    public function __construct(
        public string $id,
        public int $authorId,
        public PostBody $body,
    ) {}
}
