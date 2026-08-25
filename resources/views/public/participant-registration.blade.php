<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Participant form — MobilityCloud</title>
    <style>
        :root { color-scheme: light; --brand:#4f46e5; --ink:#111827; --muted:#667085; --line:#e5e7eb; --bg:#f7f8fb; }
        * { box-sizing:border-box; }
        body { margin:0;font-family:Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;background:radial-gradient(circle at top left, rgba(79,70,229,.12), transparent 34rem), var(--bg);color:var(--ink); }
        .wrap { width:min(920px, calc(100% - 32px));margin:0 auto;padding:42px 0 56px; }
        .brand { display:flex;align-items:center;gap:.65rem;font-weight:850;font-size:1.05rem;margin-bottom:1.4rem; }
        .mark { display:grid;place-items:center;width:34px;height:34px;border-radius:12px;background:#111827;color:#fff;letter-spacing:-.04em; }
        .card { background:#fff;border:1px solid rgba(17,24,39,.08);box-shadow:0 24px 70px rgba(15,23,42,.08);border-radius:28px;overflow:hidden; }
        .hero { padding:34px 36px;border-bottom:1px solid var(--line);background:linear-gradient(135deg, rgba(79,70,229,.08), rgba(14,165,233,.05)); }
        .eyebrow { color:var(--brand);font-size:.78rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em;margin:0 0 .55rem; }
        h1 { font-size:clamp(2rem, 4vw, 3rem);line-height:1.03;margin:0;letter-spacing:-.05em; }
        .subtitle { color:var(--muted);font-size:1rem;line-height:1.6;margin:.85rem 0 0;max-width:42rem; }
        .body { padding:30px 36px 34px; }
        .status { padding:14px 16px;border-radius:16px;margin-bottom:22px;font-weight:750;font-size:.92rem; }
        .status.ok { color:#047857;background:#ecfdf5;border:1px solid #bbf7d0; }
        .status.closed { color:#92400e;background:#fffbeb;border:1px solid #fde68a; }
        .grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px; }
        .full { grid-column:1 / -1; }
        label { display:block;font-size:.72rem;font-weight:850;text-transform:uppercase;letter-spacing:.055em;color:#667085;margin-bottom:6px; }
        input, select, textarea { width:100%;border:1px solid #d0d5dd;border-radius:13px;background:#fff;padding:12px 13px;font:inherit;font-size:.95rem;color:#111827;outline:none;transition:.15s border-color,.15s box-shadow; }
        textarea { min-height:90px;resize:vertical; }
        input:focus, select:focus, textarea:focus { border-color:var(--brand);box-shadow:0 0 0 4px rgba(79,70,229,.12); }
        .section { margin:26px 0 14px;font-size:.82rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#111827; }
        .check { display:flex;align-items:flex-start;gap:.6rem;padding:13px 14px;border:1px solid var(--line);border-radius:14px;background:#fafafa; }
        .check input { width:auto;margin-top:.2rem;accent-color:var(--brand); }
        .mobility-choice { display:flex;align-items:flex-start;gap:.7rem;padding:13px 14px;border:1px solid var(--line);border-radius:14px;background:#fafafa;cursor:pointer; }
        .mobility-choice + .mobility-choice { margin-top:.6rem; }
        .mobility-choice input { width:auto;margin-top:.2rem;accent-color:var(--brand); }
        .mobility-choice strong,.mobility-choice small { display:block; }
        .mobility-choice small { color:var(--muted);margin-top:.16rem; }
        .mobility-choice.is-hidden { display:none; }
        .mobility-choice.is-waiting { opacity:.55;cursor:not-allowed; }
        .locked-mobility { padding:14px 16px;border:1px solid #c7d2fe;border-radius:15px;background:#eef2ff;color:#3730a3;font-size:.92rem;line-height:1.5; }
        .error { color:#dc2626;font-size:.78rem;margin-top:5px; }
        .actions { display:flex;align-items:center;justify-content:flex-end;gap:.8rem;margin-top:28px; }
        button { border:0;border-radius:14px;background:var(--brand);color:#fff;padding:13px 20px;font:inherit;font-weight:850;cursor:pointer;box-shadow:0 12px 30px rgba(79,70,229,.22); }
        button:hover { background:#4338ca; }
        .fine { color:#667085;font-size:.82rem;line-height:1.55;margin-top:18px; }
        @media (max-width:720px) { .wrap { width:min(100% - 20px, 920px);padding-top:20px; } .hero,.body { padding:24px 20px; } .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="brand"><span class="mark">M</span> MobilityCloud</div>

        <section class="card">
            <div class="hero">
                <p class="eyebrow">Participant form</p>
                <h1>{{ $project?->name ?? 'Project participant registration' }}</h1>
                <p class="subtitle">Please complete the form below. Your answers will be sent directly to the project team and stored in the participant register.</p>
            </div>

            <div class="body">
                @if(session('status'))
                    <div class="status ok">{{ session('status') }}</div>
                @endif

                @if($closed || ! $project)
                    <div class="status closed">This participant form is currently closed. Please contact the project team for a new link.</div>
                @elseif(count($organisations) === 0)
                    <div class="status closed">This form cannot be used yet because the project team has not configured the participating organisations.</div>
                @else
                    <form method="POST" action="{{ route('public.participant-registration.store', $registrationToken) }}">
                        @csrf

                        <div class="section">Identity</div>
                        <div class="grid">
                            <div class="full">
                                <label for="complete_name">Complete name *</label>
                                <input id="complete_name" name="complete_name" value="{{ old('complete_name') }}" autocomplete="name" required>
                                @error('complete_name') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label for="partner_organisation">Organisation *</label>
                                <select id="partner_organisation" name="partner_organisation" required>
                                    <option value="">Choose organisation</option>
                                    @foreach($organisations as $organisation)
                                        <option value="{{ $organisation['name'] }}" @selected(old('partner_organisation') === $organisation['name'])>{{ $organisation['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('partner_organisation') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label for="birth_date">Birth date</label>
                                <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}">
                                @error('birth_date') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label for="nationality">Nationality</label>
                                <input id="nationality" name="nationality" value="{{ old('nationality') }}" autocomplete="country-name">
                                @error('nationality') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Prefer not to say</option>
                                    <option value="female" @selected(old('gender') === 'female')>Female</option>
                                    <option value="male" @selected(old('gender') === 'male')>Male</option>
                                    <option value="other" @selected(old('gender') === 'other')>Other</option>
                                    <option value="undisclosed" @selected(old('gender') === 'undisclosed')>Prefer not to say</option>
                                </select>
                                @error('gender') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        @if($lockedMobility)
                            <div class="section">Your mobility</div>
                            <div class="locked-mobility">
                                <strong>{{ $lockedMobility->name }}</strong><br>
                                This form is dedicated to this mobility. Your registration will be added here automatically.
                            </div>
                        @elseif($mobilities->isNotEmpty())
                            <div class="section">Mobilities *</div>
                            <p id="mobility-help" class="subtitle" style="margin:0 0 .9rem;font-size:.9rem;">Choose your organisation first. We will show only its available options. Select every mobility in which you will take part; you may choose more than one.</p>
                            @foreach($mobilities as $mobility)
                                <label class="mobility-choice" for="mobility-{{ $mobility->id }}" data-mobility-id="{{ $mobility->id }}">
                                    <input id="mobility-{{ $mobility->id }}" type="checkbox" name="mobility_ids[]" value="{{ $mobility->id }}" @checked(in_array($mobility->id, old('mobility_ids', [])))>
                                    <span>
                                        <strong>{{ $mobility->name }}</strong>
                                        @if($mobility->start_date || $mobility->end_date)
                                            <small>{{ $mobility->start_date?->format('d M Y') ?: 'Date to be confirmed' }} – {{ $mobility->end_date?->format('d M Y') ?: 'Date to be confirmed' }}</small>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                            @error('mobility_ids') <div class="error">{{ $message }}</div> @enderror
                            @error('mobility_ids.*') <div class="error">{{ $message }}</div> @enderror
                        @endif

                        <div class="section">Contact</div>
                        <div class="grid">
                            <div>
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">
                                @error('email') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label for="phone">Phone</label>
                                <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                                @error('phone') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="full">
                                <label for="address">Address</label>
                                <input id="address" name="address" value="{{ old('address') }}" autocomplete="street-address">
                                @error('address') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="section">Needs and relevant information</div>
                        <div class="grid">
                            <div>
                                <label for="medical_conditions">Medical conditions</label>
                                <textarea id="medical_conditions" name="medical_conditions">{{ old('medical_conditions') }}</textarea>
                                @error('medical_conditions') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label for="allergies">Allergies</label>
                                <textarea id="allergies" name="allergies">{{ old('allergies') }}</textarea>
                                @error('allergies') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label for="dietary_restrictions">Dietary restrictions</label>
                                <textarea id="dietary_restrictions" name="dietary_restrictions">{{ old('dietary_restrictions') }}</textarea>
                                @error('dietary_restrictions') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label for="special_needs">Special needs</label>
                                <textarea id="special_needs" name="special_needs">{{ old('special_needs') }}</textarea>
                                @error('special_needs') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <label class="check full">
                                <input type="checkbox" name="fewer_opportunities" value="1" @checked(old('fewer_opportunities'))>
                                <span>I identify as a participant with fewer opportunities or I may need additional support from the project team.</span>
                            </label>
                        </div>

                        <div class="section">Legal guardian, if applicable</div>
                        <div class="grid">
                            <div>
                                <label for="guardian_name">Guardian name</label>
                                <input id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}">
                                @error('guardian_name') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label for="guardian_contact">Guardian contact</label>
                                <input id="guardian_contact" name="guardian_contact" value="{{ old('guardian_contact') }}">
                                @error('guardian_contact') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <p class="fine">By submitting this form you send your participant details to the project organiser. The organiser is responsible for using the information only for project management and reporting purposes.</p>

                        <div class="actions">
                            <button type="submit">Submit participant form</button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </main>
    @if(! $lockedMobility && $mobilities->isNotEmpty())
        <script>
            (() => {
                const organisation = document.getElementById('partner_organisation');
                const choices = Array.from(document.querySelectorAll('[data-mobility-id]'));
                const eligibility = @json($mobilityEligibility);

                const refreshMobilities = () => {
                    const selectedOrganisation = organisation?.value || '';

                    choices.forEach((choice) => {
                        const checkbox = choice.querySelector('input[type="checkbox"]');
                        const allowed = eligibility[choice.dataset.mobilityId] || [];
                        const compatible = selectedOrganisation !== '' && allowed.includes(selectedOrganisation);

                        choice.classList.toggle('is-hidden', selectedOrganisation !== '' && ! compatible);
                        choice.classList.toggle('is-waiting', selectedOrganisation === '');
                        checkbox.disabled = ! compatible;

                        if (selectedOrganisation !== '' && ! compatible) {
                            checkbox.checked = false;
                        }
                    });
                };

                organisation?.addEventListener('change', refreshMobilities);
                refreshMobilities();
            })();
        </script>
    @endif
</body>
</html>
