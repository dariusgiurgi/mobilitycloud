<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use Illuminate\Database\Seeder;

class ContentBlockSeeder extends Seeder
{
    /**
     * Seeds the Content Library with reusable paragraphs extracted from an
     * approved KA152 application (Scoala de Jocuri). Organisation-specific
     * details are replaced with placeholders: [PROJECT], [PARTNER], [VENUE],
     * [N]. Fill them in when inserting, or keep as a starting point.
     */
    public function run(): void
    {
        $source = 'Scoala de Jocuri (KA152) — approved 2025';

        $blocks = [
            ['title' => 'Organisation background — Școala de Jocuri', 'category' => 'organisation', 'tags' => ['NGO', 'non-formal education', 'games'], 'body' => <<<'TXT'
Școala de Jocuri Association (formerly Muzicando Association) has operated as a volunteer-based informal group since 2013 and was officially founded in May 2018. It promotes and supports education through play using its own educational games, created and produced in Maramureș, Romania. The association's aims are: educating children and young people through non-formal means; the personal development of its members through educational games; creating environments suited to building and strengthening human relationships without discrimination of any kind; developing partnerships with organisations at home and abroad; and running activities for children and young people with disabilities or from disadvantaged backgrounds.
TXT],
            ['title' => 'Partner choice — newcomer applicant with experienced partner', 'category' => 'partners', 'tags' => ['rationale', 'mentor'], 'body' => <<<'TXT'
This is our organisation's first Erasmus+ project, although we have extensive experience working with children and young people and in promoting outdoor activities. We therefore sought a partner experienced in Erasmus+ projects, which is how we identified [PARTNER], an accredited association with a history of implementing more than ten Erasmus+ projects. It brings rich expertise in non-formal learning methods to our partnership, acting as a mentor and a model of good practice. The partnership was established on specific criteria to ensure successful implementation and alignment with the Erasmus+ Youth Quality Standards, including the partners' demonstrated capacity to meet and adapt to those standards and their commitment to addressing common challenges and needs across the target groups.
TXT],
            ['title' => 'Safety & protection of participants', 'category' => 'safety', 'tags' => ['child protection', 'risk', 'emergency'], 'body' => <<<'TXT'
The safety and protection of participants is a core value of the Erasmus+ programme, and our association is fully committed to it throughout the exchange. We firmly support every measure against abuse — verbal, physical, or emotional — and any report of abuse is treated with the utmost seriousness, ensuring a prompt and appropriate response.

Before mobility, each partner organisation runs live and online preparation sessions covering logistical, intercultural, and thematic aspects. Special attention is given to the protection of minors through parental consent and the collection of emergency contact details. Registration forms gather personal data, health conditions, allergies, and consent for data processing in line with Regulation (EU) 2016/679.

During mobility, strict safety rules apply: alcohol, weapons, flammable substances, and other items that could endanger participants are prohibited. A designated team member trained in first aid, with access to transport at all times, is responsible for emergency response. A complete first-aid kit is permanently available, and contacts for local hospitals, police, ambulance, and fire services are on hand. Participants agree a Code of Conduct on the first day of the activity.
TXT],
            ['title' => 'Preparation — intercultural, linguistic, risk-prevention', 'category' => 'methodology', 'tags' => ['preparation', 'info pack'], 'body' => <<<'TXT'
In the offline preparation phase, a communication group created during planning serves as the central platform for exchanging information between participants and partners; for those who do not use it, all relevant details are shared by email and through group leaders. The Info Pack covers logistics, weather, transport, activities, and a dedicated risk-prevention section.

Intercultural preparation forms an important part of the pre-activity phase: participants receive information on cultural norms, behaviours, and differences, plus a short guide of basic words and phrases in the host-country language. Linguistic preparation reassures participants that basic English is sufficient and that translation support will be available during activities. Risk prevention is central: the Info Pack details safety measures and emergency protocols, and participants complete forms with medical history, allergies, dietary restrictions, and special needs.
TXT],
            ['title' => 'Inclusion — participants with fewer opportunities', 'category' => 'inclusion', 'tags' => ['fewer opportunities', 'mentoring'], 'body' => <<<'TXT'
[N] of the participants will be young people with fewer opportunities. To meet their specific needs we will provide reinforced mentoring and involve experienced facilitators, including one with extensive expertise in supporting participants with fewer opportunities, ensuring an inclusive and motivating learning environment. Reinforced mentoring will be tailored to each person's needs and progress and will include one-to-one discussions, group meetings, online communication, and coaching sessions.

During mobility, the confidentiality of these participants is prioritised to protect their privacy and prevent any form of stigmatisation. Facilitators actively promote their inclusion, encouraging them to take on small tasks aligned with their strengths so they can gradually step out of their comfort zone at their own pace. After mobility, debriefing sessions help them reflect on and internalise their experience, with continued support for reintegration into their local communities.
TXT],
            ['title' => 'Evaluation — initial, mid-term, final', 'category' => 'evaluation', 'tags' => ['feedback', 'indicators'], 'body' => <<<'TXT'
We combine qualitative and quantitative methods across all phases. Before mobility, qualitative evaluation gathers participant feedback on the relevance of preparation sessions, while quantitative evaluation tracks engagement and communication (number of meetings, frequency of online calls, distribution of preparation resources).

During mobility we run three evaluations. The initial evaluation has participants reflect on their expectations, hopes, and perceived challenges, and the group agrees a Code of Conduct that all participants sign. Daily evaluation sessions allow reflection on the previous day and feedback to group leaders, who meet daily to review progress and make adjustments. The mid-term evaluation, at the midpoint, assesses progress using non-formal methods such as storytelling, the human thermometer, and letters to a future self. The final evaluation uses questionnaires, discussions, and reflection sessions, revisiting initial notes on fears and expectations.

After mobility, post-mobility evaluation reviews partner and participant contributions, the success of visibility activities, and overall impact through surveys and focus groups.
TXT],
            ['title' => 'Dissemination plan', 'category' => 'dissemination', 'tags' => ['social media', 'events'], 'body' => <<<'TXT'
On the last day of mobility, participants in each national group prepare their dissemination plans in a dedicated session, detailing planned activities, the anticipated timeline, and assigned responsibilities. Each group then presents its plan and receives feedback from facilitators and the other groups to ensure clarity and alignment with the project objectives.

After returning home, each national group organises at least two dissemination events in their communities, mainly in schools or local youth organisations. These feature interactive, participant-led presentations and promote the project objectives, the benefits of outdoor activities for mental and physical health, and the opportunities offered by Erasmus+. Sessions include interactive games learned during mobility to create a participatory environment. The target audience is young people aged 15–30 from partner communities, especially in education institutions and local youth organisations.
TXT],
            ['title' => 'Sustainability of results', 'category' => 'sustainability', 'tags' => ['follow-up', 'reuse'], 'body' => <<<'TXT'
We aim to create meaningful effects that continue after the project ends. Participants benefit from the skills and experiences gained during the exchange, which leave a lasting mark on their personal and professional lives — improving employability and social adaptability, and fostering tolerance, cultural understanding, and a shared European identity.

Beyond participants, the project leaves resources accessible to a wider audience: a dedicated project web page, the Info Pack, photos and videos, press releases, and other materials made available online so others can benefit. Results uploaded to the Erasmus+ Project Results Platform serve as an example for other organisations. The project also consolidates the existing partnership and creates new collaboration opportunities, with the links established between partner organisations serving as a basis for future initiatives.
TXT],
            ['title' => 'Green & environment-friendly practices', 'category' => 'environment', 'tags' => ['green', 'waste'], 'body' => <<<'TXT'
We integrate sustainability into every aspect of the project so participants leave with a lasting understanding of their role in protecting the environment. A dedicated session, "How to be green and eco-friendly during the exchange," explores practical ways to reduce environmental impact — minimising waste, conserving energy, and using sustainable resources — through group discussion, brainstorming, and the exchange of ideas.

The project also embeds sustainable practices in its design: participants travel to the venue by coach, significantly reducing the carbon footprint compared with individual transport. During mobility we encourage efficient use of resources and materials, minimise food waste, apply energy-saving measures, and recycle collected paper, plastic, and other materials, promoting these practices among young people and partner organisations.
TXT],
            ['title' => 'Recognition of learning outcomes — Youthpass', 'category' => 'recognition', 'tags' => ['Youthpass', 'key competences'], 'body' => <<<'TXT'
We use the Youthpass certificate to validate the competences gained during the exchange. On the first day, participants take part in a "Discovering Youthpass" session where facilitators present the certificate and the eight key competences for lifelong learning, and participants note the knowledge, skills, and competences they want to develop.

To keep participants aware of their progress, structured reflection sessions are built into the daily timetable with Youthpass at their centre. Each evening participants complete standardised personal journals, reflecting on their learning process and on the day's activities; the journal supports completion of the Youthpass and is reinforced through guided pair discussions. On the final day, participants consolidate their reflections to complete their certificates, with individual guidance from facilitators and group leaders so each participant documents their achievements accurately.
TXT],
            ['title' => 'Communication plan — internal & external', 'category' => 'communication', 'tags' => ['visibility', 'roles'], 'body' => <<<'TXT'
Internal communication: one project team member is responsible for internal communication. During preparation, online meetings are held as needed to monitor progress; during implementation, project coordinators meet daily; during dissemination, online follow-ups track progress against the plan. A messaging group is the central platform for storing and sharing project information, phone calls are used for urgent matters, and email for formal communication. Important decisions are recorded in writing.

External communication targets a wider audience — the general public, media, authorities, NGOs, and other stakeholders. Participants produce a press release in both languages, distributed to all partners for publication. Promotional materials (t-shirts, banners, leaflets) are distributed, and results are shared offline and online, including social media, partner websites, and EU dissemination portals. Daily updates and activity summaries keep the audience informed and engaged.
TXT],
            ['title' => 'Virtual / blended components', 'category' => 'virtual', 'tags' => ['digital tools', 'collaboration'], 'body' => <<<'TXT'
For virtual cooperation with partner organisations we use a range of digital tools to ensure effective collaboration; team members are familiar with digital communication platforms and experienced in organising online activities. Digital tools are also used for document storage so each partner can upload and access the files they need.

When working with young participants, digital tools are central to running preparatory sessions before mobility and to monitoring impact afterwards — for example interactive quizzes and evaluation forms to engage participants and track progress. During mobility, multimedia equipment supports activities through audio-visual projections and interactive quizzes. The activity programme includes a digital collaboration session focused on creating and managing the project's online presence.
TXT],
        ];

        foreach ($blocks as $block) {
            ContentBlock::updateOrCreate(
                ['owner_id' => null, 'title' => $block['title']],
                [
                    'category' => $block['category'],
                    'ka_action' => 'ka152',
                    'language' => 'en',
                    'body' => trim($block['body']),
                    'tags' => $block['tags'],
                    'is_proven' => true,
                    'source_note' => $source,
                ]
            );
        }

        $this->command?->info('Seeded '.count($blocks).' proven content blocks.');
    }
}
