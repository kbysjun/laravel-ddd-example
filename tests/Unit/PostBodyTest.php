<?php

namespace Tests\Unit;

use App\Domain\ValueObjects\PostBody;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostBodyTest extends TestCase
{
    public function test_it_trims_body_and_accepts_japanese_boundary(): void
    {
        $this->assertSame('投稿', (new PostBody(' 投稿 '))->value);
        $this->assertSame(str_repeat('あ', 500), (new PostBody(str_repeat('あ', 500)))->value);
    }

    #[DataProvider('invalidBodies')]
    public function test_it_rejects_invalid_body(string $body): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostBody($body);
    }

    public static function invalidBodies(): array
    {
        return [[''], ['   '], [str_repeat('あ', 501)]];
    }
}
