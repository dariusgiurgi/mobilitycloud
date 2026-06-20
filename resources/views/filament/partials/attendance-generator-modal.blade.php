@if($showAttendanceModal)
    <style>
        .mc-att-label { display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:4px; }
        .mc-att-input { width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:#fafafa;color:#18181b;font-size:13px; }
        .dark .mc-att-label { color:#a1a1aa; }
        .dark .mc-att-input { background:#27303f;color:#f4f4f5;border-color:rgba(148,163,184,.3); }
    </style>
    <div style="position:fixed;inset:0;z-index:60;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem;"
         wire:click.self="closeAttendanceGenerator">
        <div class="fi-section rounded-xl bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
             style="width:min(520px,100%);padding:1.4rem;">
            <h2 class="text-gray-950 dark:text-white" style="font-size:18px;font-weight:700;margin:0 0 .35rem;">Generate attendance list</h2>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:12px;margin:0 0 1.2rem;">One landscape PDF will be generated. Every organisation starts on a new page.</p>

            <div style="display:flex;flex-direction:column;gap:.85rem;">
                <div>
                    <label class="mc-att-label">Activity</label>
                    <input type="text" wire:model="attendanceActivity" class="mc-att-input">
                    @error('attendanceActivity') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;">
                    <div>
                        <label class="mc-att-label">Date</label>
                        <input type="date" wire:model="attendanceDate" class="mc-att-input">
                        @error('attendanceDate') <span class="mc-part-err">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mc-att-label">Location</label>
                        <input type="text" wire:model="attendanceLocation" class="mc-att-input" placeholder="Optional">
                        @error('attendanceLocation') <span class="mc-part-err">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem;">
                <button type="button" wire:click="closeAttendanceGenerator"
                        style="padding:9px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;">Cancel</button>
                <button type="button" wire:click="generateAttendanceSheet" wire:loading.attr="disabled"
                        style="padding:9px 16px;border-radius:8px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-weight:600;">
                    Generate PDF
                </button>
            </div>
        </div>
    </div>
@endif
