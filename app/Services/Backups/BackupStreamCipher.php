<?php

namespace App\Services\Backups;

use RuntimeException;

class BackupStreamCipher
{
    private const MAGIC = "MCBKP1\n";

    private const CHUNK_BYTES = 1024 * 1024;

    public function encrypt(string $sourcePath, string $targetPath, string $keyPath): array
    {
        $key = $this->readKey($keyPath);
        $source = $this->open($sourcePath, 'rb');
        $target = $this->open($targetPath, 'wb');

        try {
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);

            $this->writeAll($target, self::MAGIC.pack('N', self::CHUNK_BYTES).$header);

            $wroteFrame = false;

            while (! feof($source)) {
                $chunk = fread($source, self::CHUNK_BYTES);

                if ($chunk === false) {
                    throw new RuntimeException('Could not read the backup source while encrypting it.');
                }

                if ($chunk === '' && ! feof($source)) {
                    continue;
                }

                $tag = feof($source)
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;

                $ciphertext = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, self::MAGIC, $tag);
                $this->writeAll($target, $ciphertext);
                $wroteFrame = true;
            }

            if (! $wroteFrame) {
                $this->writeAll(
                    $target,
                    sodium_crypto_secretstream_xchacha20poly1305_push(
                        $state,
                        '',
                        self::MAGIC,
                        SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL,
                    ),
                );
            }
        } finally {
            fclose($source);
            fclose($target);
            sodium_memzero($key);
        }

        @chmod($targetPath, 0600);

        return $this->metadata($targetPath, $keyPath);
    }

    public function decrypt(string $sourcePath, string $targetPath, string $keyPath): array
    {
        $key = $this->readKey($keyPath);
        $source = $this->open($sourcePath, 'rb');
        $target = $this->open($targetPath, 'wb');

        try {
            $prefixLength = strlen(self::MAGIC) + 4;
            $prefix = $this->readExactly($source, $prefixLength);

            if (substr($prefix, 0, strlen(self::MAGIC)) !== self::MAGIC) {
                throw new RuntimeException('The external backup has an unsupported encrypted format.');
            }

            $chunkBytes = unpack('Nsize', substr($prefix, strlen(self::MAGIC), 4))['size'] ?? 0;

            if ($chunkBytes < 65536 || $chunkBytes > 16 * 1024 * 1024) {
                throw new RuntimeException('The encrypted backup has an invalid chunk size.');
            }

            $header = $this->readExactly($source, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
            $frameBytes = $chunkBytes + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
            $sawFinal = false;

            while (! feof($source)) {
                $ciphertext = fread($source, $frameBytes);

                if ($ciphertext === false) {
                    throw new RuntimeException('Could not read the encrypted backup.');
                }

                if ($ciphertext === '') {
                    break;
                }

                $pulled = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $ciphertext, self::MAGIC);

                if ($pulled === false) {
                    throw new RuntimeException('The encrypted backup is corrupt or its key is incorrect.');
                }

                [$plaintext, $tag] = $pulled;

                if ($sawFinal) {
                    throw new RuntimeException('The encrypted backup contains data after its final frame.');
                }

                $this->writeAll($target, $plaintext);

                if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    $sawFinal = true;
                }
            }

            if (! $sawFinal) {
                throw new RuntimeException('The encrypted backup is incomplete.');
            }
        } catch (\Throwable $exception) {
            @unlink($targetPath);

            throw $exception;
        } finally {
            fclose($source);
            fclose($target);
            sodium_memzero($key);
        }

        @chmod($targetPath, 0600);

        return [
            'size_bytes' => (int) filesize($targetPath),
            'sha256' => hash_file('sha256', $targetPath),
        ];
    }

    public function generateKeyFile(string $keyPath): string
    {
        if (is_file($keyPath)) {
            throw new RuntimeException('A backup encryption key already exists at '.$keyPath.'.');
        }

        $directory = dirname($keyPath);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the backup key directory.');
        }

        $key = random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
        $encoded = base64_encode($key).PHP_EOL;

        if (file_put_contents($keyPath, $encoded, LOCK_EX) === false) {
            sodium_memzero($key);

            throw new RuntimeException('Could not write the backup encryption key.');
        }

        @chmod($keyPath, 0600);
        $keyId = substr(hash('sha256', $key), 0, 16);
        sodium_memzero($key);

        return $keyId;
    }

    public function keyId(string $keyPath): string
    {
        $key = $this->readKey($keyPath);
        $keyId = substr(hash('sha256', $key), 0, 16);
        sodium_memzero($key);

        return $keyId;
    }

    private function metadata(string $path, string $keyPath): array
    {
        return [
            'size_bytes' => (int) filesize($path),
            'sha256' => hash_file('sha256', $path),
            'key_id' => $this->keyId($keyPath),
            'format' => 'mobilitycloud-secretstream-v1',
        ];
    }

    private function readKey(string $keyPath): string
    {
        if (! is_file($keyPath) || ! is_readable($keyPath)) {
            throw new RuntimeException('The backup encryption key is missing or unreadable.');
        }

        $encoded = trim((string) file_get_contents($keyPath));
        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            throw new RuntimeException('The backup encryption key has an invalid format.');
        }

        return $key;
    }

    /** @return resource */
    private function open(string $path, string $mode)
    {
        $handle = fopen($path, $mode);

        if ($handle === false) {
            throw new RuntimeException('Could not open '.basename($path).' for backup processing.');
        }

        return $handle;
    }

    /** @param resource $handle */
    private function readExactly($handle, int $bytes): string
    {
        $buffer = '';

        while (strlen($buffer) < $bytes && ! feof($handle)) {
            $chunk = fread($handle, $bytes - strlen($buffer));

            if ($chunk === false) {
                throw new RuntimeException('Could not read the encrypted backup header.');
            }

            $buffer .= $chunk;
        }

        if (strlen($buffer) !== $bytes) {
            throw new RuntimeException('The encrypted backup header is incomplete.');
        }

        return $buffer;
    }

    /** @param resource $handle */
    private function writeAll($handle, string $data): void
    {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $written = fwrite($handle, substr($data, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('Could not write the encrypted backup stream.');
            }

            $offset += $written;
        }
    }
}
