<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentTemplates;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_branding_and_generated_document_uses_it(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $project = Project::create([
            'owner_id' => $admin->id,
            'name' => 'Branded Project',
            'status' => 'writing',
        ]);
        $section = ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Objectives',
            'content' => 'A clear objective.',
        ]);

        $this->actingAs($admin);

        Livewire::test(DocumentTemplates::class)
            ->set('brandName', 'Scoala de Jocuri Association')
            ->set('legalName', 'Scoala de Jocuri Association')
            ->set('vatNumber', 'RO12345678')
            ->set('legalAddress', 'Cluj-Napoca, Romania')
            ->set('headerText', 'Official Erasmus+ record')
            ->set('footerText', 'Confidential organisational document')
            ->set('signatoryName', 'Ileana Codrea')
            ->set('signatoryRole', 'President')
            ->set('accentColor', '#123456')
            ->set('logo', UploadedFile::fake()->image('logo.png', 240, 80))
            ->call('save')
            ->assertHasNoErrors();

        $admin->refresh();
        $this->assertSame('Scoala de Jocuri Association', data_get($admin->document_settings, 'brand_name'));
        $this->assertSame('#123456', data_get($admin->document_settings, 'accent_color'));
        $this->assertSame('RO12345678', data_get($admin->document_settings, 'vat_number'));
        Storage::disk('local')->assertExists(data_get($admin->document_settings, 'logo_path'));

        $html = view('pdf.application-report', [
            'project' => $project->refresh()->load('ownerAccount'),
            'sections' => collect([$section]),
        ])->render();
        $this->assertStringContainsString('Scoala de Jocuri Association', $html);
        $this->assertStringContainsString('Official Erasmus+ record', $html);
        $this->assertStringContainsString('#123456', $html);
        $this->assertStringStartsWith('%PDF', Pdf::loadView('pdf.application-report', [
            'project' => $project->refresh()->load('ownerAccount'),
            'sections' => collect([$section]),
        ])->output());
    }

    public function test_regular_user_can_open_account_document_template_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertTrue(DocumentTemplates::canAccess());
        $this->get(DocumentTemplates::getUrl())
            ->assertOk();
    }
}
