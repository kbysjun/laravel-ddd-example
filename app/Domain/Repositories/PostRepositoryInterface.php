<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Post;

interface PostRepositoryInterface
{
    public function save(Post $post): void;

    public function find(string $id): ?Post;

    /** Ownership is checked in the same statement as deletion. */
    public function deleteOwned(string $id, int $authorId): bool;
}
