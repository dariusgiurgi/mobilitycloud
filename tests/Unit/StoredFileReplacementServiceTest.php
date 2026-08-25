<?php

namespace Tests\Unit;

use App\Jobs\DeleteStoredFile;
use App\Services\StoredFileReplacementService;
use App\Support\StoredFileReference;
use App\Support\StoredFileSwapResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class StoredFileReplacementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put('documents/old.pdf', 'old');
    }

    public function test_it_deletes_the_previous_file_after_its_owned_database_transaction_commits(): void
    {
        $value = app(StoredFileReplacementService::class)->replace(
            disk: 'local',
            path: 'documents/new.pdf',
            write: fn (): bool => Storage::disk('local')->put('documents/new.pdf', 'new file'),
            swap: fn (StoredFileReference $newFile): StoredFileSwapResult => new StoredFileSwapResult(
                value: $newFile->path,
                replacedFile: new StoredFileReference('local', 'documents/old.pdf', 3),
            ),
            expectedSize: 8,
        );

        $this->assertSame('documents/new.pdf', $value);
        Storage::disk('local')->assertExists('documents/new.pdf');
        Storage::disk('local')->assertMissing('documents/old.pdf');
    }

    public function test_it_waits_for_the_surrounding_transaction_before_deleting_the_previous_file(): void
    {
        DB::beginTransaction();

        try {
            $value = app(StoredFileReplacementService::class)->replace(
                disk: 'local',
                path: 'documents/new.pdf',
                write: fn (): bool => Storage::disk('local')->put('documents/new.pdf', 'new file'),
                swap: fn (StoredFileReference $newFile): StoredFileSwapResult => new StoredFileSwapResult(
                    $newFile->path,
                    new StoredFileReference('local', 'documents/old.pdf', 3),
                ),
                expectedSize: 8,
            );

            $this->assertSame('documents/new.pdf', $value);
            Storage::disk('local')->assertExists('documents/new.pdf');
            Storage::disk('local')->assertExists('documents/old.pdf');

            DB::commit();

            Storage::disk('local')->assertMissing('documents/old.pdf');
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    public function test_it_removes_the_new_file_if_the_surrounding_transaction_rolls_back(): void
    {
        DB::beginTransaction();

        app(StoredFileReplacementService::class)->replace(
            disk: 'local',
            path: 'documents/new.pdf',
            write: fn (): bool => Storage::disk('local')->put('documents/new.pdf', 'new file'),
            swap: fn (StoredFileReference $newFile): StoredFileSwapResult => new StoredFileSwapResult(
                $newFile->path,
                new StoredFileReference('local', 'documents/old.pdf', 3),
            ),
            expectedSize: 8,
        );

        Storage::disk('local')->assertExists('documents/new.pdf');
        Storage::disk('local')->assertExists('documents/old.pdf');

        DB::rollBack();

        Storage::disk('local')->assertMissing('documents/new.pdf');
        Storage::disk('local')->assertExists('documents/old.pdf');
    }

    public function test_it_preserves_the_previous_file_and_removes_the_new_file_when_the_database_swap_fails(): void
    {
        try {
            app(StoredFileReplacementService::class)->replace(
                disk: 'local',
                path: 'documents/new.pdf',
                write: fn (): bool => Storage::disk('local')->put('documents/new.pdf', 'new file'),
                swap: function (): never {
                    throw new RuntimeException('Simulated database failure.');
                },
                expectedSize: 8,
            );

            $this->fail('The simulated database failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure.', $exception->getMessage());
        }

        Storage::disk('local')->assertExists('documents/old.pdf');
        Storage::disk('local')->assertMissing('documents/new.pdf');
    }

    public function test_it_rejects_and_removes_a_file_that_fails_size_verification_before_the_database_swap(): void
    {
        $swapWasCalled = false;

        try {
            app(StoredFileReplacementService::class)->replace(
                disk: 'local',
                path: 'documents/new.pdf',
                write: fn (): bool => Storage::disk('local')->put('documents/new.pdf', 'short'),
                swap: function () use (&$swapWasCalled): StoredFileSwapResult {
                    $swapWasCalled = true;

                    return new StoredFileSwapResult(null);
                },
                expectedSize: 100,
            );

            $this->fail('The failed verification was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The stored file size does not match the uploaded file.', $exception->getMessage());
        }

        $this->assertFalse($swapWasCalled);
        Storage::disk('local')->assertExists('documents/old.pdf');
        Storage::disk('local')->assertMissing('documents/new.pdf');
    }

    public function test_retry_job_deletes_an_obsolete_file_idempotently(): void
    {
        $job = new DeleteStoredFile('local', 'documents/old.pdf');

        $job->handle();
        $job->handle();

        Storage::disk('local')->assertMissing('documents/old.pdf');
    }
}
