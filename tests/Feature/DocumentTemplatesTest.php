<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentTemplates;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use App\Models\Workspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
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
        $workspace = Workspace::create(['name' => 'Brand Workspace']);
        $admin = User::factory()->create();
        $workspace->users()->attach($admin, ['role' => 'admin']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Branded Project',
            'status' => 'writing',
        ]);
        $section = ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Objectives',
            'content' => 'A clear objective.',
        ]);

        $this->actingAs($admin);
        Filament::setTenant($workspace);

        Livewire::test(DocumentTemplates::class)
            ->set('brandName', 'Roots in Motion Association')
            ->set('legalName', 'Roots in Motion Association')
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

        $workspace->refresh();
        $this->assertSame('Roots in Motion Association', $workspace->documentSetting('brand_name'));
        $this->assertSame('#123456', $workspace->documentSetting('accent_color'));
        $this->assertSame('RO12345678', $workspace->billing_vat);
        Storage::disk('local')->assertExists($workspace->document_logo_path);

        $html = view('pdf.application-report', [
            'project' => $project->load('workspace'),
            'sections' => collect([$section]),
        ])->render();
        $this->assertStringContainsString('Roots in Motion Association', $html);
        $this->assertStringContainsString('Official Erasmus+ record', $html);
        $this->assertStringContainsString('#123456', $html);
        $this->assertStringStartsWith('%PDF', Pdf::loadView('pdf.application-report', [
            'project' => $project,
            'sections' => collect([$section]),
        ])->output());
    }

    public function test_viewer_cannot_open_document_template_settings(): void
    {
        $workspace = Workspace::create(['name' => 'Protected Brand']);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        $this->assertFalse(DocumentTemplates::canAccess());
        $this->get(DocumentTemplates::getUrl(tenant: $workspace))->assertForbidden();
    }
}
