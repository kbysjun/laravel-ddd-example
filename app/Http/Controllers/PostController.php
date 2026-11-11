<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Presenters\PostPresenter;
use App\Http\Requests\CreatePostRequest;
use App\UseCases\Post\CreatePost;
use App\UseCases\Post\DeletePost;
use App\UseCases\Post\ShowPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PostController extends Controller
{
    public function store(CreatePostRequest $request, CreatePost $useCase, PostPresenter $presenter): JsonResponse
    {
        $post = $useCase->execute((int) $request->user()->getAuthIdentifier(), $request->validated('body'));

        return response()->json($presenter->present($post), 201)
            ->header('Location', url('/api/v1/posts/'.$post->id));
    }

    public function show(string $post, ShowPost $useCase, PostPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->present($useCase->execute($post)));
    }

    public function destroy(Request $request, string $post, DeletePost $useCase): Response
    {
        $useCase->execute((int) $request->user()->getAuthIdentifier(), $post);

        return response()->noContent();
    }
}
