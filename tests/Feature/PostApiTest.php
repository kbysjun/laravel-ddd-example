<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_any_post_endpoint(): void
    {
        $id = (string) Str::uuid();
        $this->postJson('/api/v1/posts', ['body' => 'hello'])->assertUnauthorized();
        $this->getJson('/api/v1/posts/'.$id)->assertUnauthorized();
        $this->deleteJson('/api/v1/posts/'.$id)->assertUnauthorized();
    }

    public function test_owner_can_create_read_and_delete_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/posts', ['body' => ' 投稿 '])->assertCreated()->assertJsonPath('data.body', '投稿');
        $id = $response->json('data.id');
        $response->assertHeader('Location', url('/api/v1/posts/'.$id));
        $this->assertTrue(Str::isUuid($id));
        $this->assertDatabaseHas('posts', ['id' => $id, 'author_id' => $user->id]);
        $this->assertArrayNotHasKey('author_id', $response->json('data'));
        $this->getJson('/api/v1/posts/'.$id)->assertOk()->assertJsonPath('data.body', '投稿');
        $this->deleteJson('/api/v1/posts/'.$id)->assertNoContent();
        $this->getJson('/api/v1/posts/'.$id)->assertNotFound();
    }

    public function test_another_user_cannot_delete_a_post_even_knowing_its_uuid(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/v1/posts', ['body' => 'hello'])->json('data.id');
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson('/api/v1/posts/'.$id)->assertNotFound();
        $this->assertDatabaseHas('posts', ['id' => $id]);
    }

    public function test_invalid_input_and_author_spoofing_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        foreach (['', '   ', str_repeat('あ', 501)] as $body) {
            $this->postJson('/api/v1/posts', ['body' => $body])->assertUnprocessable()->assertJsonValidationErrors('body');
        }
        $this->postJson('/api/v1/posts', ['body' => 'hello', 'author_id' => 999])->assertUnprocessable();
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_missing_and_malformed_ids_return_json_404(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/posts/'.Str::uuid())->assertNotFound();
        $this->get('/api/v1/posts/not-a-uuid')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_real_bearer_token_is_required_and_expiration_is_enforced(): void
    {
        $user = User::factory()->create();
        $valid = $user->createToken('test', ['*'], now()->addHour())->plainTextToken;
        $expired = $user->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($valid)->postJson('/api/v1/posts', ['body' => 'hello'])->assertCreated();
        app('auth')->forgetGuards();
        $this->withToken($expired)->postJson('/api/v1/posts', ['body' => 'hello'])->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->withToken('invalid')->postJson('/api/v1/posts', ['body' => 'hello'])->assertUnauthorized();
    }

    public function test_rate_limit_is_applied_per_user(): void
    {
        Sanctum::actingAs(User::factory()->create());
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/posts/'.Str::uuid())->assertNotFound();
        }
        $this->getJson('/api/v1/posts/'.Str::uuid())->assertStatus(429);
    }
}
