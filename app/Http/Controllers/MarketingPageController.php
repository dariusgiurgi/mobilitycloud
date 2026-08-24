<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MarketingPageController extends Controller
{
    private const RESOURCE_PAGES = [
        'erasmus-project-management-platform' => [
            'title' => 'Erasmus+ project management platform',
            'description' => 'How MobilityCloud helps Erasmus+ teams manage applications, approved grants, budgets, participants, documents, mobility evidence and reporting workflows.',
            'eyebrow' => 'Project management',
            'hero' => 'A calmer way to manage Erasmus+ projects after approval.',
            'intro' => 'MobilityCloud keeps the operational side of Erasmus+ mobility projects structured after the grant is approved: budgets, participants, documents, daily mobility evidence, dissemination reports, outputs and final preparation.',
            'sections' => [
                ['title' => 'Why Erasmus+ projects become difficult to manage', 'body' => 'Approved mobility projects often move quickly from application writing to real-world implementation. Teams suddenly need to coordinate participants, partner organisations, documents, travel evidence, expenses, dissemination proof and final reporting files. When this work is split across spreadsheets, folders and messages, important evidence can be missed.'],
                ['title' => 'What MobilityCloud centralises', 'body' => 'The platform is organised around one project record. Application context, approved grant value, budget baskets, participant lists, document files, mobility days, dissemination reports and outputs stay connected so the project owner can see what still needs attention.'],
                ['title' => 'Built for project owners and collaborators', 'body' => 'Project owners can invite collaborators to a specific project and give them the appropriate level of access. This makes it easier to involve editors, mobility facilitators, partner organisations or viewers without exposing unrelated projects.'],
            ],
            'faqs' => [
                ['q' => 'Is MobilityCloud an official Erasmus+ platform?', 'a' => 'No. MobilityCloud is an independent platform powered by Xeotype. Official rules, forms and decisions remain with the Erasmus+ Programme Guide, National Agencies and other competent authorities.'],
                ['q' => 'Can it be used only after approval?', 'a' => 'No. It supports writing and planning before approval, then unlocks management workflows after the project owner declares the approved grant value.'],
            ],
        ],
        'erasmus-mobility-documents' => [
            'title' => 'Erasmus+ mobility documents',
            'description' => 'A practical overview of how MobilityCloud keeps Erasmus+ project files, generated records, signed copies and participant documents organised.',
            'eyebrow' => 'Documents',
            'hero' => 'Keep Erasmus+ mobility documents understandable, not buried.',
            'intro' => 'MobilityCloud is designed to reduce document chaos by keeping project files, generated documents, signed copies and supporting records inside the relevant project instead of scattered across local folders.',
            'sections' => [
                ['title' => 'The document problem in mobility projects', 'body' => 'Mobility projects usually produce many files: applications, agreements, attendance lists, participant forms, reports, signed copies, invoices, evidence and internal notes. Without a clear structure, teams can lose time searching for the latest version.'],
                ['title' => 'How files are organised', 'body' => 'The platform separates project documents from mobility evidence and budget proof. This prevents every uploaded photo, expense file or dissemination attachment from turning into one endless mixed list.'],
                ['title' => 'Generated and uploaded documents', 'body' => 'MobilityCloud can support generated records and uploaded signed copies. The goal is to make the file state clear: what exists, what is still pending and what belongs to final reporting preparation.'],
            ],
            'faqs' => [
                ['q' => 'Does MobilityCloud replace official document templates?', 'a' => 'No. It helps teams organise and generate working documents, but users must verify all official requirements with the relevant programme and authority.'],
                ['q' => 'Can files be downloaded later?', 'a' => 'Yes. Project files and supporting records are intended to remain downloadable to authorised users with access to that project.'],
            ],
        ],
        'erasmus-final-report-evidence' => [
            'title' => 'Erasmus+ final report evidence',
            'description' => 'How to organise mobility evidence, daily proof, dissemination reports, materials and outputs before final reporting.',
            'eyebrow' => 'Evidence',
            'hero' => 'Prepare final report evidence while the project is happening.',
            'intro' => 'MobilityCloud encourages teams to collect evidence during implementation, not only when the final report deadline arrives.',
            'sections' => [
                ['title' => 'Evidence should follow the mobility timeline', 'body' => 'Daily evidence is easier to review when it follows the actual activity timeline. MobilityCloud supports evidence by day, including title, date, description, images, links, files and observations.'],
                ['title' => 'Dissemination and outputs', 'body' => 'Dissemination reports, external photo folders, final video links, materials and outputs can be separated from general documents. This helps teams explain what happened and where public proof or deliverables can be found.'],
                ['title' => 'Final archive direction', 'body' => 'The long-term goal is to let project owners select what should be included in a final archive, keeping evidence ordered and understandable for final reporting and future checks.'],
            ],
            'faqs' => [
                ['q' => 'Does MobilityCloud submit the final report automatically?', 'a' => 'No. It helps organise evidence and files. Official final report submission remains the responsibility of the user in the official system.'],
                ['q' => 'Can evidence include external links?', 'a' => 'Yes. Teams can keep links to photo folders, social media posts, videos or other public proof connected to the project evidence.'],
            ],
        ],
        'erasmus-budget-tracking' => [
            'title' => 'Erasmus+ budget tracking',
            'description' => 'How MobilityCloud helps approved Erasmus+ projects track budget baskets, expenses, currencies, evidence files and remaining balances.',
            'eyebrow' => 'Budget',
            'hero' => 'Track approved Erasmus+ budgets with evidence attached.',
            'intro' => 'MobilityCloud helps teams move from an approved grant value to practical budget baskets, expenses, supporting evidence and spending visibility.',
            'sections' => [
                ['title' => 'From approved grant to practical control', 'body' => 'After approval, project teams need to understand what has been allocated, what has been spent and what still needs evidence. MobilityCloud keeps spending inside project budget baskets.'],
                ['title' => 'Evidence matters', 'body' => 'Expenses can include attached supporting files. Renaming and organising files by expense number makes evidence easier to review later.'],
                ['title' => 'Currencies and reporting clarity', 'body' => 'Projects can work with currencies and converted amounts while preserving the final project context. Budget tracking is informational and must be checked against official accounting requirements.'],
            ],
            'faqs' => [
                ['q' => 'Is MobilityCloud accounting software?', 'a' => 'No. It is a project management platform. Users remain responsible for accounting, tax treatment and official financial reporting.'],
                ['q' => 'Can budgets be managed before approval?', 'a' => 'Budget planning tools can be used before approval, while full management workflows are designed for projects marked as approved.'],
            ],
        ],
        'mobilitycloud-partner-sharing-kit' => [
            'title' => 'MobilityCloud partner & sharing kit',
            'description' => 'Official short descriptions, sharing text and backlink guidance for Erasmus+ trainers, NGOs, schools and partner organisations that want to mention MobilityCloud.',
            'eyebrow' => 'Partner kit',
            'hero' => 'Share MobilityCloud clearly and consistently.',
            'intro' => 'This page provides public wording that partners, trainers, NGOs, schools and collaborators can use when mentioning MobilityCloud in articles, newsletters, resource lists or Erasmus+ community posts.',
            'sections' => [
                ['title' => 'Official short description', 'body' => 'MobilityCloud is a professional Erasmus+ project platform for application writing, approved-project management, budget control, participant records, mobility evidence, documents and final reporting preparation.'],
                ['title' => 'Longer description for articles and partner pages', 'body' => 'MobilityCloud helps organisations keep Erasmus+ mobility projects structured from writing and planning to approved-project implementation. The platform connects budgets, participants, documents, tasks, mobility evidence, dissemination reports, materials and outputs inside one project environment, so teams can prepare cleaner records and reduce scattered files.'],
                ['title' => 'Suggested backlink text', 'body' => 'Recommended link text: Erasmus+ project management platform, Erasmus+ mobility project documents, Erasmus+ budget tracking, Erasmus+ final report evidence, or MobilityCloud. The preferred destination is https://mobilitycloud.eu/ or a relevant resource page when the article is about a specific topic.'],
                ['title' => 'Social post example', 'body' => 'Useful tool for Erasmus+ teams: MobilityCloud helps structure applications, approved budgets, participants, project files, mobility evidence and final reporting workflows in one platform. More information: https://mobilitycloud.eu/'],
                ['title' => 'Independence and attribution', 'body' => 'MobilityCloud is independent software powered by Xeotype. It is not an official Erasmus+ or European Commission service and does not replace official Programme Guide requirements, National Agency instructions, legal advice or accounting advice.'],
            ],
            'copy_blocks' => [
                [
                    'label' => 'LinkedIn post',
                    'text' => "Managing Erasmus+ projects can become complicated quickly: applications, approved budgets, participant records, documents, mobility evidence, dissemination reports and final reporting files often end up spread across folders and spreadsheets.\n\nMobilityCloud was built to make this work more structured. It helps Erasmus+ teams write applications and manage approved projects in one project environment, with budgets, participants, documents, evidence and tasks connected from the beginning.\n\nLearn more: https://mobilitycloud.eu/",
                ],
                [
                    'label' => 'Facebook / community post',
                    'text' => "Useful tool for Erasmus+ teams: MobilityCloud helps organise applications, approved budgets, participants, documents, mobility evidence and final report preparation in one place.\n\nIt is especially helpful for organisations that want fewer scattered files and a clearer project workflow.\n\nMore info: https://mobilitycloud.eu/",
                ],
                [
                    'label' => 'Short email to Erasmus+ partners',
                    'text' => "Hello,\n\nI wanted to share MobilityCloud, a platform designed for Erasmus+ project writing and approved-project management. It helps teams keep budgets, participants, documents, mobility evidence, dissemination reports and final reporting preparation connected inside one project workspace.\n\nYou can find more information here: https://mobilitycloud.eu/\n\nMobilityCloud is independent software powered by Xeotype and does not replace official Erasmus+ guidance or National Agency instructions.",
                ],
                [
                    'label' => 'NGO / school resource directory text',
                    'text' => 'MobilityCloud is an Erasmus+ project writing and management platform for organisations that need a clearer way to handle applications, approved budgets, participant records, project documents, mobility evidence, dissemination reports and final reporting preparation. Website: https://mobilitycloud.eu/',
                ],
                [
                    'label' => 'Trainer or consultant referral text',
                    'text' => 'For organisations that struggle with Erasmus+ project files after approval, MobilityCloud offers a structured way to manage budgets, participants, documents, mobility evidence, dissemination reports and implementation tasks. It can be useful as a project-management companion alongside official Erasmus+ systems and National Agency requirements: https://mobilitycloud.eu/',
                ],
                [
                    'label' => 'Very short mention',
                    'text' => 'MobilityCloud is a professional platform for Erasmus+ application writing and approved-project management: https://mobilitycloud.eu/',
                ],
            ],
            'faqs' => [
                ['q' => 'Can partners link directly to MobilityCloud?', 'a' => 'Yes. Public links to the MobilityCloud homepage or resource pages are welcome, especially from relevant Erasmus+, NGO, school, training or project-management contexts.'],
                ['q' => 'What wording should be avoided?', 'a' => 'Avoid presenting MobilityCloud as an official Erasmus+ platform, funding authority, National Agency tool, legal adviser or accounting service.'],
                ['q' => 'Who operates MobilityCloud?', 'a' => 'MobilityCloud is powered and operated by Xeotype. Public business and support enquiries can be sent to contact@mobilitycloud.eu.'],
            ],
        ],
    ];

    public function home(): View
    {
        return $this->page('home');
    }

    public function features(): View
    {
        return $this->page('features');
    }

    public function pricing(): View
    {
        return $this->page('pricing');
    }

    public function guide(): View
    {
        return $this->page('guide');
    }

    public function help(): View
    {
        return $this->page('help');
    }

    public function contact(): View
    {
        return $this->page('contact');
    }

    public function resources(): View
    {
        return view('public.resources', [
            'pages' => self::RESOURCE_PAGES,
            'company' => config('mobilitycloud.company'),
            'emails' => config('mobilitycloud.emails'),
        ]);
    }

    public function resource(string $slug): View
    {
        abort_unless(array_key_exists($slug, self::RESOURCE_PAGES), 404);

        return view('public.resource-page', [
            'slug' => $slug,
            'resource' => self::RESOURCE_PAGES[$slug],
            'company' => config('mobilitycloud.company'),
            'emails' => config('mobilitycloud.emails'),
        ]);
    }

    private function page(string $page): View
    {
        return view('public.marketing', [
            'page' => $page,
            'company' => config('mobilitycloud.company'),
            'emails' => config('mobilitycloud.emails'),
        ]);
    }
}
