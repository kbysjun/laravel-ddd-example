<?php

declare(strict_types=1);

namespace App\UseCases\Post;

use App\Domain\Entities\Post;
use App\Domain\Repositories\PostRepositoryInterface;

final readonly class ShowPost
{
    public function __construct(private PostRepositoryInterface $posts) {}

    public function execute(string $id): Post
    {
        return $this->posts->find($id) ?? throw new PostNotFound;
    }
}
