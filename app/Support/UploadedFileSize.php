<?php

namespace App\Support;

final class UploadedFileSize
{
    public static function read(mixed $upload): ?int
    {
        if (is_object($upload) && method_exists($upload, 'readStream')) {
            $stream = $upload->readStream();

            if (is_resource($stream)) {
                $stats = fstat($stream);
                fclose($stream);

                if (isset($stats['size'])) {
                    return (int) $stats['size'];
                }
            }
        }

        if (is_object($upload) && method_exists($upload, 'getRealPath')) {
            $path = $upload->getRealPath();

            if (is_string($path) && is_file($path)) {
                $size = filesize($path);

                if ($size !== false) {
                    return $size;
                }
            }
        }

        return is_object($upload) && method_exists($upload, 'getSize')
            ? (int) $upload->getSize()
            : null;
    }
}
