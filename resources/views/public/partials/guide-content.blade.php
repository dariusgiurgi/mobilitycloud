@php
    /*
     * Guide content is intentionally kept here instead of the marketing layout.
     * To change the order or labels later, edit this map and the matching chapter
     * below. Screenshots live in public/images/guide/.
     */
    $guideNavigation = [
        ['id' => 'side-menu', 'number' => '00', 'label' => 'Sidebar menu'],
        ['id' => 'prepare', 'number' => '01', 'label' => 'Prepare your workspace'],
        ['id' => 'create', 'number' => '02', 'label' => 'Create a project'],
        ['id' => 'write', 'number' => '03', 'label' => 'Write and review'],
        ['id' => 'table-models', 'number' => '03A', 'label' => 'Choose a table model'],
        ['id' => 'approve', 'number' => '04', 'label' => 'Declare approval'],
        ['id' => 'manage', 'number' => '05', 'label' => 'Manage implementation'],
        ['id' => 'archive', 'number' => '06', 'label' => 'Prepare final files'],
        ['id' => 'controls', 'number' => '07', 'label' => 'Button reference'],
    ];

    $gettingStarted = [
        ['icon' => '1', 'title' => 'Use a real project owner account', 'body' => 'The project owner controls access, approval status and the project record. Invite collaborators after the project exists.'],
        ['icon' => '2', 'title' => 'Keep official requirements close', 'body' => 'MobilityCloud organises the work, but official Programme Guide and National Agency requirements always remain the source of truth.'],
        ['icon' => '3', 'title' => 'Work in the project lifecycle order', 'body' => 'Start with writing and planning. After approval, continue in the same project with implementation modules.'],
    ];

    $tableModels = [
        [
            'title' => 'Activity plan',
            'use' => 'Use when the question asks what you will do, who will take part, when it happens or who is responsible.',
            'columns' => ['Activity', 'Purpose / method', 'Participants', 'Dates / duration', 'Countries', 'Responsible / output'],
        ],
        [
            'title' => 'Participant groups',
            'use' => 'Use when you need to compare national groups, participant profiles, inclusion needs or support roles.',
            'columns' => ['Group / country', 'Number', 'Profile / age', 'Fewer opportunities', 'Support / leaders'],
        ],
        [
            'title' => 'Needs and additional costs',
            'use' => 'Use for a specific need, exceptional cost, inclusion support or other item that needs a clear justification.',
            'columns' => ['Need / cost type', 'Group concerned', 'Description', 'Why necessary', 'Estimated cost'],
        ],
        [
            'title' => 'Evaluation matrix',
            'use' => 'Use when the question asks how objectives, results or impact will be measured.',
            'columns' => ['Objective', 'Indicator', 'Evidence', 'When checked', 'Responsible'],
        ],
        [
            'title' => 'Dissemination plan',
            'use' => 'Use when you must show who will receive results, through which channel and how you will prove the reach.',
            'columns' => ['Audience', 'Message / result', 'Channel', 'Timing', 'Owner', 'Evidence'],
        ],
    ];

    /* Button names map to the current core project workspaces. */
    $controlGroups = [
        [
            'title' => 'Global navigation',
            'eyebrow' => 'Available across the platform',
            'items' => [
                ['control' => 'MobilityCloud logo', 'does' => 'Returns to the project dashboard.', 'note' => 'Use it when you want to switch projects or check priorities.', 'kind' => 'navigate'],
                ['control' => 'Expand sidebar', 'does' => 'Shows or hides the left navigation labels.', 'note' => 'It only changes the layout; it does not change project data.', 'kind' => 'view'],
                ['control' => 'Global search', 'does' => 'Searches the information available to your account.', 'note' => 'Use specific project, participant or document words for faster results.', 'kind' => 'search'],
                ['control' => 'Notifications', 'does' => 'Opens account and project notifications.', 'note' => 'Review notifications after invitations, requests or important project activity.', 'kind' => 'view'],
                ['control' => 'User menu', 'does' => 'Opens your account settings and sign-out options.', 'note' => 'Use Account settings to maintain personal and billing information.', 'kind' => 'settings'],
            ],
        ],
        [
            'title' => 'Project header and tabs',
            'eyebrow' => 'Inside one project',
            'items' => [
                ['control' => 'Project access', 'does' => 'Invites collaborators and manages their access to this project.', 'note' => 'Give access only to people who need this project. Project access does not expose other projects.', 'kind' => 'manage'],
                ['control' => 'Add task', 'does' => 'Creates a manual follow-up task for the project team.', 'note' => 'Use it for responsibilities that are not automatically detected by readiness checks.', 'kind' => 'create'],
                ['control' => 'Overview', 'does' => 'Shows stage priorities, readiness, funding and project details.', 'note' => 'This is the best place to decide what to work on next.', 'kind' => 'navigate'],
                ['control' => 'Application / Budget / Mobility / Participants / Documents / Finalisation', 'does' => 'Opens the relevant project workspace without losing the project context.', 'note' => 'Tabs do not create or save anything by themselves.', 'kind' => 'navigate'],
                ['control' => 'Mark as completed', 'does' => 'Moves a project to the completed state when implementation is genuinely finished.', 'note' => 'Review final files first. This is a project status change.', 'kind' => 'important'],
            ],
        ],
        [
            'title' => 'Application workspace',
            'eyebrow' => 'Writing and review',
            'items' => [
                ['control' => 'Section list', 'does' => 'Moves between application questions and shows which sections are complete or still need work.', 'note' => 'Work from top to bottom for a coherent application.', 'kind' => 'navigate'],
                ['control' => 'Answer field', 'does' => 'Stores the text for the selected application question.', 'note' => 'Use the question guidance and character counter to keep the answer focused.', 'kind' => 'edit'],
                ['control' => 'Writing outline icon', 'does' => 'Adds a text-only answer outline with prompts for context, examples, responsibilities and results.', 'note' => 'It never creates or fills a table. Edit or delete the outline text before using it in your answer.', 'kind' => 'edit'],
                ['control' => 'Writing library', 'does' => 'Opens reusable writing content and guidance.', 'note' => 'Import or adapt content; always make sure it fits the actual project.', 'kind' => 'library'],
                ['control' => 'Add table / Column / Add row', 'does' => 'Creates an optional, editable table below the current question.', 'note' => 'Use a model from the table guide only when repeated facts are clearer in rows than in prose.', 'kind' => 'create'],
                ['control' => 'Readiness check', 'does' => 'Highlights incomplete or weak application areas before export.', 'note' => 'It is a working signal, not an official eligibility or approval decision.', 'kind' => 'review'],
                ['control' => 'Export PDF / Word', 'does' => 'Creates a working export of the application content.', 'note' => 'Use exports for internal review. Verify official submission format separately.', 'kind' => 'export'],
            ],
        ],
        [
            'title' => 'Budget control',
            'eyebrow' => 'Approved-project implementation',
            'items' => [
                ['control' => 'Export PDF', 'does' => 'Downloads a project budget summary in PDF format.', 'note' => 'Useful for internal review; it does not submit anything to an authority.', 'kind' => 'export'],
                ['control' => 'Transfer budget', 'does' => 'Moves an amount between existing budget baskets.', 'note' => 'Check the project rules and the new totals before saving the transfer.', 'kind' => 'important'],
                ['control' => 'Add expense', 'does' => 'Adds a new expense row to the selected basket.', 'note' => 'Enter the cost, date, currency and supporting information before relying on the total.', 'kind' => 'create'],
                ['control' => 'Upload file icon', 'does' => 'Attaches a supporting invoice, receipt or file to that expense.', 'note' => 'Attach the evidence while it is easy to identify, not at final-report time.', 'kind' => 'upload'],
                ['control' => 'Notes icon', 'does' => 'Adds a short internal explanation to an expense.', 'note' => 'Use it for context that is not clear from the file name or description.', 'kind' => 'edit'],
                ['control' => 'Edit basket / Add new basket', 'does' => 'Changes a basket or creates another category for budget tracking.', 'note' => 'Keep categories understandable for your own project team.', 'kind' => 'edit'],
                ['control' => 'Delete basket or expense', 'does' => 'Removes the selected budget record.', 'note' => 'This is destructive. Confirm that the record is genuinely incorrect before removing it.', 'kind' => 'danger'],
            ],
        ],
        [
            'title' => 'Participants and documents',
            'eyebrow' => 'Records and supporting files',
            'items' => [
                ['control' => 'Add participant', 'does' => 'Creates one participant record manually.', 'note' => 'Use it for individual additions or corrections after an import.', 'kind' => 'create'],
                ['control' => 'Import CSV', 'does' => 'Imports multiple participant rows from a CSV file.', 'note' => 'Check column names and sample data before importing a full group.', 'kind' => 'upload'],
                ['control' => 'Registration link', 'does' => 'Creates or manages the public link used for participant self-registration.', 'note' => 'Share the link only with the intended group and close it when registration ends.', 'kind' => 'manage'],
                ['control' => 'Upload project file', 'does' => 'Adds a general project document such as a source file, agreement or supporting record.', 'note' => 'Choose a clear title and category so the file can be found later.', 'kind' => 'upload'],
                ['control' => 'Upload signed copy', 'does' => 'Adds or replaces the signed version of a generated record.', 'note' => 'Use the signed-copy action rather than mixing signed files with unrelated documents.', 'kind' => 'upload'],
                ['control' => 'Search and filter', 'does' => 'Narrows the document or participant list without changing data.', 'note' => 'Use filters before assuming a record is missing.', 'kind' => 'search'],
            ],
        ],
        [
            'title' => 'Mobility evidence and finalisation',
            'eyebrow' => 'Daily proof and handover',
            'items' => [
                ['control' => 'Add evidence day', 'does' => 'Creates a day card for a mobility activity.', 'note' => 'Create the day before or during the activity so evidence follows the real timeline.', 'kind' => 'create'],
                ['control' => 'Upload images', 'does' => 'Attaches photos to one evidence day.', 'note' => 'Add a short batch note so later reviewers understand what the images show.', 'kind' => 'upload'],
                ['control' => 'Upload files', 'does' => 'Attaches agendas, worksheets, presentations, certificates or other files to one evidence day.', 'note' => 'Use the day that best explains why the file belongs to the mobility.', 'kind' => 'upload'],
                ['control' => 'Delete evidence day', 'does' => 'Removes the day entry while uploaded files remain in the project files list.', 'note' => 'This is destructive for the timeline entry. Use only for duplicate or incorrect days.', 'kind' => 'danger'],
                ['control' => 'Upload material or output', 'does' => 'Adds project-wide materials, outputs or implementation files.', 'note' => 'Use this for content that belongs to the project but not to one specific mobility day.', 'kind' => 'upload'],
                ['control' => 'Finalisation checks', 'does' => 'Shows which records still need review before final preparation.', 'note' => 'Treat this as your internal checklist; confirm official submission requirements separately.', 'kind' => 'review'],
            ],
        ],
    ];

    $workspaceMenu = [
        ['icon' => '⌂', 'title' => 'Project dashboard', 'does' => 'Your starting screen: current projects, funding, priorities, milestones and quick actions.', 'use' => 'Open it first when you return to MobilityCloud or when you need to choose the next task.'],
        ['icon' => '▤', 'title' => 'Projects', 'does' => 'Lists every project you can access and lets project owners start a new one.', 'use' => 'Use filters or search to find an existing project; open the project card to enter its workspaces.'],
        ['icon' => '✓', 'title' => 'My Tasks', 'does' => 'Collects tasks assigned to you across accessible projects.', 'use' => 'Use the status, due-date and text filters to focus on open or overdue work. Completing a task updates the shared project record.'],
        ['icon' => '⌕', 'title' => 'Search', 'does' => 'Finds accessible projects, participants, expenses and documents from one place.', 'use' => 'Enter at least two characters. Search results take you directly to the relevant project area.'],
        ['icon' => '◫', 'title' => 'Calendar', 'does' => 'Shows project, mobility and other relevant dates together.', 'use' => 'Use it to spot approaching milestones and check whether project dates have been configured.'],
        ['icon' => '▣', 'title' => 'Content Library', 'does' => 'Stores your organisation’s reusable private writing blocks.', 'use' => 'Save approved wording here, then reuse and adapt it for new applications without recreating the text.'],
        ['icon' => '€', 'title' => 'Individual Support', 'does' => 'Calculates planning scenarios for Individual Support, travel and Organisational Support unit contributions.', 'use' => 'Use it for estimates before approval. It does not decide eligibility or replace official rates.'],
        ['icon' => '◎', 'title' => 'Public Library', 'does' => 'Offers public reusable content blocks shared by the MobilityCloud community.', 'use' => 'Preview a block and import an editable copy into your own Content Library; the public original stays unchanged.'],
    ];
