<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Tests\TestCase;

class UploadValidationResponseTest extends TestCase
{
    public function test_upload_validation_returns_json_instead_of_redirecting_to_an_html_page(): void
    {
        Storage::fake('tmp-for-tests');
        $url = app(GenerateSignedUploadUrl::class)->forLocal();

        $this->withHeaders(['Accept' => 'application/json'])
            ->post($url, ['files' => [UploadedFile::fake()->create('too-large.pdf', 51 * 1024, 'application/pdf')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files.0');
    }
}
