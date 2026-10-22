<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\File\File;

class MaxUploadBatchSize implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bytes = 0;
        foreach (is_array($value) ? $value : [] as $file) {
            if ($file instanceof File) {
                $bytes += $file->getSize();
            }
        }
        if ($bytes > 60 * 1024 * 1024) {
            $fail('Select at most 60 MB of files at a time. Upload larger collections in separate batches.');
        }
    }
}
