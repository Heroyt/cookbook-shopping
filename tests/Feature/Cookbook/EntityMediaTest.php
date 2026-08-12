<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use GdImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class EntityMediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        config()->set('media.disk', 'media');
    }

    public function test_each_member_can_upload_replace_and_read_normalized_private_webp_variants(): void
    {
        config()->set('media.types.store-logo.variants', [
            'catalogue' => ['width' => 80, 'height' => 80],
        ]);

        $uploader = User::factory()->create();
        $reader = User::factory()->create();
        $family = $this->createFamilyWithMembers('Společná rodina', $uploader, $reader);
        $this->selectCurrentFamily($uploader, $family);
        $this->selectCurrentFamily($reader, $family);
        $store = Store::factory()->for($family)->create();
        $path = $this->mediaPath($family, 'store-logo', $store->id, 'catalogue');

        $this->actingAs($uploader)
            ->post(route('entity-media.store', ['mediaType' => 'store-logo', 'entity' => $store]), [
                'image' => UploadedFile::fake()->image('logo.jpeg', 160, 80),
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Obrázek byl uložen.'])
            ->assertRedirect(route('stores.index'));

        Storage::disk('media')->assertExists($path);
        $firstBytes = Storage::disk('media')->get($path);
        $this->assertWebp($firstBytes, 80, 40);

        $this->actingAs($reader)
            ->get(route('entity-media.show', [
                'mediaType' => 'store-logo',
                'entity' => $store,
                'variant' => 'catalogue',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'image/webp')
            ->assertHeader('cache-control', 'no-store, private');

        $this->actingAs($reader)
            ->post(route('entity-media.store', ['mediaType' => 'store-logo', 'entity' => $store]), [
                'image' => UploadedFile::fake()->image('replacement.png', 40, 120),
            ])
            ->assertSessionHasNoErrors();

        $secondBytes = Storage::disk('media')->get($path);
        $this->assertNotSame($firstBytes, $secondBytes);
        $this->assertWebp($secondBytes, 27, 80);
        $this->assertCount(1, Storage::disk('media')->allFiles(dirname($path)));
    }

    public function test_all_approved_entity_media_is_current_family_scoped_and_projected_as_protected_urls(): void
    {
        $member = User::factory()->create();
        $otherMember = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $member);
        $otherFamily = $this->createFamilyWithMembers('Cizí', $otherMember);
        $this->selectCurrentFamily($member, $family);
        $this->selectCurrentFamily($otherMember, $otherFamily);

        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $ingredient = Ingredient::factory()->for($family)->create();
        $recipe = Recipe::factory()->for($family)->create();
        $foreignStore = Store::factory()->for($otherFamily)->create();

        foreach ([
            ['store-logo', $store->id],
            ['store-section-icon', $section->id],
            ['ingredient-photo', $ingredient->id],
            ['recipe-cover', $recipe->id],
        ] as [$type, $entityId]) {
            $this->actingAs($member)
                ->post(route('entity-media.store', ['mediaType' => $type, 'entity' => $entityId]), [
                    'image' => UploadedFile::fake()->image("{$type}.png", 120, 80),
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($member)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('stores.0.logoUrl', $this->mediaUrl('store-logo', $store->id, 'catalogue'))
                ->where('storeSections.0.iconUrl', $this->mediaUrl('store-section-icon', $section->id, 'catalogue')));
        $this->actingAs($member)
            ->get(route('ingredients.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('ingredients.0.photoUrl', $this->mediaUrl('ingredient-photo', $ingredient->id, 'catalogue')));
        $this->actingAs($member)
            ->get(route('recipes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('recipes.0.coverUrl', $this->mediaUrl('recipe-cover', $recipe->id, 'catalogue')));

        $this->actingAs($member)
            ->post(route('entity-media.store', ['mediaType' => 'store-logo', 'entity' => $foreignStore]), [
                'image' => UploadedFile::fake()->image('foreign.png'),
            ])
            ->assertNotFound();
        $this->actingAs($member)
            ->get(route('entity-media.show', [
                'mediaType' => 'store-logo',
                'entity' => $foreignStore,
                'variant' => 'catalogue',
            ]))
            ->assertNotFound();
        Storage::disk('media')->assertMissing(
            $this->mediaPath($otherFamily, 'store-logo', $foreignStore->id, 'catalogue'),
        );
    }

    public function test_upload_accepts_static_webp_and_rejects_animated_unsupported_oversized_and_undecodable_files_in_czech(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $route = route('entity-media.store', ['mediaType' => 'store-logo', 'entity' => $store]);

        $this->actingAs($user)
            ->post($route, ['image' => UploadedFile::fake()->createWithContent('logo.webp', $this->webpBytes())])
            ->assertSessionHasNoErrors();

        $storedPath = $this->mediaPath($family, 'store-logo', $store->id, 'catalogue');
        Storage::disk('media')->assertExists($storedPath);
        $storedBytes = Storage::disk('media')->get($storedPath);

        $this->actingAs($user)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent(
                    'animated.webp',
                    $this->animatedWebpBytes(),
                ),
            ])
            ->assertSessionHasErrors([
                'image' => 'Animované obrázky WebP nejsou podporované. Vyberte statický obrázek.',
            ]);

        $jpeg = UploadedFile::fake()->image('logo.jpg');

        $this->actingAs($user)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent('logo.txt', $jpeg->getContent()),
            ])
            ->assertSessionHasErrors(['image' => 'Soubor musí mít příponu JPG, JPEG, PNG nebo WEBP.']);

        $this->actingAs($user)
            ->post($route, ['image' => UploadedFile::fake()->image('logo.jpg')->size(5121)])
            ->assertSessionHasErrors(['image' => 'Obrázek nesmí být větší než 5 MB.']);

        $this->actingAs($user)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent(
                    'broken.jpg',
                    "\xFF\xD8\xFF\xE0" . str_repeat("\0", 128),
                ),
            ])
            ->assertSessionHasErrors(['image' => 'Obrázek se nepodařilo bezpečně načíst.']);

        $this->actingAs($user)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent(
                    'compressed-bomb.png',
                    $this->oversizedPngHeader(8192, 8192),
                ),
            ])
            ->assertSessionHasErrors(['image' => 'Obrázek se nepodařilo bezpečně načíst.']);

        $completeJpeg = UploadedFile::fake()->image('complete.jpg')->getContent();

        $this->actingAs($user)
            ->post($route, [
                'image' => UploadedFile::fake()->createWithContent(
                    'truncated.jpg',
                    substr($completeJpeg, 0, -2),
                ),
            ])
            ->assertSessionHasErrors(['image' => 'Obrázek se nepodařilo bezpečně načíst.']);

        $this->assertSame($storedBytes, Storage::disk('media')->get($storedPath));
    }

    public function test_upload_enforces_each_configured_decoded_dimension_limit_in_czech(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $route = route('entity-media.store', ['mediaType' => 'store-logo', 'entity' => $store]);

        foreach ([
            ['max_width', 2, 3, 2],
            ['max_height', 2, 2, 3],
            ['max_pixels', 8, 3, 3],
        ] as [$limit, $maximum, $width, $height]) {
            config()->set('media.max_width', 10);
            config()->set('media.max_height', 10);
            config()->set('media.max_pixels', 100);
            config()->set("media.{$limit}", $maximum);

            $this->actingAs($user)
                ->post($route, [
                    'image' => UploadedFile::fake()->image("{$limit}.png", $width, $height),
                ])
                ->assertSessionHasErrors(['image' => 'Obrázek se nepodařilo bezpečně načíst.']);
        }

        Storage::disk('media')->assertDirectoryEmpty('family-media');
    }

    public function test_replacement_removes_variants_that_are_no_longer_configured(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create();
        $route = route('entity-media.store', ['mediaType' => 'ingredient-photo', 'entity' => $ingredient]);

        $this->actingAs($user)
            ->post($route, ['image' => UploadedFile::fake()->image('first.jpg')])
            ->assertSessionHasNoErrors();
        Storage::disk('media')->assertExists($this->mediaPath($family, 'ingredient-photo', $ingredient->id, 'detail'));

        config()->set('media.types.ingredient-photo.variants', [
            'catalogue' => ['width' => 80, 'height' => 80],
        ]);

        $this->actingAs($user)
            ->post($route, ['image' => UploadedFile::fake()->image('replacement.png')])
            ->assertSessionHasNoErrors();

        Storage::disk('media')->assertMissing($this->mediaPath($family, 'ingredient-photo', $ingredient->id, 'detail'));
        $this->assertCount(
            1,
            Storage::disk('media')->allFiles("family-media/{$family->id}/ingredient-photo/{$ingredient->id}"),
        );
    }

    public function test_archive_retains_media_while_hard_entity_and_family_deletion_remove_it(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $ingredient = Ingredient::factory()->for($family)->create();
        $recipe = Recipe::factory()->for($family)->create();

        foreach ([
            ['store-logo', $store->id],
            ['store-section-icon', $section->id],
            ['ingredient-photo', $ingredient->id],
            ['recipe-cover', $recipe->id],
        ] as [$type, $entityId]) {
            $this->actingAs($user)
                ->post(route('entity-media.store', ['mediaType' => $type, 'entity' => $entityId]), [
                    'image' => UploadedFile::fake()->image("{$type}.jpg"),
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($user)->patch(route('ingredients.archive', $ingredient))->assertSessionHasNoErrors();
        $this->actingAs($user)->patch(route('recipes.archive', $recipe))->assertSessionHasNoErrors();
        Storage::disk('media')->assertExists($this->mediaPath($family, 'ingredient-photo', $ingredient->id, 'catalogue'));
        Storage::disk('media')->assertExists($this->mediaPath($family, 'recipe-cover', $recipe->id, 'catalogue'));
        $archivedIngredientBytes = Storage::disk('media')->get(
            $this->mediaPath($family, 'ingredient-photo', $ingredient->id, 'catalogue'),
        );

        $this->actingAs($user)
            ->post(route('entity-media.store', [
                'mediaType' => 'ingredient-photo',
                'entity' => $ingredient,
            ]), ['image' => UploadedFile::fake()->image('replacement.png')])
            ->assertSessionHasErrors([
                'image' => 'Před změnou obrázku obnovte položku z archivu.',
            ]);
        $this->assertSame(
            $archivedIngredientBytes,
            Storage::disk('media')->get(
                $this->mediaPath($family, 'ingredient-photo', $ingredient->id, 'catalogue'),
            ),
        );

        $this->actingAs($user)->delete(route('stores.destroy', $store))->assertSessionHasNoErrors();
        $this->actingAs($user)->delete(route('store-sections.destroy', $section))->assertSessionHasNoErrors();
        Storage::disk('media')->assertDirectoryEmpty("family-media/{$family->id}/store-logo");
        Storage::disk('media')->assertDirectoryEmpty("family-media/{$family->id}/store-section-icon");

        $this->actingAs($user)
            ->delete(route('current-family.destroy'), ['family_name' => $family->name])
            ->assertSessionHasNoErrors();
        Storage::disk('media')->assertMissing("family-media/{$family->id}");
    }

    public function test_a_failed_multi_variant_replacement_restores_every_previous_file_and_becomes_a_czech_field_error(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create();
        $cataloguePath = app(EntityMediaStorage::class)->path(
            $family->id,
            EntityMediaType::IngredientPhoto,
            $ingredient->id,
            'catalogue',
        );
        $detailPath = app(EntityMediaStorage::class)->path(
            $family->id,
            EntityMediaType::IngredientPhoto,
            $ingredient->id,
            'detail',
        );
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('allFiles')->once()->with(dirname($cataloguePath))->andReturn([
            $cataloguePath,
            $detailPath,
        ]);
        $disk->shouldReceive('get')->once()->with($cataloguePath)->andReturn('old catalogue');
        $disk->shouldReceive('get')->once()->with($detailPath)->andReturn('old detail');
        $disk->shouldReceive('put')->once()->with($cataloguePath, Mockery::type('string'))->andReturnTrue();
        $disk->shouldReceive('put')->once()->with($detailPath, Mockery::type('string'))->andReturnFalse();
        $disk->shouldReceive('put')->once()->with($cataloguePath, 'old catalogue')->andReturnTrue();
        $disk->shouldReceive('put')->once()->with($detailPath, 'old detail')->andReturnTrue();
        Storage::shouldReceive('disk')->with('media')->andReturn($disk);

        $this->actingAs($user)
            ->post(route('entity-media.store', [
                'mediaType' => 'ingredient-photo',
                'entity' => $ingredient,
            ]), ['image' => UploadedFile::fake()->image('replacement.png')])
            ->assertSessionHasErrors([
                'image' => 'Obrázek se nepodařilo uložit. Zkuste to znovu.',
            ]);
    }

    public function test_store_deletion_rolls_back_when_media_cleanup_fails(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $directory = "family-media/{$family->id}/store-logo/{$store->id}";
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with($directory)->andReturnTrue();
        $disk->allows('allFiles')->with($directory)->andReturn([]);
        $disk->shouldReceive('deleteDirectory')->once()->with($directory)->andReturnFalse();
        Storage::shouldReceive('disk')->with('media')->andReturn($disk);

        try {
            $this->actingAs($user)->delete(route('stores.destroy', $store));
            $this->fail('The failed media cleanup should abort Store deletion.');
        } catch (RuntimeException) {
            $this->assertModelExists($store);
        }
    }

    public function test_family_deletion_rolls_back_when_media_cleanup_fails(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $directory = "family-media/{$family->id}";
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with($directory)->andReturnTrue();
        $disk->allows('allFiles')->with($directory)->andReturn([]);
        $disk->shouldReceive('deleteDirectory')->once()->with($directory)->andReturnFalse();
        Storage::shouldReceive('disk')->with('media')->andReturn($disk);

        try {
            $this->actingAs($user)
                ->delete(route('current-family.destroy'), ['family_name' => $family->name]);
            $this->fail('The failed media cleanup should abort Family deletion.');
        } catch (RuntimeException) {
            $this->assertModelExists($family);
        }
    }

    public function test_guests_and_unknown_media_routes_cannot_access_private_images(): void
    {
        $this->post(route('entity-media.store', ['mediaType' => 'store-logo', 'entity' => 1]))
            ->assertRedirect(route('login'));
        $this->get(route('entity-media.show', [
            'mediaType' => 'store-logo',
            'entity' => 1,
            'variant' => 'catalogue',
        ]))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers('Domov', $user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();

        $this->actingAs($user)
            ->post(route('entity-media.store', ['mediaType' => 'video', 'entity' => $store]))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('entity-media.show', [
                'mediaType' => 'store-logo',
                'entity' => $store,
                'variant' => 'original',
            ]))
            ->assertNotFound();
    }

    private function mediaPath(Family $family, string $type, int $entityId, string $variant): string
    {
        return "family-media/{$family->id}/{$type}/{$entityId}/{$type}-{$entityId}-{$variant}.webp";
    }

    private function mediaUrl(string $type, int $entityId, string $variant): string
    {
        return route('entity-media.show', [
            'mediaType' => $type,
            'entity' => $entityId,
            'variant' => $variant,
        ]);
    }

    private function assertWebp(string $bytes, int $width, int $height): void
    {
        $this->assertSame('RIFF', substr($bytes, 0, 4));
        $this->assertSame('WEBP', substr($bytes, 8, 4));
        $image = imagecreatefromstring($bytes);
        $this->assertInstanceOf(GdImage::class, $image);
        $this->assertSame($width, imagesx($image));
        $this->assertSame($height, imagesy($image));
        imagedestroy($image);
    }

    private function webpBytes(): string
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagewebp($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        $this->assertIsString($bytes);

        return $bytes;
    }

    private function animatedWebpBytes(): string
    {
        $static = $this->webpBytes();
        $animationChunk = 'ANIM' . pack('V', 6) . str_repeat("\0", 6);
        $contents = substr($static, 0, 12) . $animationChunk . substr($static, 12);

        return substr_replace($contents, pack('V', strlen($contents) - 8), 4, 4);
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

    private function createFamilyWithMembers(string $name, User ...$users): Family
    {
        $family = Family::factory()->create(['name' => $name]);

        foreach ($users as $user) {
            FamilyMembership::factory()->create([
                'family_id' => $family->id,
                'user_id' => $user->id,
            ]);
        }

        return $family;
    }

    private function selectCurrentFamily(User $user, Family $family): void
    {
        $user->forceFill(['current_family_id' => $family->id])->save();
    }
}
