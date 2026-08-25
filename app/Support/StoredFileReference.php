<?php

namespace App\Support;

final readonly class StoredFileReference
{
    public function __construct(
        public string $disk,
        public string $path,
        public ?int $size = null,
    ) {}

    public static function from(?string $disk, ?string $path, ?int $size = null): ?self
    {
        if (! filled($path)) {
            return null;
        }

        return new self($disk ?: 'local', $path, $size);
    }

    public function isSameLocationAs(self $other): bool
    {
        return $this->disk === $other->disk && $this->path === $other->path;
    }
}
