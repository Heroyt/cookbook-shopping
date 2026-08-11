<?php

declare(strict_types=1);

namespace App\Cookbook\Services;

use App\Cookbook\Exceptions\InvalidEntityMedia;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\Models\Family;
use GdImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class EntityMediaStorage
{
    public function store(Family $family, EntityMediaType $type, int $entityId, UploadedFile $upload): void
    {
        $contents = file_get_contents($upload->getRealPath());

        if ( ! is_string($contents)) {
            throw new InvalidEntityMedia('The uploaded image could not be read.');
        }

        $image = $this->decode($contents);

        try {
            $variants = $this->encodeVariants($type, $image);
        } finally {
            imagedestroy($image);
        }

        $writes = [];
        foreach ($variants as $variant => $bytes) {
            $writes[$this->path($family->id, $type, $entityId, $variant)] = $bytes;
        }

        $this->replaceAtomically($writes);
    }

    public function url(Family $family, EntityMediaType $type, int $entityId, string $variant = 'catalogue'): ?string
    {
        if ( ! $this->variantExists($type, $variant)) {
            return null;
        }

        $path = $this->path($family->id, $type, $entityId, $variant);

        if ( ! $this->disk()->exists($path)) {
            return null;
        }

        return route('entity-media.show', [
            'mediaType' => $type->value,
            'entity' => $entityId,
            'variant' => $variant,
        ]);
    }

    public function response(Family $family, EntityMediaType $type, int $entityId, string $variant): StreamedResponse
    {
        if ( ! $this->variantExists($type, $variant)) {
            abort(404);
        }

        $path = $this->path($family->id, $type, $entityId, $variant);

        if ( ! $this->disk()->exists($path)) {
            abort(404);
        }

        return $this->disk()->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteEntity(int $familyId, EntityMediaType $type, int $entityId): void
    {
        $directory = $this->entityDirectory($familyId, $type, $entityId);

        if ($this->disk()->exists($directory) && ! $this->disk()->deleteDirectory($directory)) {
            throw new RuntimeException('Entity media could not be deleted.');
        }
    }

    public function deleteFamily(int $familyId): void
    {
        $directory = $this->familyDirectory($familyId);

        if ($this->disk()->exists($directory) && ! $this->disk()->deleteDirectory($directory)) {
            throw new RuntimeException('Family media could not be deleted.');
        }
    }

    public function path(int $familyId, EntityMediaType $type, int $entityId, string $variant): string
    {
        return $this->entityDirectory($familyId, $type, $entityId)
            . "/{$type->value}-{$entityId}-{$variant}.webp";
    }

    private function familyDirectory(int $familyId): string
    {
        $root = config('media.root');

        if ( ! is_string($root) || $root === '') {
            throw new LogicException('Media root configuration must be a non-empty string.');
        }

        return trim($root, '/') . "/{$familyId}";
    }

    private function entityDirectory(int $familyId, EntityMediaType $type, int $entityId): string
    {
        return $this->familyDirectory($familyId) . "/{$type->value}/{$entityId}";
    }

    private function disk(): FilesystemAdapter
    {
        $disk = config('media.disk');

        if ( ! is_string($disk) || $disk === '') {
            throw new LogicException('Media disk configuration must be a non-empty string.');
        }

        return Storage::disk($disk);
    }

    private function decode(string $contents): GdImage
    {
        if ( ! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new LogicException('The GD extension with WebP support is required for entity media.');
        }

        $info = @getimagesizefromstring($contents);
        $mime = is_array($info) ? $info['mime'] : null;

        if ( ! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new InvalidEntityMedia('The uploaded image is not a decodable JPEG or PNG image.');
        }

        set_error_handler(static fn (int $severity, string $message): bool => true);
        try {
            $image = imagecreatefromstring($contents);
        } finally {
            restore_error_handler();
        }

        if ( ! $image instanceof GdImage) {
            throw new InvalidEntityMedia('The uploaded image is not decodable.');
        }

        return $image;
    }

    /** @return array<string, string> */
    private function encodeVariants(EntityMediaType $type, GdImage $source): array
    {
        $configuration = config("media.types.{$type->value}.variants");

        if ( ! is_array($configuration) || $configuration === []) {
            throw new LogicException("No media variants are configured for {$type->value}.");
        }

        $encoded = [];
        foreach ($configuration as $variant => $dimensions) {
            if ( ! is_string($variant) || ! is_array($dimensions)) {
                throw new LogicException("Invalid media variant configuration for {$type->value}.");
            }

            $width = $dimensions['width'] ?? null;
            $height = $dimensions['height'] ?? null;
            if ( ! is_int($width) || $width < 1 || ! is_int($height) || $height < 1) {
                throw new LogicException("Invalid dimensions for {$type->value} {$variant}.");
            }

            $encoded[$variant] = $this->encodeWebp($source, $width, $height);
        }

        return $encoded;
    }

    private function encodeWebp(GdImage $source, int $maximumWidth, int $maximumHeight): string
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, $maximumWidth / $sourceWidth, $maximumHeight / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($width, $height);

        if ( ! $target instanceof GdImage) {
            throw new RuntimeException('A normalized image canvas could not be created.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);

        if ( ! is_int($transparent)) {
            imagedestroy($target);

            throw new RuntimeException('A transparent image colour could not be allocated.');
        }

        imagefill($target, 0, 0, $transparent);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        try {
            ob_start();
            $quality = config('media.webp_quality');
            $success = imagewebp($target, null, is_int($quality) ? $quality : 82);
            $bytes = ob_get_clean();
        } catch (Throwable $exception) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $exception;
        } finally {
            imagedestroy($target);
        }

        if ( ! $success || ! is_string($bytes)) {
            throw new RuntimeException('A normalized WebP image could not be encoded.');
        }

        return $bytes;
    }

    /** @param array<string, string> $writes */
    private function replaceAtomically(array $writes): void
    {
        $disk = $this->disk();
        $previous = [];

        foreach (array_keys($writes) as $path) {
            $previous[$path] = $disk->exists($path) ? $disk->get($path) : null;
        }

        try {
            foreach ($writes as $path => $bytes) {
                if ( ! $disk->put($path, $bytes)) {
                    throw new RuntimeException('A normalized image variant could not be stored.');
                }
            }
        } catch (Throwable $exception) {
            foreach ($previous as $path => $bytes) {
                if (is_string($bytes)) {
                    $disk->put($path, $bytes);
                } else {
                    $disk->delete($path);
                }
            }

            throw $exception;
        }
    }

    private function variantExists(EntityMediaType $type, string $variant): bool
    {
        return is_array(config("media.types.{$type->value}.variants.{$variant}"));
    }
}
