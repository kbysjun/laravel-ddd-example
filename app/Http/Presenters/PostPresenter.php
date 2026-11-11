<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Domain\Entities\Post;

final class PostPresenter
{
    public function present(Post $post): array
    {
        return ['data' => ['id' => $post->id, 'body' => $post->body->value]];
    }
}
