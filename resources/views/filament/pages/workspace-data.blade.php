<x-filament-panels::page>
    <x-ui-polish />
    <div style="max-width:760px;display:grid;gap:1rem;">
        <x-filament::section heading="Full workspace backup" description="Download one ZIP archive containing structured data and every uploaded file from active and archived projects." icon="heroicon-o-archive-box-arrow-down">
            <div style="display:grid;gap:.9rem;">
                <div class="text-gray-600 dark:text-gray-300" style="font-size:.8rem;line-height:1.65;">
                    The archive includes project settings, applications, budgets, expenses, participants, tasks, activity history, the content library, saved calculations and original or signed files. Passwords and invitation tokens are never included.
                </div>
                <div style="padding:.75rem;border-radius:.65rem;background:rgba(245,158,11,.08);font-size:.73rem;line-height:1.5;color:#92400e;">
                    Participant and financial files may contain sensitive personal data. Store the archive securely and remove old copies according to your retention policy.
                </div>
                <div>
                    <x-filament::button tag="a" :href="$this->backupUrl" icon="heroicon-o-arrow-down-tray">Download workspace backup</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
