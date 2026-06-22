<?php

namespace App\Support;

class ApplicationTemplates
{
    // Sabloane bazate pe formularele oficiale Erasmus+ (Call 2024).
    // Intrebarile reflecta formularele reale; limitele de caractere sunt orientative
    // (formularele oficiale folosesc limite de ~3000-5000 si sunt editabile dupa incarcare).
    public const TEMPLATES = [
        'ka122' => [
            'label' => 'KA122 — Short-term mobility (School / VET / Adult)',
            'sections' => [
                ['category' => 'Context', 'title' => 'What are the specific objectives of your project? What needs do you want to address?', 'char_limit' => 3000],
                ['category' => 'Context', 'title' => 'What results do you expect from the activities?', 'char_limit' => 3000],
                ['category' => 'Activities', 'title' => 'Describe the planned activities, including the number and profile of participants.', 'char_limit' => 5000],
                ['category' => 'Activities', 'title' => 'How will participants be selected and involved in the project?', 'char_limit' => 3000],
                ['category' => 'Quality', 'title' => 'How will you prepare participants (task-related, intercultural, linguistic) before departure?', 'char_limit' => 3000],
                ['category' => 'Quality', 'title' => 'What practical arrangements (travel, accommodation, insurance, support) have you planned?', 'char_limit' => 3000],
                ['category' => 'Quality', 'title' => 'How will learning outcomes be identified and recognised (e.g. Europass)?', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'What is the expected impact on participants, your organisation, and target groups?', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'How will you share the results of your project within and beyond your organisation?', 'char_limit' => 3000],
            ],
        ],
        'ka152' => [
            'label' => 'KA152 — Mobility of young people (Youth Exchanges)',
            'sections' => [
                ['category' => 'Context', 'title' => 'What are the objectives of your project and how are they linked to the priorities you selected?', 'char_limit' => 3000],
                ['category' => 'Context', 'title' => 'Please describe the motivation for your project and explain why it should be funded.', 'char_limit' => 3000],
                ['category' => 'Context', 'title' => 'What topics will you address and what results do you expect?', 'char_limit' => 3000],
                ['category' => 'Preparation', 'title' => 'How and why did you choose your project partners? What experiences and competences will they bring?', 'char_limit' => 3000],
                ['category' => 'Preparation', 'title' => 'How will you organise the practical and logistical part of the project (travel, accommodation, insurance, visa, mentoring, preparatory meetings)?', 'char_limit' => 4000],
                ['category' => 'Activities', 'title' => 'Describe the Youth Exchange activity: programme, methods, daily flow, participants and group leaders.', 'char_limit' => 5000],
                ['category' => 'Activities', 'title' => 'Do you foresee virtual/blended activities or any virtual component before, during or after the activity?', 'char_limit' => 2000],
                ['category' => 'Quality', 'title' => 'How will you support participants to be aware of what they learned (reflection, documentation, Youthpass)?', 'char_limit' => 3000],
                ['category' => 'Quality', 'title' => 'How will you evaluate whether and to what extent your project reached its objectives and results?', 'char_limit' => 3000],
                ['category' => 'Follow-up', 'title' => 'What activities are foreseen after the end of the Youth Exchange? How will participants follow up?', 'char_limit' => 3000],
                ['category' => 'Follow-up', 'title' => 'How will you make your project visible and share its results outside your organisation and partners?', 'char_limit' => 3000],
            ],
        ],
        'ka210' => [
            'label' => 'KA210 — Small-scale partnerships',
            'sections' => [
                ['category' => 'Project description', 'title' => 'What are the concrete objectives you would like to achieve and outcomes or results you would like to realise? How are these objectives linked to the priorities you have selected?', 'char_limit' => 3000],
                ['category' => 'Project description', 'title' => 'Please outline the target groups of your project.', 'char_limit' => 2000],
                ['category' => 'Project description', 'title' => 'Please describe the motivation for your project and explain why it should be funded.', 'char_limit' => 3000],
                ['category' => 'Cooperation', 'title' => 'How does the project address the needs and goals of the participating organisations and the identified needs of their target groups?', 'char_limit' => 3000],
                ['category' => 'Cooperation', 'title' => 'What will be the benefits of cooperating with transnational partners to achieve the project objectives?', 'char_limit' => 3000],
                ['category' => 'Activities', 'title' => 'Describe the activities you plan to implement, including how they will help reach the objectives.', 'char_limit' => 5000],
                ['category' => 'Activities', 'title' => 'Describe the proposed methodology and how tasks and responsibilities are distributed among partners.', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'How will you assess whether the project objectives have been achieved?', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'What is the expected impact of the project, and how will you make the results sustainable beyond its lifetime?', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'How will you share the results of your project (within and outside the partnership)?', 'char_limit' => 3000],
            ],
        ],
        'ka220' => [
            'label' => 'KA220 — Cooperation partnerships',
            'sections' => [
                ['category' => 'Relevance', 'title' => 'What are the concrete objectives of the project? What results and outputs do you want to realise? How are these linked to the selected priorities?', 'char_limit' => 5000],
                ['category' => 'Relevance', 'title' => 'What makes your proposal innovative? How does it complement other initiatives already carried out?', 'char_limit' => 3000],
                ['category' => 'Relevance', 'title' => 'Why should this project be carried out transnationally? What are the expected benefits of cooperation?', 'char_limit' => 3000],
                ['category' => 'Partnership', 'title' => 'How did you form your partnership? Does it bring together complementary actors and the right mix of organisations?', 'char_limit' => 4000],
                ['category' => 'Partnership', 'title' => 'How will you ensure sound management, monitoring of quality, time and budget, and effective communication among partners?', 'char_limit' => 4000],
                ['category' => 'Activities', 'title' => 'Describe the work packages, main activities, expected outputs and timeline of the project.', 'char_limit' => 6000],
                ['category' => 'Activities', 'title' => 'How does the project incorporate the use of digital tools and the Erasmus+ horizontal priorities (inclusion, sustainability, digital, participation)?', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'How will you assess project results? What is the expected impact on participants, organisations and target groups?', 'char_limit' => 4000],
                ['category' => 'Impact', 'title' => 'How will you ensure the sustainability of the results after the funding ends?', 'char_limit' => 3000],
                ['category' => 'Impact', 'title' => 'Describe your dissemination plan: target audiences, channels, and how results will be made available (open licences).', 'char_limit' => 4000],
            ],
        ],
    ];

    public static function list(): array
    {
        $out = [];
        foreach (self::TEMPLATES as $key => $tpl) {
            $out[$key] = $tpl['label'];
        }

        return $out;
    }

    public static function sections(string $key): array
    {
        return self::TEMPLATES[$key]['sections'] ?? [];
    }
}
