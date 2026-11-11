<?php

declare(strict_types=1);

namespace App\UseCases\Post;

use App\Domain\Entities\Post;
use App\Domain\Repositories\PostRepositoryInterface;
use App\Domain\ValueObjects\PostBody;
use Illuminate\Support\Str;

final readonly class CreatePost
{
    public function __construct(private PostRepositoryInterface $posts) {}

    public function execute(int $actorId, string $body): Post
    {
        $post = new Post((string) Str::uuid(), $actorId, new PostBody($body));
        $this->posts->save($post);

        return $post;
    }
}