@endphp

<section class="mc-shell mc-guide-hero">
    <div>
        <span class="mc-kicker"><span>Platform guide</span></span>
        <h1 class="mc-title" style="margin-top:1rem;max-width:13ch;">Build a complete project record, step by step.</h1>
        <p class="mc-lead">This practical guide follows the same order as an Erasmus+ project: prepare, write, approve, implement and archive. Use it as an onboarding path for project owners and collaborators.</p>
        <div class="mc-guide-search" role="search" aria-label="Search the platform guide">
            <label for="mc-guide-search-input">Search this guide</label>
            <div class="mc-guide-search-field">
                <span aria-hidden="true">⌕</span>
                <input id="mc-guide-search-input" type="search" placeholder="Search a button, module or task…" autocomplete="off" aria-describedby="mc-guide-search-help">
                <button id="mc-guide-search-clear" type="button" hidden>Clear</button>
            </div>
            <p id="mc-guide-search-help">Try “CSV import”, “upload signed copy”, “calendar” or “budget transfer”.</p>
            <div id="mc-guide-search-results" class="mc-guide-search-results" aria-live="polite" hidden></div>
        </div>
        <div class="mc-guide-hero-pills" aria-label="Guide scope">
            <span>7 project stages</span><span>Sidebar menu reference</span><span>Real platform screens</span>
        </div>
    </div>
    <aside class="mc-guide-route" aria-label="Guide workflow">
        <p class="mc-card-label">Your route through MobilityCloud</p>
        <div class="mc-route-steps">
            @foreach($guideNavigation as $item)
                <a href="#{{ $item['id'] }}"><b>{{ $item['number'] }}</b><span>{{ $item['label'] }}</span></a>
            @endforeach
        </div>
        <p class="mc-guide-route-note">Tip: finish the checks shown in each stage before moving to the next one.</p>
    </aside>
