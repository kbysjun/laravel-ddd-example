<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Post;
use App\Domain\Repositories\PostRepositoryInterface;
use App\Domain\ValueObjects\PostBody;
use Illuminate\Support\Facades\DB;

final class DatabasePostRepository implements PostRepositoryInterface
{
    public function save(Post $post): void
    {
        DB::table('posts')->insert([
            'id' => $post->id,
            'author_id' => $post->authorId,
            'body' => $post->body->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function find(string $id): ?Post
    {
        $row = DB::table('posts')->where('id', $id)->first();

        return $row === null ? null : new Post($row->id, (int) $row->author_id, new PostBody($row->body));
    }

    public function deleteOwned(string $id, int $authorId): bool
    {
        return DB::table('posts')->where('id', $id)->where('author_id', $authorId)->delete() === 1;
    }
}
