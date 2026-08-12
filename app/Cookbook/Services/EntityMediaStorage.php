<?php

declare(strict_types=1);

namespace App\Cookbook\Services;

use App\Cookbook\Exceptions\AnimatedEntityMedia;
use App\Cookbook\Exceptions\InvalidEntityMedia;
use App\Cookbook\Values\EntityMediaDeletion;
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

        $this->replaceAtomically($this->entityDirectory($family->id, $type, $entityId), $writes);
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

    public function deleteEntityWithBackup(
        int $familyId,
        EntityMediaType $type,
        int $entityId,
    ): EntityMediaDeletion {
        return $this->deleteDirectoryWithBackup(
            $this->entityDirectory($familyId, $type, $entityId),
            'Entity media could not be deleted.',
        );
    }

    public function deleteFamilyWithBackup(int $familyId): EntityMediaDeletion
    {
        return $this->deleteDirectoryWithBackup(
            $this->familyDirectory($familyId),
            'Family media could not be deleted.',
        );
    }

    public function restore(EntityMediaDeletion $deletion): void
    {
        $disk = $this->disk();

        foreach ($deletion->files as $path => $bytes) {
            if ( ! $disk->put($path, $bytes)) {
                throw new RuntimeException('Deleted entity media could not be restored.');
            }
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

        if (str_starts_with($contents, 'RIFF') && substr($contents, 8, 4) === 'WEBP'
            && ! $this->hasCompleteWebpStructure($contents)) {
            throw new InvalidEntityMedia('The uploaded WebP image is structurally incomplete.');
        }

        $info = @getimagesizefromstring($contents);
        $mime = is_array($info) ? $info['mime'] : null;

        if ( ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new InvalidEntityMedia('The uploaded image is not a decodable JPEG, PNG, or WebP image.');
        }

        $width = $info[0];
        $height = $info[1];
        if ( ! $this->dimensionsAreSafe($width, $height)) {
            throw new InvalidEntityMedia('The uploaded image exceeds the safe decoded image limits.');
        }

        if ( ! $this->hasCompleteStructure($contents, $mime)) {
            throw new InvalidEntityMedia('The uploaded image is structurally incomplete.');
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

    private function dimensionsAreSafe(int $width, int $height): bool
    {
        if ($width < 1 || $height < 1) {
            return false;
        }

        $maximumWidth = config('media.max_width');
        $maximumHeight = config('media.max_height');
        $maximumPixels = config('media.max_pixels');

        if ( ! is_int($maximumWidth) || $maximumWidth < 1
            || ! is_int($maximumHeight) || $maximumHeight < 1
            || ! is_int($maximumPixels) || $maximumPixels < 1) {
            throw new LogicException('Media dimension limits must be positive integers.');
        }

        return $width <= $maximumWidth
            && $height <= $maximumHeight
            && $width <= intdiv($maximumPixels, $height);
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
    private function replaceAtomically(string $directory, array $writes): void
    {
        $disk = $this->disk();
        $previous = $this->readFiles($disk, $directory);

        try {
            foreach ($writes as $path => $bytes) {
                if ( ! $disk->put($path, $bytes)) {
                    throw new RuntimeException('A normalized image variant could not be stored.');
                }
            }

            $stalePaths = array_values(array_diff(array_keys($previous), array_keys($writes)));

            if ($stalePaths !== [] && ! $disk->delete($stalePaths)) {
                throw new RuntimeException('An obsolete image variant could not be deleted.');
            }
        } catch (Throwable $exception) {
            foreach (array_keys($writes) as $path) {
                if ( ! array_key_exists($path, $previous)) {
                    $disk->delete($path);
                }
            }

            foreach ($previous as $path => $bytes) {
                $disk->put($path, $bytes);
            }

            throw $exception;
        }
    }

    private function deleteDirectoryWithBackup(string $directory, string $failureMessage): EntityMediaDeletion
    {
        $disk = $this->disk();
        if ( ! $disk->exists($directory)) {
            return new EntityMediaDeletion([]);
        }

        $files = $this->readFiles($disk, $directory);
        $deletion = new EntityMediaDeletion($files);

        if ( ! $disk->deleteDirectory($directory)) {
            $this->restore($deletion);

            throw new RuntimeException($failureMessage);
        }

        return $deletion;
    }

    /** @return array<string, string> */
    private function readFiles(FilesystemAdapter $disk, string $directory): array
    {
        $files = [];

        foreach ($disk->allFiles($directory) as $path) {
            $bytes = $disk->get($path);

            if ( ! is_string($bytes)) {
                throw new RuntimeException('Existing entity media could not be read.');
            }

            $files[$path] = $bytes;
        }

        return $files;
    }

    private function hasCompleteStructure(string $contents, string $mime): bool
    {
        if ($mime === 'image/jpeg') {
            return str_starts_with($contents, "\xFF\xD8") && str_ends_with($contents, "\xFF\xD9");
        }

        if ($mime === 'image/png') {
            return $this->hasCompletePngStructure($contents);
        }

        return $this->hasCompleteWebpStructure($contents);
    }

    private function hasCompleteWebpStructure(string $contents): bool
    {
        $length = strlen($contents);
        if ($length < 20
            || substr($contents, 0, 4) !== 'RIFF'
            || substr($contents, 8, 4) !== 'WEBP') {
            return false;
        }

        $declaredSizeData = unpack('Vsize', substr($contents, 4, 4));
        $declaredSize = is_array($declaredSizeData) ? $declaredSizeData['size'] : null;
        if ( ! is_int($declaredSize) || $declaredSize !== $length - 8) {
            return false;
        }

        $offset = 12;
        $hasImageData = false;
        while ($offset + 8 <= $length) {
            $type = substr($contents, $offset, 4);
            $chunkSizeData = unpack('Vsize', substr($contents, $offset + 4, 4));
            $chunkSize = is_array($chunkSizeData) ? $chunkSizeData['size'] : null;
            if ( ! is_int($chunkSize) || $chunkSize < 0) {
                return false;
            }

            $paddedSize = $chunkSize + ($chunkSize % 2);
            if ($offset + 8 + $paddedSize > $length) {
                return false;
            }

            $chunkData = substr($contents, $offset + 8, $chunkSize);
            if ($type === 'ANIM' || $type === 'ANMF'
                || ($type === 'VP8X' && $chunkData !== '' && (ord($chunkData[0]) & 0x02) !== 0)) {
                throw new AnimatedEntityMedia('Animated WebP images are not supported.');
            }

            if ($type === 'VP8 ' || $type === 'VP8L' || $type === 'VP8X') {
                $hasImageData = true;
            }

            $offset += 8 + $paddedSize;
        }

        return $hasImageData && $offset === $length;
    }

    private function hasCompletePngStructure(string $contents): bool
    {
        if ( ! str_starts_with($contents, "\x89PNG\r\n\x1A\n")) {
            return false;
        }

        $offset = 8;
        $length = strlen($contents);
        $firstChunk = true;

        while ($offset + 12 <= $length) {
            $chunkLengthData = unpack('Nlength', substr($contents, $offset, 4));
            $chunkLength = is_array($chunkLengthData) ? $chunkLengthData['length'] : null;

            if ( ! is_int($chunkLength) || $chunkLength < 0 || $offset + 12 + $chunkLength > $length) {
                return false;
            }

            $type = substr($contents, $offset + 4, 4);
            $data = substr($contents, $offset + 8, $chunkLength);
            $expectedCrc = substr($contents, $offset + 8 + $chunkLength, 4);
            $actualCrc = hex2bin(hash('crc32b', $type . $data));

            if ( ! is_string($actualCrc) || ! hash_equals($expectedCrc, $actualCrc)) {
                return false;
            }

            if ($firstChunk && $type !== 'IHDR') {
                return false;
            }

            $offset += 12 + $chunkLength;
            $firstChunk = false;

            if ($type === 'IEND') {
                return $chunkLength === 0 && $offset === $length;
            }
        }

        return false;
    }

    private function variantExists(EntityMediaType $type, string $variant): bool
    {
        return is_array(config("media.types.{$type->value}.variants.{$variant}"));
    }
}
