<?php

declare(strict_types=1);

namespace App\UseCases\Post;

use App\Domain\Repositories\PostRepositoryInterface;

final readonly class DeletePost
{
    public function __construct(private PostRepositoryInterface $posts) {}

    public function execute(int $actorId, string $id): void
    {
        if (! $this->posts->deleteOwned($id, $actorId)) {
            throw new PostNotFound;
        }
    }
}
