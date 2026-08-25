<?php

namespace App\Support;

final readonly class StoredFileSwapResult
{
    public function __construct(
        public mixed $value,
        public ?StoredFileReference $replacedFile = null,
    ) {}
}
