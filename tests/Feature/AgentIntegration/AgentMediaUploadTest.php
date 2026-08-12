<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AgentMediaUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        config()->set('media.disk', 'media');
    }

    public function test_credential_can_upload_a_store_image_through_the_shared_media_workflow(): void
    {
        [, $family, $secret] = $this->credential([AgentCredentialAbility::CookbookWrite]);
        $store = Store::factory()->for($family)->create();

        $this->withToken($secret)
            ->post('/api/v1/media/stores/' . $store->id, [
                'image' => UploadedFile::fake()->image('logo.png', 120, 80),
            ])
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'resource_type' => 'stores',
                    'id' => $store->id,
                    'media_type' => 'store-logo',
                ],
            ]);

        Storage::disk('media')->assertExists(
            "family-media/{$family->id}/store-logo/{$store->id}/store-logo-{$store->id}-catalogue.webp",
        );
    }

    public function test_each_supported_resource_type_replaces_only_credential_family_media_without_changing_current_family(): void
    {
        [$issuer, $family, $secret] = $this->credential([AgentCredentialAbility::CookbookWrite]);
        $currentFamily = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $currentFamily->id, 'user_id' => $issuer->id]);
        $issuer->forceFill(['current_family_id' => $currentFamily->id])->save();
        $foreignStore = Store::factory()->for($currentFamily)->create();
        $entities = [
            ['stores', 'store-logo', Store::factory()->for($family)->create(), 1],
            ['ingredients', 'ingredient-photo', Ingredient::factory()->for($family)->create(), 2],
            ['recipes', 'recipe-cover', Recipe::factory()->for($family)->create(), 2],
        ];

        foreach ($entities as [$resourceType, $mediaType, $entity, $variantCount]) {
            $route = "/api/v1/media/{$resourceType}/{$entity->id}";
            $cataloguePath = $this->mediaPath($family, $mediaType, $entity->id, 'catalogue');

            $this->withToken($secret)->post($route, [
                'image' => UploadedFile::fake()->image("{$resourceType}.jpg", 160, 80),
            ])->assertOk();
            $firstBytes = Storage::disk('media')->get($cataloguePath);

            $this->withToken($secret)->post($route, [
                'image' => UploadedFile::fake()->image("{$resourceType}.png", 40, 120),
            ])->assertOk()
                ->assertJsonPath('data.media_type', $mediaType);

            $this->assertNotSame($firstBytes, Storage::disk('media')->get($cataloguePath));
            $this->assertCount(
                $variantCount,
                Storage::disk('media')->allFiles("family-media/{$family->id}/{$mediaType}/{$entity->id}"),
            );
        }

        $this->withToken($secret)
            ->post('/api/v1/media/stores/' . $foreignStore->id, [
                'image' => UploadedFile::fake()->image('foreign.png'),
            ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'resource_not_found');
        Storage::disk('media')->assertMissing(
            $this->mediaPath($currentFamily, 'store-logo', $foreignStore->id, 'catalogue'),
        );
        $this->assertSame($currentFamily->id, $issuer->fresh()?->current_family_id);
    }

    public function test_upload_requires_a_live_credential_with_cookbook_write_ability(): void
    {
        [$issuer, $family, $secret] = $this->credential([]);
        $store = Store::factory()->for($family)->create();
        $route = '/api/v1/media/stores/' . $store->id;

        $this->post($route, ['image' => UploadedFile::fake()->image('logo.png')])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication_required');
        $this->actingAs($issuer)
            ->post($route, ['image' => UploadedFile::fake()->image('logo.png')])
            ->assertUnauthorized();

        Auth::guard('web')->logout();
        Auth::forgetGuards();
        $this->withToken($secret)
            ->post($route, ['image' => UploadedFile::fake()->image('logo.png')])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'ability_required')
            ->assertJsonPath('error.details.required_abilities', ['content:read', 'cookbook:write']);

        FamilyMembership::query()
            ->where('family_id', $family->id)
            ->where('user_id', $issuer->id)
            ->delete();
        Auth::forgetGuards();
        $this->withToken($secret)
            ->post($route, ['image' => UploadedFile::fake()->image('logo.png')])
            ->assertUnauthorized();

        Storage::disk('media')->assertDirectoryEmpty('family-media');
    }

    public function test_upload_uses_the_web_image_validation_and_archive_rules_with_machine_english_errors(): void
    {
        [, $family, $secret] = $this->credential([AgentCredentialAbility::CookbookWrite]);
        $ingredient = Ingredient::factory()->for($family)->create(['archived_at' => now()]);
        $recipe = Recipe::factory()->for($family)->create(['archived_at' => now()]);

        foreach ([
            ['ingredients', $ingredient->id],
            ['recipes', $recipe->id],
        ] as [$resourceType, $id]) {
            $this->withToken($secret)
                ->post("/api/v1/media/{$resourceType}/{$id}", [
                    'image' => UploadedFile::fake()->image('archived.png'),
                ])
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'validation_failed')
                ->assertJsonPath('error.details.fields.image.0', 'Restore the entity before changing its image.');
        }

        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $route = '/api/v1/media/stores/' . $store->id;
        $this->withToken($secret)
            ->post($route, ['image' => UploadedFile::fake()->create('logo.gif', 10, 'image/gif')])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.image.0', 'The image must have a JPG, JPEG, PNG, or WEBP extension.');
        $this->withToken($secret)
            ->post($route, ['image' => UploadedFile::fake()->image('logo.jpg')->size(5121)])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.image.0', 'The image must not be larger than 5 MB.');
        $this->withToken($secret)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent(
                    'broken.jpg',
                    "\xFF\xD8\xFF\xE0" . str_repeat("\0", 128),
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.image.0', 'The image could not be decoded safely.');
        $this->withToken($secret)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent(
                    'compressed-bomb.png',
                    $this->oversizedPngHeader(8192, 8192),
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.image.0', 'The image could not be decoded safely.');
        $this->withToken($secret)
            ->post($route, [
                'image' => UploadedFile::fake()->image('logo.png'),
                'family_id' => $family->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.family_id.0', 'The field is not supported.');
        $this->withToken($secret)
            ->post($route, [
                'image' => UploadedFile::fake()->image('logo.png'),
                'attachment' => UploadedFile::fake()->image('extra.png'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.attachment.0', 'The field is not supported.');

        $this->withToken($secret)
            ->post('/api/v1/media/store_sections/' . $section->id, [
                'image' => UploadedFile::fake()->image('section.png'),
            ])
            ->assertNotFound();
        $this->withToken($secret)
            ->post('/api/v1/media/recipe_tags/1', ['image' => UploadedFile::fake()->image('logo.png')])
            ->assertNotFound();
        Storage::disk('media')->assertDirectoryEmpty('family-media');
    }

    public function test_upload_enforces_each_configured_decoded_dimension_limit_with_machine_english_errors(): void
    {
        [, $family, $secret] = $this->credential([AgentCredentialAbility::CookbookWrite]);
        $store = Store::factory()->for($family)->create();
        $route = '/api/v1/media/stores/' . $store->id;

        foreach ([
            ['max_width', 2, 3, 2],
            ['max_height', 2, 2, 3],
            ['max_pixels', 8, 3, 3],
        ] as [$limit, $maximum, $width, $height]) {
            config()->set('media.max_width', 10);
            config()->set('media.max_height', 10);
            config()->set('media.max_pixels', 100);
            config()->set("media.{$limit}", $maximum);

            $this->withToken($secret)
                ->post($route, [
                    'image' => UploadedFile::fake()->image("{$limit}.png", $width, $height),
                ])
                ->assertUnprocessable()
                ->assertJsonPath('error.details.fields.image.0', 'The image could not be decoded safely.');
        }

        Storage::disk('media')->assertDirectoryEmpty('family-media');
    }

    public function test_upload_is_rate_limited_per_credential(): void
    {
        [, $family, $secret] = $this->credential([AgentCredentialAbility::CookbookWrite]);
        $store = Store::factory()->for($family)->create();
        config()->set('agent-integration.rates.media_upload_per_minute', 2);
        $route = '/api/v1/media/stores/' . $store->id;

        foreach (['first.jpg', 'second.jpg'] as $filename) {
            $this->withToken($secret)
                ->post($route, ['image' => UploadedFile::fake()->image($filename)])
                ->assertOk();
        }

        $this->withToken($secret)
            ->post($route, ['image' => UploadedFile::fake()->image('third.jpg')])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'rate_limit_exceeded');
    }

    private function oversizedPngHeader(int $width, int $height): string
    {
        $ihdr = pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0);

        return "\x89PNG\r\n\x1A\n"
            . $this->pngChunk('IHDR', $ihdr)
            . $this->pngChunk('IEND', '');
    }

    private function pngChunk(string $type, string $data): string
    {
        $crc = hex2bin(hash('crc32b', $type . $data));
        $this->assertIsString($crc);

        return pack('N', strlen($data)) . $type . $data . $crc;
    }

    private function mediaPath(Family $family, string $mediaType, int $entityId, string $variant): string
    {
        return "family-media/{$family->id}/{$mediaType}/{$entityId}/{$mediaType}-{$entityId}-{$variant}.webp";
    }

    /** @param list<AgentCredentialAbility> $abilities */
    private function credential(array $abilities): array
    {
        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $issuer->id,
        ]);
        $issued = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Media agent',
            $abilities,
        );

        return [$issuer, $family, $issued->plainTextSecret];
    }
}