</section>

<section class="mc-shell mc-guide-start">
    <div class="mc-guide-start-head">
        <p class="mc-eyebrow">Before you start</p>
        <h2>Set up the project so the record stays useful later.</h2>
    </div>
    <div class="mc-guide-start-grid">
        @foreach($gettingStarted as $item)
            <article>
                <strong>{{ $item['icon'] }}</strong>
                <div><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></div>
            </article>
        @endforeach
    </div>
</section>

<section class="mc-shell mc-guide-layout">
    <nav class="mc-guide-toc" aria-label="Guide chapters">
        <p>In this guide</p>
        @foreach($guideNavigation as $item)
            <a href="#{{ $item['id'] }}"><span>{{ $item['number'] }}</span>{{ $item['label'] }}</a>
        @endforeach
        <a class="mc-guide-toc-help" href="{{ route('marketing.help') }}">Need a quick answer? <b>Open Help</b></a>
    </nav>

    <div class="mc-guide-chapters">
        <article class="mc-guide-chapter" id="side-menu" data-guide-search-item data-guide-anchor="side-menu" data-guide-search-title="Sidebar menu and platform navigation">
            <div class="mc-guide-chapter-number">00</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Sidebar menu</p>
                <h2>Use the left sidebar as your map of the platform.</h2>
                <p class="mc-guide-intro">The sidebar stays available while you work. It takes you to cross-project areas; once you open a project, its own tabs keep the application, implementation and evidence work connected to that project.</p>
                <div class="mc-sidebar-guide-screen" aria-label="Example MobilityCloud sidebar menu">
                    <div class="mc-sidebar-guide-brand"><span>MC</span><b>MobilityCloud</b><i>‹</i></div>
                    <div class="mc-sidebar-guide-links">
                        @foreach($workspaceMenu as $index => $item)
                            <div @class(['is-active' => $index === 0])><span>{{ $item['icon'] }}</span><b>{{ $item['title'] }}</b></div>
                        @endforeach
                    </div>
                    <p><span>Tip</span> The chevron collapses or expands the menu labels; it does not change data.</p>
                </div>
                <div class="mc-workspace-menu-grid">
                    @foreach($workspaceMenu as $item)
                        <article class="mc-workspace-menu-card" data-guide-search-item data-guide-anchor="side-menu" data-guide-search-title="{{ $item['title'] }} sidebar menu">
                            <span class="mc-workspace-menu-icon">{{ $item['icon'] }}</span>
                            <div>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['does'] }}</p>
                                <small><b>How to use it:</b> {{ $item['use'] }}</small>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mc-guide-callout info"><b>Navigation rule</b><p>Use the sidebar to move between areas that belong to your account. Use a project’s internal tabs to work inside that particular project, so you never mix up documents, people or budget data from different projects.</p></div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="prepare" data-guide-search-item data-guide-anchor="prepare" data-guide-search-title="Prepare your workspace and dashboard">
            <div class="mc-guide-chapter-number">01</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Prepare your workspace</p>
                <h2>Start from your dashboard, not from a folder.</h2>
                <p class="mc-guide-intro">The dashboard brings projects, available funding, current priorities and quick actions into one view. It is the best place to decide what needs attention first.</p>
                <ol class="mc-guide-list">
                    <li><b>Review the priority cards.</b><span>Red and amber items point to incomplete project data, missing evidence or work that is due next.</span></li>
                    <li><b>Open the relevant project.</b><span>Each project keeps its own application, budget, participants, documents and mobility evidence.</span></li>
                    <li><b>Use Quick actions for repeat work.</b><span>Create a project, manage expenses, add participants or prepare documents without hunting through menus.</span></li>
                </ol>
                <figure class="mc-guide-shot mc-guide-shot-dashboard">
                    <img src="{{ asset('images/guide/dashboard-demo.png') }}" alt="MobilityCloud project dashboard with funding, priorities and quick actions">
                    <figcaption><span>Platform screen</span> The project dashboard highlights funding, readiness and actions that require attention.</figcaption>
                </figure>
                <div class="mc-guide-callout info"><b>Good habit</b><p>Open the dashboard whenever you return to the platform. It helps you continue from the most important task rather than from the last tab you used.</p></div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="create" data-guide-search-item data-guide-anchor="create" data-guide-search-title="Create a project">
            <div class="mc-guide-chapter-number">02</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Create a project</p>
                <h2>Create one project record for one mobility project.</h2>
                <p class="mc-guide-intro">Choose <b>New project</b> from the dashboard. Add the core identity details carefully; they become the context used across writing, planning and implementation.</p>
                <div class="mc-guide-split-visual">
                    <div>
                        <ol class="mc-guide-list compact">
                            <li><b>Name the project clearly.</b><span>Use the name your team can recognise in exports, internal discussions and final files.</span></li>
                            <li><b>Select the right action or template.</b><span>Use a writing template for an application, or create an approved project if the application already exists.</span></li>
                            <li><b>Add dates and partners.</b><span>Project and mobility dates drive readiness checks; partner names give the project useful working context.</span></li>
                        </ol>
                    </div>
                    <div class="mc-guide-ui-card" aria-label="Example project creation form">
                        <div class="mc-guide-ui-title"><span class="mc-ui-icon">+</span><b>New project</b><em>Step 1 of 2</em></div>
                        <label>Project name <i>Green Skills Exchange</i></label>
                        <label>Action / template <i>KA122-VET · Short-term project</i></label>
                        <div class="mc-guide-ui-two"><label>Start date <i>01 Sep 2026</i></label><label>End date <i>31 Aug 2027</i></label></div>
                        <div class="mc-guide-ui-footer"><span>All details can be updated later.</span><b>Continue →</b></div>
                    </div>
                </div>
                <div class="mc-guide-callout warning"><b>Before approval</b><p>Keep the project in the writing or planning stage until you know the official result. The management modules are designed to continue after you declare approval.</p></div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="write" data-guide-search-item data-guide-anchor="write" data-guide-search-title="Write and review application">
            <div class="mc-guide-chapter-number">03</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Write and review</p>
                <h2>Use the application workspace one question at a time.</h2>
                <p class="mc-guide-intro">The writing view gives each answer a clear place. Move through the sections, use the guidance where needed and watch the readiness signal before exporting.</p>
                <div class="mc-writing-guide-screen" aria-label="Example application workspace">
                    <aside><b>Application</b><span class="done">✓ Context</span><span class="done">✓ Objectives</span><span class="active">3 Activities</span><span>4 Impact</span><span>5 Follow-up</span></aside>
                    <div>
                        <div class="mc-guide-screen-top"><span>Activities and methodology</span><em>Saved just now</em></div>
                        <p>Describe the activities, learning methods, preparation and how participants will be supported before, during and after mobility.</p>
                        <div class="mc-guide-screen-lines"><i></i><i></i><i></i><i></i></div>
                        <footer><span>2,184 / 3,000 characters</span><b>Readiness 82%</b></footer>
                    </div>
                </div>
                <ol class="mc-guide-list">
                    <li><b>Read the prompt first.</b><span>Answer the question that is shown, not a general version of it. Keep one clear idea per section.</span></li>
                    <li><b>Save as you work.</b><span>Use the section status and character count to see whether an answer is still incomplete.</span></li>
                    <li><b>Review before export.</b><span>Use readiness checks to locate gaps, then create a PDF or Word working copy for your team review.</span></li>
                </ol>
            </div>
        </article>

        <article class="mc-guide-chapter" id="table-models" data-guide-search-item data-guide-anchor="table-models" data-guide-search-title="Choose a table model for an application question">
            <div class="mc-guide-chapter-number">03A</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Table models</p>
                <h2>Choose a table only when rows make the answer clearer.</h2>
                <p class="mc-guide-intro">Tables are optional working tools, not official fixed fields. Use normal prose for a question that asks <em>why</em>, <em>how</em> or <em>what you will learn</em>. Add a table when you need to compare several groups, activities, costs, responsibilities or measures.</p>
                <div class="mc-table-decision" aria-label="When to use a table">
                    <div><span>Write prose</span><b>One idea, rationale or explanation</b><p>Example: Why is the project needed? How will you prepare participants?</p></div>
                    <div><span>Add a table</span><b>Repeated facts that need comparison</b><p>Example: several activities, participant groups, costs, indicators or audiences.</p></div>
                </div>
                <ol class="mc-guide-list">
                    <li><b>Read the exact question.</b><span>Choose the model whose purpose matches the question, not the one that merely has a familiar name.</span></li>
                    <li><b>Add one table, then name the columns.</b><span>Select <b>+ Add table</b>; type the column names from the chosen model, then use <b>+ Add row</b> for every item you need to compare.</span></li>
                    <li><b>Keep the written answer and table complementary.</b><span>Use the answer field for the explanation and the table for the structured facts. Do not repeat the whole table in prose.</span></li>
                </ol>
                <div class="mc-table-model-grid">
                    @foreach($tableModels as $model)
                        <article data-guide-search-item data-guide-anchor="table-models" data-guide-search-title="{{ $model['title'] }} table model">
                            <h3>{{ $model['title'] }}</h3>
                            <p>{{ $model['use'] }}</p>
                            <div class="mc-table-model-preview" aria-label="{{ $model['title'] }} column model">
                                @foreach($model['columns'] as $column)
                                    <span>{{ $column }}</span>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mc-guide-callout warning"><b>Keep it simple</b><p>Start with the smallest useful table: normally one table per question and only the columns you will actually fill. A table is included in the PDF and Word working exports exactly as you write it; you remain responsible for transferring and checking the final information in the official application system.</p></div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="approve" data-guide-search-item data-guide-anchor="approve" data-guide-search-title="Declare approval">
            <div class="mc-guide-chapter-number">04</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Declare approval</p>
                <h2>Move to implementation only after the official result.</h2>
                <p class="mc-guide-intro">When the project is approved, open its settings and declare the exact approved grant amount. The record then continues into budget, participant, document and mobility workflows.</p>
                <div class="mc-guide-approval-flow">
                    <div><span>1</span><b>Official approval received</b><small>Confirm the grant and project conditions outside MobilityCloud.</small></div>
                    <i>→</i>
                    <div><span>2</span><b>Enter approved grant</b><small>Use the exact approved amount, not an estimate.</small></div>
                    <i>→</i>
                    <div><span>3</span><b>Management modules open</b><small>Create practical budget baskets and start implementation.</small></div>
                </div>
                <div class="mc-guide-callout important"><b>Important</b><p>MobilityCloud is an independent working platform. Approval decisions, contractual obligations and official reporting always remain with the competent National Agency and official systems.</p></div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="manage" data-guide-search-item data-guide-anchor="manage" data-guide-search-title="Manage implementation budget participants documents mobility">
            <div class="mc-guide-chapter-number">05</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Manage implementation</p>
                <h2>Keep spending and evidence connected to the project.</h2>
                <p class="mc-guide-intro">After approval, the project navigation gives each operational area its own workspace. Start with budget baskets, then add the people, files and daily mobility evidence needed for delivery.</p>
                <figure class="mc-guide-shot">
                    <img src="{{ asset('images/guide/budget-demo.png') }}" alt="MobilityCloud budget screen with approved grant, spending and expense evidence">
                    <figcaption><span>Platform screen</span> Budget control shows the approved grant, remaining balance, expense rows and supporting files in one project view.</figcaption>
                </figure>
                <div class="mc-guide-module-grid">
                    <article><span>€</span><h3>Budget</h3><p>Create baskets, enter expenses and attach the relevant invoice, receipt or support file.</p><b>Check: amount, currency, evidence</b></article>
                    <article><span>◎</span><h3>Participants</h3><p>Add the group when it is known, complete required details and keep participant documents connected.</p><b>Check: contacts, documents, status</b></article>
                    <article><span>▣</span><h3>Documents</h3><p>Separate project files, generated records and signed copies so the document list remains understandable.</p><b>Check: checklist and signed copies</b></article>
                    <article><span>◫</span><h3>Mobility evidence</h3><p>Create one entry for each activity day and add photos, links, notes and files while the mobility happens.</p><b>Check: every day has proof</b></article>
                </div>
                <div class="mc-evidence-guide-screen" aria-label="Example daily mobility evidence screen">
                    <div class="mc-evidence-guide-head"><b>Lisbon mobility · 12–16 May</b><span>16 participants</span></div>
                    <div class="mc-evidence-guide-day"><i>01</i><div><b>Arrival and welcome</b><span>8 photos · 2 files · attendance list</span></div><em>Complete</em></div>
                    <div class="mc-evidence-guide-day"><i>02</i><div><b>Digital workshop</b><span>12 photos · agenda · participant reflections</span></div><em>Complete</em></div>
                    <div class="mc-evidence-guide-day pending"><i>03</i><div><b>School visit and reflection</b><span>6 photos · 1 note still needed</span></div><em>Add note</em></div>
                </div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="archive" data-guide-search-item data-guide-anchor="archive" data-guide-search-title="Prepare final files">
            <div class="mc-guide-chapter-number">06</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Prepare final files</p>
                <h2>Archive continuously, not in the final week.</h2>
                <p class="mc-guide-intro">The finalisation stage is easier when the record has been maintained during implementation. Use the checks below before you prepare files for internal review or official reporting.</p>
                <div class="mc-final-checklist">
                    <div><span>✓</span><b>Project details and dates are complete</b><small>Grant value, project period, mobility period and key organisations are correct.</small></div>
                    <div><span>✓</span><b>Expenses have supporting evidence</b><small>Files are attached where your team needs them and spending is reviewed by basket.</small></div>
                    <div><span>✓</span><b>Participant and mobility records are complete</b><small>Required participant data and daily activity proof have been reviewed.</small></div>
                    <div><span>✓</span><b>Documents and outputs are organised</b><small>Signed copies, materials, dissemination evidence and links can be found quickly.</small></div>
                </div>
                <div class="mc-guide-final-banner"><div><p class="mc-card-label">Ready for your final preparation</p><h3>One project record. A clearer handover.</h3><p>Use the project views and exports as an organised working archive. Verify each official submission requirement separately before sending it.</p></div><span>↗</span></div>
            </div>
        </article>

        <article class="mc-guide-chapter" id="controls" data-guide-search-item data-guide-anchor="controls" data-guide-search-title="Button and control reference">
            <div class="mc-guide-chapter-number">07</div>
            <div class="mc-guide-chapter-content">
                <p class="mc-eyebrow">Button reference</p>
                <h2>What each core button and control does.</h2>
                <p class="mc-guide-intro">Use this reference while working in the platform. Controls are grouped by the screen where they appear, so you can find the action before you use it. Labels can differ slightly when your role is view-only or a project is not yet approved.</p>
                <div class="mc-control-legend" aria-label="Control types">
                    <span class="navigate">Navigate or view</span><span class="create">Create or add</span><span class="upload">Upload or import</span><span class="important">Changes project state</span><span class="danger">Destructive action</span>
                </div>
                <div class="mc-control-groups">
                    @foreach($controlGroups as $group)
                        <section class="mc-control-group">
                            <header><p>{{ $group['eyebrow'] }}</p><h3>{{ $group['title'] }}</h3></header>
                            <div class="mc-control-table">
                                @foreach($group['items'] as $item)
                                    <article class="mc-control-row" data-guide-search-item data-guide-anchor="controls" data-guide-search-title="{{ $item['control'] }}">
                                        <span class="mc-control-kind {{ $item['kind'] }}">{{ match($item['kind']) { 'danger' => '!', 'important' => '•', 'upload' => '↑', 'create' => '+', 'search' => '⌕', 'export' => '↓', 'review' => '✓', 'settings' => '⚙', 'library' => '▤', 'manage' => '↗', 'edit' => '✎', default => '→' } }}</span>
                                        <div><h4>{{ $item['control'] }}</h4><p>{{ $item['does'] }}</p></div>
                                        <small>{{ $item['note'] }}</small>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                <div class="mc-guide-callout important"><b>Access matters</b><p>If a control is missing or disabled, your project role, project stage or account status may not allow that action. Ask the project owner before trying to work around access limits.</p></div>
            </div>
        </article>
    </div>
