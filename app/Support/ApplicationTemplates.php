<?php

namespace App\Support;

class ApplicationTemplates
{
    /**
     * Curated writing structures based on the official Erasmus+ application forms.
     * Character limits are working targets: the submission portal remains authoritative.
     */
    public const TEMPLATES = [
        'ka151-you' => [
            'label' => 'KA151-YOU - Accredited youth mobility projects',
            'action' => 'KA151-YOU',
            'call_year' => 2026,
            'form_id' => 'KA151-YOU-37A4BB00',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Accredited%20projects%20for%20youth%20mobility%20%28KA151-YOU%29_watermark.pdf',
            'description' => 'Annual activity plan for organisations holding an Erasmus Youth accreditation.',
            'sections' => [
                ['key' => 'objectives-contribution', 'category' => 'Objectives and activity plan', 'title' => 'How will the planned activities contribute to your accreditation objectives?', 'char_limit' => 4000, 'guidance' => 'Connect each requested activity type and participant profile to concrete accreditation objectives and expected organisational change.'],
                ['key' => 'activity-targets', 'category' => 'Objectives and activity plan', 'title' => 'Explain your activity targets and the estimated number and profile of participants.', 'char_limit' => 4000, 'guidance' => 'Justify the scale, activity types, participant mix and inclusion targets. Keep the figures consistent with the budget request.'],
                ['key' => 'activity-plan-changes', 'category' => 'Objectives and activity plan', 'title' => 'Have there been relevant changes to your activity plan? If so, explain them.', 'char_limit' => 3000, 'guidance' => 'Describe only material changes since accreditation and explain why they remain consistent with the approved Erasmus Plan.'],
                ['key' => 'quality-implementation', 'category' => 'Quality and implementation', 'title' => 'How will you ensure that all activities comply with the Erasmus Youth Quality Standards?', 'char_limit' => 4000, 'guidance' => 'Cover participant support, learning outcomes, inclusion, partner responsibilities, safeguarding and monitoring.'],
                ['key' => 'virtual-green', 'category' => 'Quality and implementation', 'title' => 'How will you use virtual components and environmentally sustainable practices where relevant?', 'char_limit' => 3000, 'guidance' => 'Name practical measures and explain how they improve learning or reduce environmental impact.'],
            ],
        ],
        'ka152-you' => [
            'label' => 'KA152-YOU - Youth Exchanges',
            'action' => 'KA152-YOU',
            'call_year' => 2026,
            'form_id' => 'KA152-YOU-801EC704',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Mobility%20of%20young%20people%20%28KA152-YOU%29_watermark.pdf',
            'description' => 'Mobility of young people through Youth Exchanges.',
            'sections' => [
                ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'What do you want to achieve by implementing the project? What are the objectives from the perspective of youth work practice?', 'char_limit' => 2000, 'guidance' => 'Write a concise public summary. State the change you want to create, for whom and why it matters.'],
                ['key' => 'summary-activities', 'category' => 'Project summary', 'title' => 'What activities do you plan to implement? What is the number and profile of participants involved?', 'char_limit' => 2000, 'guidance' => 'Summarise the activity format, countries, duration, participant profile and approximate numbers.'],
                ['key' => 'summary-impact', 'category' => 'Project summary', 'title' => 'What results and impact do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Distinguish participant learning, organisational results and wider community impact.'],
                ['key' => 'needs-objectives', 'category' => 'Project rationale', 'title' => 'Why do you want to carry out this project? Describe the issues and needs you want to address and your project objectives.', 'char_limit' => 5000, 'guidance' => 'Use evidence and partner perspectives. Turn each identified need into a specific, measurable objective.'],
                ['key' => 'programme-link', 'category' => 'Project rationale', 'title' => 'How does your project link to the objectives of Erasmus+ and those of Youth Exchanges?', 'char_limit' => 3000, 'guidance' => 'Explain the link rather than listing programme priorities. Show how the design puts them into practice.'],
                ['key' => 'participant-benefit', 'category' => 'Project rationale', 'title' => 'How will the project benefit the young participants during and after the project lifetime?', 'char_limit' => 3000, 'guidance' => 'Cover knowledge, skills, attitudes, participation and how learning will transfer after mobility.'],
                ['key' => 'organisation-benefit', 'category' => 'Project rationale', 'title' => 'How will the project benefit the organisations or groups implementing it?', 'char_limit' => 3000, 'guidance' => 'Describe improved youth-work practice, capacity, partnerships and follow-up use of results.'],
                ['key' => 'participants-involvement', 'category' => 'Activities and participants', 'title' => 'How will young people be involved in planning, preparation, implementation and follow-up?', 'char_limit' => 4000, 'guidance' => 'Give young people real responsibilities and decision points, not only attendance roles.'],
                ['key' => 'preparation-support', 'category' => 'Project design', 'title' => 'How will you prepare participants and support them before, during and after the activity?', 'char_limit' => 4000, 'guidance' => 'Cover intercultural and linguistic preparation, risk prevention, mentoring, accessibility and post-activity support.'],
                ['key' => 'safety-protection', 'category' => 'Project design', 'title' => 'What measures will you put in place to ensure the safety and protection of participants?', 'char_limit' => 3000, 'guidance' => 'Include safeguarding roles, emergency procedures, insurance, consent, reporting and activity-specific risks.'],
                ['key' => 'follow-up', 'category' => 'Project design', 'title' => 'What activities are foreseen after the Youth Exchange? How will participants follow up?', 'char_limit' => 3000, 'guidance' => 'Name owners, timing and concrete local actions after the mobility.'],
                ['key' => 'learning-recognition', 'category' => 'Project design', 'title' => 'How will participants reflect on, document and recognise their learning outcomes?', 'char_limit' => 3500, 'guidance' => 'Explain daily reflection methods, facilitator support and how Youthpass or other tools will be used.'],
                ['key' => 'inclusion', 'category' => 'Project design', 'title' => 'How will you identify and support participants with fewer opportunities?', 'char_limit' => 3000, 'guidance' => 'Name relevant barriers, reasonable support measures and how participation remains dignified and equitable.'],
                ['key' => 'virtual-green', 'category' => 'Project design', 'title' => 'How will virtual components and environmentally friendly practices support the project?', 'char_limit' => 2500, 'guidance' => 'Only include tools and practices that have a clear purpose in preparation, learning, cooperation or impact.'],
                ['key' => 'management-logistics', 'category' => 'Project management', 'title' => 'How will you manage the project and organise its practical and logistical aspects?', 'char_limit' => 5000, 'guidance' => 'Cover agreements, roles, timeline, travel, accommodation, insurance, visas, accessibility, mentoring and financial control.'],
                ['key' => 'partnership', 'category' => 'Project management', 'title' => 'Why were the partners chosen, what will they contribute, and how will you communicate and coordinate?', 'char_limit' => 4500, 'guidance' => 'Show complementary expertise and define communication rhythm, decisions, monitoring and escalation.'],
                ['key' => 'evaluation', 'category' => 'Project management', 'title' => 'How will you evaluate whether and to what extent the project reached its objectives and results?', 'char_limit' => 3500, 'guidance' => 'Pair each objective with indicators, evidence, timing and a responsible person.'],
                ['key' => 'dissemination', 'category' => 'Project management', 'title' => 'How will you make the project visible and share its results? How will participants be involved?', 'char_limit' => 3500, 'guidance' => 'Define audiences, messages, channels, outputs, timing, owners and evidence of reach.'],
            ],
        ],
        'ka153-you' => [
            'label' => 'KA153-YOU - Mobility of youth workers',
            'action' => 'KA153-YOU', 'call_year' => 2026, 'form_id' => 'KA153-YOU-3F05A132',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Mobility%20of%20youth%20workers%20%28KA153-YOU%29_watermark.pdf',
            'description' => 'Professional development and system-development activities for youth workers.',
            'sections' => [
                ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'What do you want to achieve and what are the objectives from the perspective of youth work practice?', 'char_limit' => 2000, 'guidance' => 'Summarise the intended improvement in youth-work practice and its beneficiaries.'],
                ['key' => 'summary-activities', 'category' => 'Project summary', 'title' => 'What activities will you implement and what is the number and profile of participants?', 'char_limit' => 2000, 'guidance' => 'Summarise professional development activities, participants, duration and countries.'],
                ['key' => 'summary-impact', 'category' => 'Project summary', 'title' => 'What results and impact do you expect?', 'char_limit' => 2000, 'guidance' => 'Include impact on youth workers, organisations, young people and youth work more broadly.'],
                ['key' => 'needs-objectives', 'category' => 'Project rationale', 'title' => 'Why do you want to carry out this project? Describe the idea, needs and objectives.', 'char_limit' => 5000, 'guidance' => 'Base the rationale on practice needs and convert them into observable professional-development objectives.'],
                ['key' => 'programme-link', 'category' => 'Project rationale', 'title' => 'How does the project link to Erasmus+ objectives and those of Youth Workers Mobility?', 'char_limit' => 3000, 'guidance' => 'Demonstrate the connection through project choices and methods.'],
                ['key' => 'target-groups', 'category' => 'Project rationale', 'title' => 'What are the main target groups of your project?', 'char_limit' => 2500, 'guidance' => 'Describe youth-worker profiles, experience, roles, needs and the young people ultimately reached.'],
                ['key' => 'practice-benefit', 'category' => 'Project rationale', 'title' => 'How will the project benefit youth workers and their organisations in their daily work with young people?', 'char_limit' => 3500, 'guidance' => 'Explain transfer into daily practice during and after the project.'],
                ['key' => 'youth-work-development', 'category' => 'Project rationale', 'title' => 'How will the project contribute to the development of youth work more generally?', 'char_limit' => 3500, 'guidance' => 'Describe transferability, networks, methods, policy links or reusable outputs.'],
                ['key' => 'preparation-support', 'category' => 'Project design', 'title' => 'How will you prepare, support and follow up with participants?', 'char_limit' => 4000, 'guidance' => 'Cover preparation, support during activities and transfer to practice afterwards.'],
                ['key' => 'learning-recognition', 'category' => 'Project design', 'title' => 'How will participants reflect on, document and recognise their learning outcomes?', 'char_limit' => 3500, 'guidance' => 'Describe reflection methods, learning evidence and Youthpass or national instruments.'],
                ['key' => 'management-logistics', 'category' => 'Project management', 'title' => 'How will you manage the project and organise practical and logistical arrangements?', 'char_limit' => 5000, 'guidance' => 'Define roles, agreements, timeline, travel, accommodation, insurance, access and quality control.'],
                ['key' => 'partnership', 'category' => 'Project management', 'title' => 'How will partners and other actors contribute, communicate and coordinate?', 'char_limit' => 4000, 'guidance' => 'Show complementary competence, decision-making and monitoring.'],
                ['key' => 'evaluation', 'category' => 'Project management', 'title' => 'How will you evaluate success against the objectives and expected results?', 'char_limit' => 3500, 'guidance' => 'Use indicators for learning, transfer to practice, organisational benefit and wider youth-work impact.'],
                ['key' => 'dissemination', 'category' => 'Project management', 'title' => 'How will you make the project visible and share its results, including participant involvement?', 'char_limit' => 3500, 'guidance' => 'Specify audiences, formats, channels, timing and ownership.'],
            ],
        ],
        'ka154-you' => [
            'label' => 'KA154-YOU - Youth participation activities',
            'action' => 'KA154-YOU', 'call_year' => 2026, 'form_id' => 'KA154-YOU-26CD668E',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Youth%20participation%20activities%20%28KA154-YOU%29_watermark_0.pdf',
            'description' => 'Local, national or transnational youth participation projects.',
            'sections' => [
                ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'What do you want to achieve by implementing the project?', 'char_limit' => 2000, 'guidance' => 'Summarise the participation change and the objectives in clear public language.'],
                ['key' => 'summary-activities', 'category' => 'Project summary', 'title' => 'What activities will you implement and who will participate?', 'char_limit' => 2000, 'guidance' => 'Summarise activity formats, reach, participant profiles and geography.'],
                ['key' => 'summary-impact', 'category' => 'Project summary', 'title' => 'What results and impact do you expect?', 'char_limit' => 2000, 'guidance' => 'Cover young people actively involved, wider target groups and participating organisations.'],
                ['key' => 'needs-objectives', 'category' => 'Project rationale', 'title' => 'Describe the project idea, identified needs and objectives.', 'char_limit' => 5000, 'guidance' => 'Ground the project in young people’s realities and show how they helped define the needs.'],
                ['key' => 'programme-link', 'category' => 'Project rationale', 'title' => 'How does the project link to Erasmus+ and Youth Participation Activities objectives?', 'char_limit' => 3000, 'guidance' => 'Show how the activities strengthen meaningful youth participation and dialogue.'],
                ['key' => 'target-groups', 'category' => 'Project rationale', 'title' => 'Who are the target groups of the individual activities?', 'char_limit' => 3000, 'guidance' => 'Distinguish the core young people, activity participants and wider audiences.'],
                ['key' => 'expected-impact', 'category' => 'Project rationale', 'title' => 'How will the project benefit the organisations, actively involved young people and wider target groups?', 'char_limit' => 4500, 'guidance' => 'Describe distinct benefits for each group and how they will be evidenced.'],
                ['key' => 'youth-involvement', 'category' => 'Activities and participants', 'title' => 'How will target groups be involved in planning, preparation, implementation and follow-up?', 'char_limit' => 4500, 'guidance' => 'Specify decisions, tasks and leadership roles held by young people in every phase.'],
                ['key' => 'cooperation', 'category' => 'Activities and participants', 'title' => 'How will participants cooperate and communicate to prepare and follow up the activities?', 'char_limit' => 3000, 'guidance' => 'Describe accessible channels, facilitation, responsibilities and decision-making.'],
                ['key' => 'preparation-support', 'category' => 'Project design', 'title' => 'How will you prepare participants and support them during and after activities?', 'char_limit' => 4000, 'guidance' => 'Include preparation, accessibility, safeguarding, facilitation and follow-up support.'],
                ['key' => 'learning-recognition', 'category' => 'Project design', 'title' => 'How will participants reflect on, document and recognise their learning?', 'char_limit' => 3500, 'guidance' => 'Embed reflection into activities and explain Youthpass or other recognition.'],
                ['key' => 'management-logistics', 'category' => 'Project management', 'title' => 'How will you manage the project and organise its practical and logistical aspects?', 'char_limit' => 5000, 'guidance' => 'Cover roles, agreements, communication, venues, mobility where relevant, accessibility and risk.'],
                ['key' => 'evaluation', 'category' => 'Project management', 'title' => 'How will you evaluate whether the project reached its objectives and results?', 'char_limit' => 3500, 'guidance' => 'Use participation-quality indicators as well as output and reach measures.'],
                ['key' => 'sustainability', 'category' => 'Project management', 'title' => 'Which results or activities will continue after funding and how will they benefit target groups?', 'char_limit' => 3000, 'guidance' => 'Name what continues, who owns it and what resources make continuation credible.'],
                ['key' => 'dissemination', 'category' => 'Project management', 'title' => 'How will you make the project visible and share its results? How will young people be involved?', 'char_limit' => 3500, 'guidance' => 'Plan dissemination with young people, not only for them. Define audiences and evidence of reach.'],
            ],
        ],
        'ka155-you' => [
            'label' => 'KA155-YOU - DiscoverEU Inclusion Action',
            'action' => 'KA155-YOU', 'call_year' => 2025, 'form_id' => 'KA155-YOU',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/resources-and-tools/documents-and-guidelines/template-application-form-discovereu-inclusion-action-ka155-you-0',
            'description' => 'Supported DiscoverEU travel for young people with fewer opportunities.',
            'sections' => [
                ['key' => 'summary', 'category' => 'Project summary', 'title' => 'Summarise the project objectives, activities, participant profile and expected results.', 'char_limit' => 3000, 'guidance' => 'Use clear public language and foreground inclusion.'],
                ['key' => 'needs-objectives', 'category' => 'Project rationale', 'title' => 'What needs and barriers will the project address, and what are its objectives?', 'char_limit' => 5000, 'guidance' => 'Describe concrete barriers without labelling participants and connect support to objectives.'],
                ['key' => 'participant-profile', 'category' => 'Participants', 'title' => 'Describe the participant profile, selection process and their involvement in shaping the journey.', 'char_limit' => 4000, 'guidance' => 'Show a fair selection process and meaningful participant choice.'],
                ['key' => 'journey-learning', 'category' => 'Project design', 'title' => 'Describe the journey, learning dimension and non-formal learning methods.', 'char_limit' => 5000, 'guidance' => 'A route is not yet a learning programme: explain preparation, reflection and learning moments.'],
                ['key' => 'support-safety', 'category' => 'Project design', 'title' => 'How will you provide preparation, inclusion support, mentoring, safety and follow-up?', 'char_limit' => 5000, 'guidance' => 'Tailor support to barriers, travel realities and participant autonomy.'],
                ['key' => 'learning-recognition', 'category' => 'Project design', 'title' => 'How will learning outcomes be reflected on, documented and recognised?', 'char_limit' => 3000, 'guidance' => 'Describe regular reflection and how participants own their learning record.'],
                ['key' => 'management', 'category' => 'Project management', 'title' => 'How will you manage logistics, responsibilities, risk and quality?', 'char_limit' => 4500, 'guidance' => 'Include bookings, contingency, emergency contact, accessibility and financial controls.'],
                ['key' => 'impact-follow-up', 'category' => 'Project management', 'title' => 'How will you evaluate impact, follow up and share results?', 'char_limit' => 4000, 'guidance' => 'Define indicators and participant-led follow-up and dissemination.'],
            ],
        ],
        'ka122' => [
            'label' => 'KA122 - Short-term mobility projects',
            'action' => 'KA122', 'call_year' => 2026, 'form_id' => 'KA122',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/resources-and-tools/documents-and-guidelines',
            'description' => 'General drafting structure for short-term mobility. Confirm the exact sector form (SCH, VET or ADU) before submission.',
            'sections' => [
                ['key' => 'needs-objectives', 'category' => 'Project objectives', 'title' => 'What needs does the organisation face and what concrete objectives will the project address?', 'char_limit' => 4000, 'guidance' => 'Use organisational evidence and connect every objective to an identified need.'],
                ['key' => 'activities', 'category' => 'Activities', 'title' => 'Describe the planned activities and explain how they contribute to the objectives.', 'char_limit' => 5000, 'guidance' => 'Keep activity types, participant numbers, destinations, duration and budget mutually consistent.'],
                ['key' => 'participants', 'category' => 'Activities', 'title' => 'Describe participant profiles, selection and support, including participants with fewer opportunities.', 'char_limit' => 4000, 'guidance' => 'Explain transparent selection, accessibility and support throughout the mobility cycle.'],
                ['key' => 'preparation', 'category' => 'Quality', 'title' => 'How will participants be prepared and supported before, during and after mobility?', 'char_limit' => 4000, 'guidance' => 'Cover task-related, linguistic, intercultural, practical and risk preparation.'],
                ['key' => 'learning', 'category' => 'Quality', 'title' => 'How will learning outcomes be defined, monitored, documented and recognised?', 'char_limit' => 3500, 'guidance' => 'Describe learning agreements, reflection, evidence and recognition instruments.'],
                ['key' => 'management', 'category' => 'Quality', 'title' => 'How will responsibilities, practical arrangements and quality standards be managed?', 'char_limit' => 4500, 'guidance' => 'Define roles, partner agreements, logistics, safeguarding and monitoring.'],
                ['key' => 'impact', 'category' => 'Impact and follow-up', 'title' => 'What impact is expected on participants, the organisation and relevant target groups?', 'char_limit' => 4000, 'guidance' => 'Use measurable indicators at participant and organisational level.'],
                ['key' => 'dissemination', 'category' => 'Impact and follow-up', 'title' => 'How will results be integrated, sustained and shared inside and outside the organisation?', 'char_limit' => 4000, 'guidance' => 'Name audiences, outputs, channels, owners, dates and how results enter regular practice.'],
            ],
        ],
        'ka210' => [
            'label' => 'KA210 - Small-scale partnerships',
            'action' => 'KA210', 'call_year' => 2026, 'form_id' => 'KA210',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/programme-guide/part-b/key-action-2/cooperation-partnerships',
            'description' => 'Compact cooperation projects designed for smaller-scale actors and newcomers.',
            'sections' => [
                ['key' => 'objectives', 'category' => 'Relevance', 'title' => 'What concrete objectives and results will the project achieve, and how are they linked to the selected priorities?', 'char_limit' => 4000, 'guidance' => 'Build a clear chain from needs to objectives, activities and results.'],
                ['key' => 'target-groups', 'category' => 'Relevance', 'title' => 'Who are the target groups and what evidence supports their needs?', 'char_limit' => 3000, 'guidance' => 'Segment target groups and avoid generic claims.'],
                ['key' => 'motivation', 'category' => 'Relevance', 'title' => 'Why is the project needed and why should it be funded?', 'char_limit' => 3500, 'guidance' => 'Explain the gap, European added value and why the proposed scale is appropriate.'],
                ['key' => 'partnership', 'category' => 'Partnership', 'title' => 'How do the partners complement each other and how will tasks and decisions be shared?', 'char_limit' => 4000, 'guidance' => 'Match responsibilities to demonstrated expertise and capacity.'],
                ['key' => 'activities', 'category' => 'Implementation', 'title' => 'Describe the activities, methodology, responsibilities, timetable and budget logic.', 'char_limit' => 6000, 'guidance' => 'Make every activity necessary for an objective and attach an owner, timing and result.'],
                ['key' => 'evaluation', 'category' => 'Impact', 'title' => 'How will you assess whether objectives and expected results have been achieved?', 'char_limit' => 3500, 'guidance' => 'Define indicators, baselines where possible, evidence and review moments.'],
                ['key' => 'sustainability', 'category' => 'Impact', 'title' => 'What impact is expected and how will results remain useful after funding ends?', 'char_limit' => 4000, 'guidance' => 'State who will use each result, in what setting and with what resources.'],
                ['key' => 'dissemination', 'category' => 'Impact', 'title' => 'How will results be shared within and outside the partnership?', 'char_limit' => 3500, 'guidance' => 'Define audiences, channels, timing, owners and reach indicators.'],
            ],
        ],
        'ka220' => [
            'label' => 'KA220 - Cooperation partnerships',
            'action' => 'KA220', 'call_year' => 2026, 'form_id' => 'KA220',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/programme-guide/part-b/key-action-2/cooperation-partnerships',
            'description' => 'Full cooperation partnership drafting structure, organised around relevance, implementation and impact.',
            'sections' => [
                ['key' => 'needs-objectives', 'category' => 'Relevance', 'title' => 'What needs, objectives, results and selected priorities define the project intervention logic?', 'char_limit' => 5000, 'guidance' => 'Use credible evidence and show a complete needs-to-impact chain.'],
                ['key' => 'innovation', 'category' => 'Relevance', 'title' => 'What is innovative and how does the proposal complement existing initiatives?', 'char_limit' => 3500, 'guidance' => 'Compare against current practice and describe the practical improvement.'],
                ['key' => 'eu-value', 'category' => 'Relevance', 'title' => 'Why is transnational cooperation necessary and what European added value will it create?', 'char_limit' => 3500, 'guidance' => 'Explain what could not be achieved equally well at national level.'],
                ['key' => 'partnership', 'category' => 'Partnership', 'title' => 'How was the partnership formed and how do partners provide complementary expertise and reach?', 'char_limit' => 4500, 'guidance' => 'Connect each partner to specific responsibilities and target groups.'],
                ['key' => 'management', 'category' => 'Implementation', 'title' => 'How will you manage quality, risk, communication, time and budget across the partnership?', 'char_limit' => 5000, 'guidance' => 'Describe governance, decisions, monitoring cadence, risk ownership and financial control.'],
                ['key' => 'work-packages', 'category' => 'Implementation', 'title' => 'Describe work packages, activities, outputs, milestones, responsibilities and budget allocation.', 'char_limit' => 7000, 'guidance' => 'Make dependencies and acceptance criteria visible, not only the activity list.'],
                ['key' => 'horizontal-priorities', 'category' => 'Implementation', 'title' => 'How are inclusion, digital practice, sustainability and participation embedded in delivery?', 'char_limit' => 3500, 'guidance' => 'Describe design choices and measures, not slogans.'],
                ['key' => 'evaluation-impact', 'category' => 'Impact', 'title' => 'How will results and impact on participants, organisations and target groups be evaluated?', 'char_limit' => 4500, 'guidance' => 'Combine output, outcome and longer-term impact indicators.'],
                ['key' => 'sustainability', 'category' => 'Impact', 'title' => 'How will results be used and sustained after the project ends?', 'char_limit' => 3500, 'guidance' => 'Name owners, resources, integration points and access conditions for every major result.'],
                ['key' => 'dissemination', 'category' => 'Impact', 'title' => 'Describe dissemination, exploitation, open access and target-audience engagement.', 'char_limit' => 4500, 'guidance' => 'Plan differentiated messages and channels and show how uptake will be measured.'],
            ],
        ],
    ];

    public static function list(): array
    {
        return collect(self::TEMPLATES)->mapWithKeys(fn (array $template, string $key) => [
            $key => $template['label'].' · Call '.$template['call_year'],
        ])->all();
    }

    public static function sections(string $key): array
    {
        return self::TEMPLATES[self::normaliseKey($key)]['sections'] ?? [];
    }

    public static function get(string $key): ?array
    {
        return self::TEMPLATES[self::normaliseKey($key)] ?? null;
    }

    public static function normaliseKey(?string $key): string
    {
        $key = strtolower((string) $key);
        $aliases = ['ka151' => 'ka151-you', 'ka152' => 'ka152-you', 'ka153' => 'ka153-you', 'ka154' => 'ka154-you', 'ka155' => 'ka155-you'];

        return $aliases[$key] ?? $key;
    }

    public static function libraryKeys(?string $key): array
    {
        $normalised = self::normaliseKey($key);
        $base = explode('-', $normalised)[0];

        return array_values(array_unique(['any', $normalised, $base]));
    }
}