</section>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('mc-guide-search-input');
            const clear = document.getElementById('mc-guide-search-clear');
            const results = document.getElementById('mc-guide-search-results');

            if (!input || !clear || !results) {
                return;
            }

            const entries = Array.from(document.querySelectorAll('[data-guide-search-item]')).map(function (node) {
                return {
                    anchor: node.dataset.guideAnchor || node.id,
                    title: node.dataset.guideSearchTitle || (node.querySelector('h2, h3, h4') || {}).textContent || 'Guide item',
                    text: node.textContent.replace(/\s+/g, ' ').trim(),
                };
            });

            const renderResults = function () {
                const query = input.value.trim().toLocaleLowerCase();
                clear.hidden = query.length === 0;
                results.replaceChildren();

                if (query.length < 2) {
                    results.hidden = true;
                    return;
                }

                const seen = new Set();
                const matches = entries.filter(function (entry) {
                    return entry.title.toLocaleLowerCase().includes(query) || entry.text.toLocaleLowerCase().includes(query);
                }).filter(function (entry) {
                    const key = entry.anchor + '|' + entry.title;
                    if (seen.has(key)) {
                        return false;
                    }
                    seen.add(key);
                    return true;
                }).slice(0, 10);

                const summary = document.createElement('p');
                summary.className = 'mc-guide-search-summary';
                summary.textContent = matches.length ? matches.length + ' matching guide item' + (matches.length === 1 ? '' : 's') : 'No matching guide items';
                results.appendChild(summary);

                if (matches.length) {
                    const list = document.createElement('div');
                    list.className = 'mc-guide-search-list';
                    matches.forEach(function (entry) {
                        const link = document.createElement('a');
                        link.href = '#' + entry.anchor;
                        const title = document.createElement('b');
                        title.textContent = entry.title.trim();
                        const hint = document.createElement('span');
                        hint.textContent = 'Open this section';
                        link.append(title, hint);
                        link.addEventListener('click', function () {
                            results.hidden = true;
                        });
                        list.appendChild(link);
                    });
                    results.appendChild(list);
                } else {
                    const hint = document.createElement('small');
                    hint.textContent = 'Try a module name, an action such as “upload”, or the name of a button.';
                    results.appendChild(hint);
                }

                results.hidden = false;
            };

            input.addEventListener('input', renderResults);
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    input.value = '';
                    renderResults();
                    input.blur();
                }
            });
            clear.addEventListener('click', function () {
                input.value = '';
                renderResults();
                input.focus();
            });
        });
    </script>
@endonce
