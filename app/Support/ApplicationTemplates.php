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
            'officially_verified' => true,
            'sections' => [
                ['key' => 'additional-funding-needs', 'category' => 'Activities', 'title' => 'Have you, at this stage, identified the need of any specific additional funding such as Exceptional costs for expensive travel, visas, financial guarantee, or Inclusion support for participants etc.? If this is the case, please fill in the table below.', 'char_limit' => null, 'guidance' => 'Official KA151-YOU form prompt. Use the activity/budget table for the detailed cost type, participants, description, justification and estimated cost.'],
                ['key' => 'virtual-blended-components', 'category' => 'Virtual learning/Blended activities and use of virtual components', 'title' => 'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?', 'char_limit' => null, 'guidance' => 'Official KA151-YOU form prompt. Answer consistently with the activity table and the use of digital tools or learning methods.'],
                ['key' => 'participant-contributions', 'category' => 'Participant contribution and fees', 'title' => 'Are you planning to ask for any contributions from participants?', 'char_limit' => null, 'guidance' => 'Official KA151-YOU form prompt. If yes, justify the contribution and ensure it does not create unfair barriers to participation.'],
            ],
        ],
        'ka152-you' => [
            'label' => 'KA152-YOU - Youth Exchanges',
            'action' => 'KA152-YOU',
            'call_year' => 2026,
            'form_id' => 'KA152-YOU-801EC704',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Mobility%20of%20young%20people%20%28KA152-YOU%29_watermark.pdf',
            'description' => 'Mobility of young people through Youth Exchanges.',
            'officially_verified' => true,
            'sections' => [
                ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'What do you want to achieve by implementing the project? What are the objectives of your project? Please specify from the perspective of youth work practice.', 'char_limit' => 2000, 'guidance' => 'Write a concise public summary. State the change you want to create, for whom and why it matters.'],
                ['key' => 'summary-activities', 'category' => 'Project summary', 'title' => 'What activities do you plan to implement? What is the number and profile of the participants involved?', 'char_limit' => 2000, 'guidance' => 'Summarise the activity format, countries, duration, participant profile and approximate numbers.'],
                ['key' => 'summary-impact', 'category' => 'Project summary', 'title' => 'What results and impact do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Distinguish participant learning, organisational results and wider community impact.'],
                ['key' => 'needs-objectives', 'category' => 'Project rationale', 'title' => 'Why do you want to carry out this project? Please describe the issues and needs you want to address and your project’s objectives.', 'char_limit' => 5000, 'guidance' => 'Use evidence and partner perspectives. Turn each identified need into a specific, measurable objective.'],
                ['key' => 'programme-link', 'category' => 'Project rationale', 'title' => 'How does your project link to the objectives of the Erasmus programme and those of Youth Exchanges?', 'char_limit' => 3000, 'guidance' => 'Explain the link rather than listing programme priorities. Show how the design puts them into practice.'],
                ['key' => 'participant-benefit', 'category' => 'Project rationale', 'title' => 'How will your project benefit the young participants involved in the project, during and after the project lifetime?', 'char_limit' => 3000, 'guidance' => 'Cover knowledge, skills, attitudes, participation and how learning will transfer after mobility.'],
                ['key' => 'organisation-benefit', 'category' => 'Project rationale', 'title' => 'How will your project benefit the organisations or the groups of young people implementing the project, during and after the project lifetime?', 'char_limit' => 3000, 'guidance' => 'Describe improved youth-work practice, capacity, partnerships and follow-up use of results.'],
                ['key' => 'wider-impact', 'category' => 'Project rationale', 'title' => 'What would be the impact of your project beyond the participants and participating organisations, at local, regional, national, if any European level ?', 'char_limit' => 3000, 'guidance' => 'Describe credible effects beyond direct participants, with a clear scale and audience.'],
                ['key' => 'topics', 'category' => 'Topic', 'title' => 'Please select up to three topics addressed by your project', 'char_limit' => null, 'guidance' => 'Select the topics that genuinely match the project logic and are visible in objectives, activities and impact.'],
                ['key' => 'participant-contributions', 'category' => 'Participant contribution and fees', 'title' => 'Are you planning to charge participation fees ?', 'char_limit' => null, 'guidance' => 'If yes, justify the amount, purpose and safeguards so no unfair barrier is created for participants.'],
                ['key' => 'activity-participant-background', 'category' => 'Description of the activity', 'title' => 'Please describe the background of the participants in each participating group and how each group was formed. Please also provide information on the group leaders, the age of the participants and how country balance is ensured. If necessary, explain how the gender balance is respected.', 'char_limit' => 5000, 'guidance' => 'Describe participant profile, selection, country balance, age range, leaders and inclusion logic.'],
                ['key' => 'activity-participant-involvement', 'category' => 'Description of the activity', 'title' => 'Please describe the role and involvement of the participants from each participating group in all phases (planning before submitting application, preparation, implementation of activities and follow-up).', 'char_limit' => 5000, 'guidance' => 'Show meaningful youth participation before, during and after the mobility, with concrete responsibilities by group.'],
                ['key' => 'activity-learning-outcomes', 'category' => 'Description of the activity', 'title' => 'What will the participants learn about the chosen topic of the activity? Which learning outcomes or competences (i.e. knowledge, skills and attitudes/behaviours) are to be acquired/improved by participants in the activity?', 'char_limit' => 5000, 'guidance' => 'Link topic, methods and activities to knowledge, skills, attitudes and the key competences.'],
                ['key' => 'activity-basic-elements', 'category' => 'Description of the activity', 'title' => 'What are the basic elements of the activity? Please describe at the very least the venue(s), non formal learning methods used, aims of the session etc.', 'char_limit' => 5000, 'guidance' => 'Describe venue, daily flow, session aims, non-formal methods, safety and how learning is structured.'],
                ['key' => 'activity-group-cooperation', 'category' => 'Description of the activity', 'title' => 'How will the groups of participants cooperate and communicate between them to prepare and follow-up on the Youth Exchange?', 'char_limit' => 3500, 'guidance' => 'Explain pre-mobility communication, tools, meetings, shared preparation and follow-up coordination.'],
                ['key' => 'preparation-support', 'category' => 'Project design', 'title' => 'How will you prepare the participants before the start of the activity (e.g. intercultural, linguistic, risk-prevention etc.) and how will you support them during and after the activity?', 'char_limit' => 4000, 'guidance' => 'Cover intercultural and linguistic preparation, risk prevention, mentoring, accessibility and post-activity support.'],
                ['key' => 'safety-protection', 'category' => 'Project design', 'title' => 'What measures will you put in place to ensure the safety and protection of participants?', 'char_limit' => 3000, 'guidance' => 'Include safeguarding roles, emergency procedures, insurance, consent, reporting and activity-specific risks.'],
                ['key' => 'follow-up', 'category' => 'Project design', 'title' => 'What activities are foreseen after the end of the Youth Exchange? How will the participants follow-up on the activity?', 'char_limit' => 3000, 'guidance' => 'Name owners, timing and concrete local actions after the mobility.'],
                ['key' => 'learning-awareness', 'category' => 'Project design', 'title' => 'How will you support participants to be aware of what they have learned and which competences they have developed or improved? Please remember to include the methods that support reflection and documentation of the learning outcomes in the daily timetable of each activity.', 'char_limit' => 3500, 'guidance' => 'Explain daily reflection methods, facilitator support and learning evidence.'],
                ['key' => 'european-certificates', 'category' => 'Project design', 'title' => 'The Erasmus Programme promotes the use of instruments/certificates like Youthpass or Europass , to validate the competences acquired by the participants during their experiences abroad. Will your project make use of such European instruments/certificates?', 'char_limit' => null, 'guidance' => 'Explain how Youthpass, Europass or equivalent tools will be used if applicable.'],
                ['key' => 'european-certificates-which', 'category' => 'Project design', 'title' => 'Which one(s)?', 'char_limit' => null, 'guidance' => 'Name the European recognition instrument, normally Youthpass for youth exchanges.'],
                ['key' => 'national-certificates', 'category' => 'Project design', 'title' => 'Are you planning to use any national instrument/certificate? If so, please describe which one.', 'char_limit' => null, 'guidance' => 'Mention national recognition tools only where relevant.'],
                ['key' => 'fewer-opportunities', 'category' => 'Participant with fewer opportunities', 'title' => 'Are there participants involved in the activities who face situations that make their participation in the activities more difficult?', 'char_limit' => null, 'guidance' => 'Answer consistently with the participant profile and inclusion budget.'],
                ['key' => 'fewer-opportunities-types', 'category' => 'Participant with fewer opportunities', 'title' => 'Which types of situations are these participants facing?', 'char_limit' => null, 'guidance' => 'Select or describe barriers respectfully, without stigmatising participants.'],
                ['key' => 'fewer-opportunities-measures', 'category' => 'Participant with fewer opportunities', 'title' => 'If any, please explain the particular measures (accompanying person, reinforced preparation etc.) you will put in place to cater for the specific needs of these participants and/or to support their participation.', 'char_limit' => 4000, 'guidance' => 'Explain concrete support before, during and after mobility, including confidentiality and dignified participation.'],
                ['key' => 'virtual-blended-components', 'category' => 'Project design', 'title' => 'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?', 'char_limit' => null, 'guidance' => 'Only include tools and practices that have a clear purpose in preparation, learning, cooperation or impact.'],
                ['key' => 'virtual-blended-description', 'category' => 'Virtual learning/Blended activities and use of virtual components', 'title' => 'If yes, please describe them.', 'char_limit' => 3000, 'guidance' => 'Describe virtual tools and components by phase: preparation, mobility, cooperation, follow-up and dissemination.'],
                ['key' => 'virtual-blended-share', 'category' => 'Virtual learning/Blended activities and use of virtual components', 'title' => 'Please provide an estimated share of participants (excluding accompanying persons, group leaders, trainers and facilitators) that will use Virtual components in their activities.', 'char_limit' => null, 'guidance' => 'Provide a percentage consistent with the activity design.'],
                ['key' => 'environmental-practices', 'category' => 'Environmental friendly practices', 'title' => 'Will you include sustainable and environmental-friendly practices in your activities?', 'char_limit' => null, 'guidance' => 'Answer consistently with travel, venue, materials and activity design.'],
                ['key' => 'environmental-practices-description', 'category' => 'Environmental friendly practices', 'title' => 'Please describe them and mention how will you raise the awareness of participants on these sustainable practices?', 'char_limit' => 3000, 'guidance' => 'Describe concrete green practices and how participants will learn, apply and transfer them.'],
                ['key' => 'management-quality', 'category' => 'Project management', 'title' => 'How will you manage the project (agreements with partners etc.) and make sure that it is done in line with the Erasmus Youth Quality Standards? You will find the quality standards further down in the application form.', 'char_limit' => 5000, 'guidance' => 'Cover agreements, roles, timeline, quality control and financial control.'],
                ['key' => 'logistics', 'category' => 'Project management', 'title' => 'How will you organise the practical and logistical part of the project (e.g. travel, accommodation, insurance, visa, social security, mentoring and support, preparatory meetings with partners etc.)?', 'char_limit' => 4500, 'guidance' => 'Define responsibilities for practical arrangements and support.'],
                ['key' => 'partner-choice', 'category' => 'Project management', 'title' => 'How and why did you choose your project partners? What experiences and competences will they bring to the project?', 'char_limit' => 3500, 'guidance' => 'Show complementary expertise and relevance.'],
                ['key' => 'partner-communication', 'category' => 'Project management', 'title' => 'How will you communicate with them?', 'char_limit' => 2500, 'guidance' => 'Define communication rhythm, tools, decision points and escalation.'],
                ['key' => 'partner-coordination', 'category' => 'Project management', 'title' => 'How will you monitor and coordinate their contribution?', 'char_limit' => 2500, 'guidance' => 'Explain monitoring, responsibilities and evidence of contribution.'],
                ['key' => 'other-actors', 'category' => 'Project management', 'title' => 'Which other actors (organisations or individuals) will be involved and how?', 'char_limit' => 2500, 'guidance' => 'Mention only relevant actors with a clear role.'],
                ['key' => 'evaluation', 'category' => 'Project management', 'title' => 'How will you evaluate your project’s success? Which activities will you carry out in order to assess whether, and to what extent, your project has reached its objectives and results?', 'char_limit' => 3500, 'guidance' => 'Pair each objective with indicators, evidence, timing and a responsible person.'],
                ['key' => 'sustainability-effects', 'category' => 'Project management', 'title' => 'What will you do to make sure that your project continues to have effects also after it ends?', 'char_limit' => 3000, 'guidance' => 'Explain continuation of outcomes and practices.'],
                ['key' => 'sustainability-results', 'category' => 'Project management', 'title' => 'Are you planning measures to make sure that the results produced are used and beneficial to others beyond the project’s lifetime? If yes, which ones?', 'char_limit' => 3000, 'guidance' => 'Define users, settings, channels and resources.'],
                ['key' => 'dissemination', 'category' => 'Project management', 'title' => 'How will you make your project visible outside your organisation and partner organisations? How will you share its results and success? With whom will you share the results?', 'char_limit' => 3500, 'guidance' => 'Define audiences, messages, channels, outputs, timing, owners and evidence of reach.'],
                ['key' => 'participant-dissemination', 'category' => 'Project management', 'title' => 'How will you involve participants in such activities?', 'char_limit' => 2500, 'guidance' => 'Specify participant roles in visibility, dissemination and follow-up.'],
            ],
        ],
        'ka153-you' => [
            'label' => 'KA153-YOU - Mobility of youth workers',
            'action' => 'KA153-YOU', 'call_year' => 2026, 'form_id' => 'KA153-YOU-3F05A132',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Mobility%20of%20youth%20workers%20%28KA153-YOU%29_watermark.pdf',
            'description' => 'Professional development and system-development activities for youth workers.',
            'officially_verified' => true,
            'sections' => [
                ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'What do you want to achieve by implementing the project? What are the objectives of your project? Please specify from the perspective of youth work practice.', 'char_limit' => 2000, 'guidance' => 'Summarise the intended improvement in youth-work practice and its beneficiaries.'],
                ['key' => 'summary-activities', 'category' => 'Project summary', 'title' => 'What activities do you plan to implement? What is the number and profile of the participants involved?', 'char_limit' => 2000, 'guidance' => 'Summarise professional development activities, participants, duration and countries.'],
                ['key' => 'summary-impact', 'category' => 'Project summary', 'title' => 'What results and impact do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Include impact on youth workers, organisations, young people and youth work more broadly.'],
                ['key' => 'aims-needs-objectives', 'category' => 'Project rationale', 'title' => 'Why do you want to carry out this project? Please describe the idea of your project, including the needs and objectives. Please also mention how the project fits the needs of the participating organisations.', 'char_limit' => 5000, 'guidance' => 'Base the rationale on practice needs and convert them into observable professional-development objectives.'],
                ['key' => 'needs-identification', 'category' => 'Project rationale', 'title' => 'How have the needs been identified and how does your project tackle them?', 'char_limit' => 3500, 'guidance' => 'Explain the evidence, consultation and link between needs and design.'],
                ['key' => 'programme-link', 'category' => 'Project rationale', 'title' => 'How does your project link to the objectives of the Erasmus+ programme and those of Youth Workers Mobility?', 'char_limit' => 3000, 'guidance' => 'Demonstrate the connection through project choices and methods.'],
                ['key' => 'target-groups', 'category' => 'Project rationale', 'title' => 'What are the main target groups of your project?', 'char_limit' => 2500, 'guidance' => 'Describe youth-worker profiles, experience, roles, needs and the young people ultimately reached.'],
                ['key' => 'practice-benefit', 'category' => 'Project rationale', 'title' => 'How will your project benefit the youth workers and their organisations in their daily work with young people, during and after the project lifetime?', 'char_limit' => 3500, 'guidance' => 'Explain transfer into daily practice during and after the project.'],
                ['key' => 'youth-work-development', 'category' => 'Project rationale', 'title' => 'How do you expect to contribute with your project to the development of youth work in general? How will you ensure the impact of the project beyond the participants and participating organisations, at local, regional, national, if any European level ?', 'char_limit' => 3500, 'guidance' => 'Describe transferability, networks, methods, policy links or reusable outputs.'],
                ['key' => 'participant-contributions', 'category' => 'Participant contribution and fees', 'title' => 'Are you planning to ask for any contributions from participants?', 'char_limit' => null, 'guidance' => 'If yes, justify the contribution and ensure it does not create unfair barriers to participation.'],
                ['key' => 'non-formal-learning', 'category' => 'Project design', 'title' => 'What non-formal learning methods will you use in your project? What will you do to be sure that the methods allowing them to learn are of high quality?', 'char_limit' => 4000, 'guidance' => 'Describe methods, facilitation choices and quality assurance.'],
                ['key' => 'preparation-support', 'category' => 'Project design', 'title' => 'How will you prepare the participants before the start of the activity (e.g. intercultural, linguistic, risk-prevention etc.) and how will you support them during and after the activities?', 'char_limit' => 4000, 'guidance' => 'Cover preparation, support during activities and transfer to practice afterwards.'],
                ['key' => 'safety-protection', 'category' => 'Project design', 'title' => 'What measures will you put in place to ensure the safety and protection of participants?', 'char_limit' => 3000, 'guidance' => 'Include safeguarding roles, emergency procedures, insurance, consent, reporting and activity-specific risks.'],
                ['key' => 'follow-up', 'category' => 'Project design', 'title' => 'What activities are foreseen after the end of the Professional Development Activity? How will the participants follow-up on the activity?', 'char_limit' => 3000, 'guidance' => 'Name owners, timing and concrete post-activity actions.'],
                ['key' => 'learning-awareness', 'category' => 'Project design', 'title' => 'How will you support participants to be aware of what they have learned and which competences they have developed or improved? Please remember to include the methods that support reflection and documentation of the learning outcomes in the daily timetable of each activity.', 'char_limit' => 3500, 'guidance' => 'Describe reflection methods, learning evidence and Youthpass or national instruments.'],
                ['key' => 'european-certificates', 'category' => 'Project design', 'title' => 'The Erasmus Programme promotes the use of instruments/certificates like Youthpass or Europass , to validate the competences acquired by the participants during their experiences abroad. Will your project make use of such European instruments/certificates?', 'char_limit' => null, 'guidance' => 'Explain how Youthpass, Europass or equivalent tools will be used if applicable.'],
                ['key' => 'national-certificates', 'category' => 'Project design', 'title' => 'Are you planning to use any national instrument/certificate? If so, please describe which one.', 'char_limit' => null, 'guidance' => 'Mention national recognition tools only where relevant.'],
                ['key' => 'fewer-opportunities', 'category' => 'Project design', 'title' => 'Are participants involved in activities facing challenges that hinder their participation?', 'char_limit' => null, 'guidance' => 'Describe barriers and support measures if applicable.'],
                ['key' => 'virtual-blended-components', 'category' => 'Project design', 'title' => 'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?', 'char_limit' => null, 'guidance' => 'Explain the purpose of virtual or blended components.'],
                ['key' => 'environmental-practices', 'category' => 'Project design', 'title' => 'Will you include sustainable and environmental-friendly practices in your activities?', 'char_limit' => null, 'guidance' => 'Describe concrete sustainable choices and how they will be applied.'],
                ['key' => 'management-quality', 'category' => 'Project management', 'title' => 'How will you manage the project (agreements with partners etc.) and make sure that it is done in line with the Erasmus Youth Quality Standards? You will find the quality standards further down in the application form.', 'char_limit' => 5000, 'guidance' => 'Define roles, agreements, timeline, monitoring and quality control.'],
                ['key' => 'logistics', 'category' => 'Project management', 'title' => 'How will you organise the practical and logistical part of the project (e.g. travel, accommodation, insurance, visa, social security, mentoring and support, preparatory meetings with partners etc.)?', 'char_limit' => 4500, 'guidance' => 'Define practical arrangements, responsibilities and risk controls.'],
                ['key' => 'partner-choice', 'category' => 'Project management', 'title' => 'How and why did you choose your project partners? What experiences and competences will they bring to the project?', 'char_limit' => 3500, 'guidance' => 'Show complementary competence and relevance.'],
                ['key' => 'partner-communication', 'category' => 'Project management', 'title' => 'How will you communicate with them?', 'char_limit' => 2500, 'guidance' => 'Define communication rhythm, tools, decisions and escalation.'],
                ['key' => 'partner-coordination', 'category' => 'Project management', 'title' => 'How will you monitor and coordinate their contribution?', 'char_limit' => 2500, 'guidance' => 'Explain monitoring, responsibilities and evidence of contribution.'],
                ['key' => 'other-actors', 'category' => 'Project management', 'title' => 'Which other actors (organisations or individuals) will be involved and how?', 'char_limit' => 2500, 'guidance' => 'Mention only relevant actors with a clear role.'],
                ['key' => 'evaluation', 'category' => 'Project management', 'title' => 'How will you evaluate your project’s success? Which activities will you carry out in order to assess whether, and to what extent, your project has reached its objectives and results?', 'char_limit' => 3500, 'guidance' => 'Use indicators for learning, transfer to practice, organisational benefit and wider youth-work impact.'],
                ['key' => 'sustainability-effects', 'category' => 'Project management', 'title' => 'What will you do to make sure that your project continues to have effects also after it ends?', 'char_limit' => 3000, 'guidance' => 'Explain continuation of outcomes and practices.'],
                ['key' => 'sustainability-results', 'category' => 'Project management', 'title' => 'Are you planning measures to make sure that the results produced are used and beneficial to others beyond the project’s lifetime? If yes, which ones?', 'char_limit' => 3000, 'guidance' => 'Define users, settings, channels and resources.'],
                ['key' => 'dissemination', 'category' => 'Project management', 'title' => 'How will you make your project visible outside your organisation and partner organisations? How will you share its results and success? With whom will you share the results?', 'char_limit' => 3500, 'guidance' => 'Specify audiences, formats, channels, timing and ownership.'],
                ['key' => 'participant-dissemination', 'category' => 'Project management', 'title' => 'How will you involve participants in such activities?', 'char_limit' => 2500, 'guidance' => 'Specify participant roles in visibility, dissemination and follow-up.'],
            ],
        ],
        'ka154-you' => [
            'label' => 'KA154-YOU - Youth participation activities',
            'action' => 'KA154-YOU', 'call_year' => 2026, 'form_id' => 'KA154-YOU-26CD668E',
            'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Youth%20participation%20activities%20%28KA154-YOU%29_watermark_0.pdf',
            'description' => 'Local, national or transnational youth participation projects.',
            'officially_verified' => true,
            'sections' => [
                ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'What do you want to achieve by implementing the project? What are the objectives of your project?', 'char_limit' => 2000, 'guidance' => 'Summarise the participation change and the objectives in clear public language.'],
                ['key' => 'summary-activities', 'category' => 'Project summary', 'title' => 'What activities do you plan to implement? What is the number and profile of the participants involved?', 'char_limit' => 2000, 'guidance' => 'Summarise activity formats, reach, participant profiles and geography.'],
                ['key' => 'summary-impact', 'category' => 'Project summary', 'title' => 'What results and impact do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Cover young people actively involved, wider target groups and participating organisations.'],
                ['key' => 'aims-needs-objectives', 'category' => 'Project rationale', 'title' => 'Please describe the idea of your project including the needs you have identified (e.g. needs of participating organisations and young people actively involved in the preparation and implementation of the project, as well as the needs of young people targeted by the activities) and the project’s objectives.', 'char_limit' => 5000, 'guidance' => 'Ground the project in young people’s realities and show how they helped define the needs.'],
                ['key' => 'youth-dialogue-goals', 'category' => 'Project rationale', 'title' => 'Does your project address one or more of the priorities defined in the context of the EU Youth Dialogue or the European Youth Goals ? If yes, please explain how.', 'char_limit' => 3000, 'guidance' => 'Explain the link to Youth Dialogue or Youth Goals only where relevant.'],
                ['key' => 'programme-link', 'category' => 'Project rationale', 'title' => 'How does your project link to the objectives of the Erasmus programme and more specifically those of Youth Participation Activities?', 'char_limit' => 3000, 'guidance' => 'Show how the activities strengthen meaningful youth participation and dialogue.'],
                ['key' => 'target-groups', 'category' => 'Project rationale', 'title' => 'What are the target groups of the individual activities of your project?', 'char_limit' => 3000, 'guidance' => 'Distinguish the core young people, activity participants and wider audiences.'],
                ['key' => 'active-young-people-benefit', 'category' => 'Project rationale', 'title' => 'How will your project benefit the organisation(s)/informal group(s) and the young people actively involved in the preparation and implementation of the project, during and after the project lifetime?', 'char_limit' => 4500, 'guidance' => 'Describe distinct benefits for applicants and young people actively involved.'],
                ['key' => 'wider-target-benefit', 'category' => 'Project rationale', 'title' => 'How will your project benefit young people in the wider target group (i.e. young people participating in the activities, beyond the informal group of young people or the young people actively involved in the preparation and implementation of the project where the applicant is an organisation) during and after the project lifetime?', 'char_limit' => 4500, 'guidance' => 'Describe distinct benefits for the wider target group and how they will be evidenced.'],
                ['key' => 'wider-impact', 'category' => 'Project rationale', 'title' => 'What would be the impact of your project at local, regional, national and/or European level ?', 'char_limit' => 3000, 'guidance' => 'Describe credible effects beyond direct participants, with a clear scale and audience.'],
                ['key' => 'target-profile', 'category' => 'Description of the activities', 'title' => 'Please describe the profile of the target groups you will address by the different activities, including their age. For target groups beyond the informal group of young people and beyond the young people who are actively involved in the preparation and implementation of the project, please describe how they have been, or will be selected.', 'char_limit' => 4000, 'guidance' => 'Describe age, profiles, selection and differences between target groups.'],
                ['key' => 'target-involvement', 'category' => 'Description of the activities', 'title' => 'Please describe how the target groups will be involved in planning, preparing, and implementing the activities and in the follow-up of the different activities. Please also describe the involvement of target groups beyond the informal group of young people and the young people who are actively involved in the preparation and implementation of the project.', 'char_limit' => 4500, 'guidance' => 'Specify decisions, tasks and leadership roles held by young people in every phase.'],
                ['key' => 'non-event-participants', 'category' => 'Description of the activities', 'title' => 'Please provide an estimate of the number of young people that will not take part in physical events or mobility activities, but still will participate in other activities of your project. The number may be 0 (if every participant at some point takes part in a physical event or a mobility activity).', 'char_limit' => 1500, 'guidance' => 'Keep the number consistent with activity planning.'],
                ['key' => 'decision-makers', 'category' => 'Description of the activities', 'title' => 'Will your project also involve decision makers?', 'char_limit' => null, 'guidance' => 'If yes, explain their role in participation activities.'],
                ['key' => 'participant-cooperation', 'category' => 'Description of the activities', 'title' => 'How will the participants cooperate and communicate between them to prepare and follow-up on the project activities?', 'char_limit' => 3000, 'guidance' => 'Describe accessible channels, facilitation, responsibilities and decision-making.'],
                ['key' => 'communication-channels', 'category' => 'Description of the activities', 'title' => 'How did you choose this channel/these channels of communication? If relevant, please distinguish between the informal group of young people (applicant or partner), the young people actively involved in the preparation/implementation of the project (if applicant or partner is an organisation) and other participants.', 'char_limit' => 3000, 'guidance' => 'Justify communication choices by accessibility and participant profile.'],
                ['key' => 'activity-learning-outcomes', 'category' => 'Description of the activities', 'title' => 'Which learning outcomes or competences (i.e. knowledge, skills and attitudes/behaviours) are to be acquired/improved by the participants in the project activities? If relevant, please distinguish between the informal group of young people (applicant or partner), the young people actively involved in the preparation/implementation of the project (if applicant or partner is an organisation) and other participants.', 'char_limit' => 4000, 'guidance' => 'Define learning outcomes by participant group.'],
                ['key' => 'coach-support', 'category' => 'Description of the activities', 'title' => 'Will the young people involved in implementing the project need the support of a coach/several coaches?', 'char_limit' => null, 'guidance' => 'If yes, describe the coach role and support logic.'],
                ['key' => 'participation-events-mobilities', 'category' => 'Project details', 'title' => 'Are you planning to carry out Youth participation events and Youth participation mobilities in your project?', 'char_limit' => null, 'guidance' => 'Answer consistently with project details and activity planning.'],
                ['key' => 'participant-contributions', 'category' => 'Participant contribution and fees', 'title' => 'Are you planning to ask for any contributions from participants?', 'char_limit' => null, 'guidance' => 'If yes, justify the contribution and ensure it does not create unfair barriers to participation.'],
                ['key' => 'non-formal-learning', 'category' => 'Project design', 'title' => 'What will the participants learn during the activities? Which learning outcomes or competences (i.e. knowledge, skills and attitudes/behaviors) are to be acquired/improved by participants in the activities? What will your project include as non-formal learning methods ?', 'char_limit' => 4000, 'guidance' => 'Embed reflection into activities and explain non-formal learning methods.'],
                ['key' => 'preparation-support', 'category' => 'Project design', 'title' => 'How will you prepare the participants before the start of the activities and how will you support them during and after the activities?', 'char_limit' => 4000, 'guidance' => 'Include preparation, accessibility, safeguarding, facilitation and follow-up support.'],
                ['key' => 'safety-protection', 'category' => 'Project design', 'title' => 'What measures will you put in place to ensure the safety and protection of participants?', 'char_limit' => 3000, 'guidance' => 'Include safeguarding roles, emergency procedures, insurance, consent, reporting and activity-specific risks.'],
                ['key' => 'learning-awareness', 'category' => 'Project design', 'title' => 'How will you help participants to become aware of what they have learned and which competences they have developed or improved? Please remember to include the methods that support reflection and documentation of the learning outcomes in the description of activities.', 'char_limit' => 3500, 'guidance' => 'Describe reflection methods and learning evidence.'],
                ['key' => 'european-certificates', 'category' => 'Project design', 'title' => 'The Erasmus Programme promotes the use of instruments/certificates like Youthpass or Europass , to validate the competences acquired by the participants during their experiences abroad. Will your project make use of such European instruments/certificates?', 'char_limit' => null, 'guidance' => 'Explain how Youthpass, Europass or equivalent tools will be used if applicable.'],
                ['key' => 'national-certificates', 'category' => 'Project design', 'title' => 'Are you planning to use any national instrument/certificate? If so, please describe which one.', 'char_limit' => null, 'guidance' => 'Mention national recognition tools only where relevant.'],
                ['key' => 'fewer-opportunities', 'category' => 'Project design', 'title' => 'Are participants involved in activities facing challenges that hinder their participation?', 'char_limit' => null, 'guidance' => 'Describe barriers and support measures if applicable.'],
                ['key' => 'virtual-blended-components', 'category' => 'Project design', 'title' => 'Do you foresee Virtual/Blended activities and/or the use of any virtual component, before, during or after the activity?', 'char_limit' => null, 'guidance' => 'Explain the purpose of virtual or blended components.'],
                ['key' => 'environmental-practices', 'category' => 'Project design', 'title' => 'Will you include sustainable and environmental-friendly practices in your activities?', 'char_limit' => null, 'guidance' => 'Describe concrete sustainable choices and how they will be applied.'],
                ['key' => 'management-quality', 'category' => 'Project management', 'title' => 'How will you manage the project (agreements with partners etc.) and make sure that it is done in line with the Erasmus Youth Quality Standards? You will find the quality standards further down in the application form.', 'char_limit' => 5000, 'guidance' => 'Cover roles, agreements, communication, accessibility and risk.'],
                ['key' => 'logistics', 'category' => 'Project management', 'title' => 'How will you organise the practical and logistical part of the project (e.g. ongoing activities, communication with participants and partners, if needed mentoring and support, and if relevant travel, accommodation, insurance, visa, social security, etc.)? What measures will you put in place to ensure the safety and protection of participants?', 'char_limit' => 4500, 'guidance' => 'Define practical arrangements, communication, support and safety measures.'],
                ['key' => 'evaluation', 'category' => 'Project management', 'title' => 'How will you evaluate your project’s success? Which activities will you carry out in order to assess whether, and to what extent, your project has reached its objectives and results?', 'char_limit' => 3500, 'guidance' => 'Use participation-quality indicators as well as output and reach measures.'],
                ['key' => 'sustainability', 'category' => 'Project management', 'title' => 'What will you do to make sure that your project continues to have effects also after it ends? What activities, if any, are going to continue to take place even after the project\'s end date (once the funding has finished), and how will they be beneficial to your target groups?', 'char_limit' => 3500, 'guidance' => 'Name what continues, who owns it and what resources make continuation credible.'],
                ['key' => 'dissemination', 'category' => 'Project management', 'title' => 'How will you make your project visible outside your organisation(s)/ informal group(s) of young people and the young people actively involved in the preparation and implementation of the project? What concrete results of the project do you plan to disseminate, how and to whom?', 'char_limit' => 3500, 'guidance' => 'Define audiences, outputs, channels, owners, dates and evidence of reach.'],
                ['key' => 'participant-dissemination', 'category' => 'Project management', 'title' => 'How will you involve participants in such activities? If relevant, please distinguish between the young people actively involved in the preparation and implementation of the project and other participants.', 'char_limit' => 2500, 'guidance' => 'Plan dissemination with young people, not only for them.'],
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

    public static function templates(): array
    {
        return [
            ...self::TEMPLATES,
            ...self::generatedSectorTemplates(),
            ...self::officialKa1EducationTemplates(),
            ...self::officialPartnershipTemplates(),
        ];
    }

    public static function list(): array
    {
        return collect(self::verifiedTemplates())->mapWithKeys(fn (array $template, string $key) => [
            $key => $template['label'].' · Call '.$template['call_year'],
        ])->all();
    }

    public static function catalog(): array
    {
        return collect(self::verifiedTemplates())->map(function (array $template, string $key) {
            $sections = $template['sections'] ?? [];
            $categories = collect($sections)
                ->pluck('category')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'key' => $key,
                'label' => $template['label'],
                'action' => $template['action'],
                'call_year' => $template['call_year'],
                'form_id' => $template['form_id'],
                'source_url' => $template['source_url'],
                'description' => $template['description'],
                'officially_verified' => (bool) ($template['officially_verified'] ?? false),
                'family' => $template['family'] ?? self::templateFamily($key),
                'sector' => $template['sector'] ?? self::templateSector($key),
                'sections_count' => count($sections),
                'categories' => $categories,
            ];
        })->values()->all();
    }

    public static function sections(string $key): array
    {
        return self::templates()[self::normaliseKey($key)]['sections'] ?? [];
    }

    public static function get(string $key): ?array
    {
        return self::templates()[self::normaliseKey($key)] ?? null;
    }

    public static function verifiedTemplates(): array
    {
        return collect(self::templates())
            ->filter(fn (array $template): bool => (bool) ($template['officially_verified'] ?? false))
            ->all();
    }

    public static function isOfficiallyVerified(string $key): bool
    {
        return (bool) (self::get($key)['officially_verified'] ?? false);
    }

    public static function defaultVerifiedKey(): string
    {
        return array_key_first(self::verifiedTemplates()) ?? 'ka151-you';
    }

    public static function normaliseKey(?string $key): string
    {
        $key = strtolower((string) $key);
        $aliases = [
            'ka121' => 'ka121-sch',
            'ka122' => 'ka122',
            'ka151' => 'ka151-you',
            'ka152' => 'ka152-you',
            'ka153' => 'ka153-you',
            'ka154' => 'ka154-you',
            'ka155' => 'ka155-you',
            'ka210-you' => 'ka210-you',
            'ka220-you' => 'ka220-you',
        ];

        return $aliases[$key] ?? $key;
    }

    public static function libraryKeys(?string $key): array
    {
        $normalised = self::normaliseKey($key);
        $base = explode('-', $normalised)[0];

        return array_values(array_unique(['any', $normalised, $base]));
    }

    public static function families(): array
    {
        return [
            'youth' => 'Youth mobility',
            'mobility' => 'Education mobility',
            'partnerships' => 'Partnerships',
            'generic' => 'Generic / legacy',
        ];
    }

    protected static function generatedSectorTemplates(): array
    {
        $mobilitySectors = [
            'sch' => ['label' => 'School education', 'suffix' => 'SCH', 'audience' => 'learners, teachers and school staff', 'quality' => 'school development priorities, pupil wellbeing, inclusion and learning outcomes'],
            'vet' => ['label' => 'Vocational education and training', 'suffix' => 'VET', 'audience' => 'VET learners, apprentices, trainers and VET staff', 'quality' => 'work-based learning quality, occupational relevance, host-company cooperation and learner safety'],
            'adu' => ['label' => 'Adult education', 'suffix' => 'ADU', 'audience' => 'adult learners, educators and adult-education staff', 'quality' => 'adult learner needs, outreach, accessibility and transfer into adult-learning provision'],
        ];

        $partnershipSectors = [
            'you' => ['label' => 'Youth', 'suffix' => 'YOU', 'audience' => 'young people, youth workers and youth organisations', 'quality' => 'non-formal learning, participation, inclusion and youth-work impact'],
            'sch' => ['label' => 'School education', 'suffix' => 'SCH', 'audience' => 'schools, pupils, teachers and education stakeholders', 'quality' => 'school improvement, learner outcomes and pedagogical transfer'],
            'vet' => ['label' => 'Vocational education and training', 'suffix' => 'VET', 'audience' => 'VET providers, learners, trainers and labour-market partners', 'quality' => 'skills relevance, work-based learning and transfer to VET systems'],
            'adu' => ['label' => 'Adult education', 'suffix' => 'ADU', 'audience' => 'adult-learning providers, adult educators and adult learners', 'quality' => 'adult-learning access, inclusion, outreach and community impact'],
        ];

        $templates = [];

        foreach ($mobilitySectors as $key => $sector) {
            $templates['ka122-'.$key] = self::mobilityTemplate('KA122', 'Short-term mobility projects in '.$sector['label'], $sector, false);
            $templates['ka121-'.$key] = self::mobilityTemplate('KA121', 'Accredited mobility projects in '.$sector['label'], $sector, true);
        }

        foreach ($partnershipSectors as $key => $sector) {
            $templates['ka210-'.$key] = self::partnershipTemplate('KA210', 'Small-scale partnerships in '.$sector['label'], $sector, false);
            $templates['ka220-'.$key] = self::partnershipTemplate('KA220', 'Cooperation partnerships in '.$sector['label'], $sector, true);
        }

        return $templates;
    }

    protected static function officialKa1EducationTemplates(): array
    {
        $ka122Sections = [
            ['key' => 'summary-background', 'category' => 'Project summary', 'title' => 'i. Background: Why did you apply for this project?', 'char_limit' => 2000, 'guidance' => 'Use the official summary prompt. Keep it clear and suitable for publication.'],
            ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'ii. Objectives: What do you want to achieve by implementing the project?', 'char_limit' => 2000, 'guidance' => 'Summarise the concrete objectives in plain language.'],
            ['key' => 'summary-results', 'category' => 'Project summary', 'title' => 'iii. Results: What results do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Summarise expected participant, organisational and wider results.'],
            ['key' => 'learning-programmes', 'category' => 'Background', 'title' => 'Does your organisation provide any formal or informal learning programmes relevant for this application?', 'char_limit' => null, 'guidance' => 'Answer according to the organisation profile in the official form.'],
            ['key' => 'organisation-presentation', 'category' => 'Background', 'title' => 'Please briefly present your organisation.', 'char_limit' => 4000, 'guidance' => 'This introduces the official background subsection.'],
            ['key' => 'main-activities', 'category' => 'Background', 'title' => 'i. What are your organisation\'s main activities?', 'char_limit' => 3000, 'guidance' => 'Describe regular activities, not only project-related work.'],
            ['key' => 'field-activities', 'category' => 'Background', 'title' => 'ii. What are your organisation\'s activities in the field of this application?', 'char_limit' => 3000, 'guidance' => 'Focus on the sector of the application.'],
            ['key' => 'learner-profiles', 'category' => 'Background', 'title' => 'iii. Please describe the learners concerned by your organisation’s daily work. What are their profiles and age groups? In particular, please mention if you are regularly working with participants with fewer opportunities, and how.', 'char_limit' => 4000, 'guidance' => 'Mention profiles, ages and fewer-opportunity experience where relevant.'],
            ['key' => 'years-experience', 'category' => 'Background', 'title' => 'iv. How many years of experience does your organisation have working in the field of this application?', 'char_limit' => 1000, 'guidance' => 'State the experience clearly and consistently.'],
            ['key' => 'organisation-size', 'category' => 'Background', 'title' => 'What is the size of your organisation in terms of number of learners and staff? If your organisation is working in more than one field of education and training, please only include learners and staff in the field of this application.', 'char_limit' => 2500, 'guidance' => 'Keep numbers sector-specific if the organisation works in multiple fields.'],
            ['key' => 'needs-challenges', 'category' => 'Project objectives', 'title' => 'What are the most important needs and challenges your organisation is currently facing? How can an Erasmus+ mobility project help improve your organisation for the benefit of all of its learners? Please illustrate your answers with concrete examples.', 'char_limit' => 5000, 'guidance' => 'Use concrete evidence and connect needs to mobility.'],
            ['key' => 'objective-achieve', 'category' => 'Project objectives', 'title' => 'What do you want to achieve?', 'char_limit' => 2500, 'guidance' => 'Write one concrete objective per objective block.'],
            ['key' => 'objective-explanation', 'category' => 'Project objectives', 'title' => 'Which needs and challenges described in the previous question are addressed by this objective, and how?', 'char_limit' => 3000, 'guidance' => 'Connect the objective directly to the needs described above.'],
            ['key' => 'objective-measuring-success', 'category' => 'Project objectives', 'title' => 'How are you going to evaluate if the objective has been reached?', 'char_limit' => 2500, 'guidance' => 'Define evidence, indicators and timing.'],
            ['key' => 'topics', 'category' => 'Project objectives', 'title' => 'What topics are you going to work on in your project?', 'char_limit' => null, 'guidance' => 'Select topics consistently with the project objectives.'],
            ['key' => 'basic-principles', 'category' => 'Follow-up', 'title' => 'What will your organisation do to contribute to the basic principles defined by the quality standards : inclusion and diversity, environmental sustainability and responsibility, digital education, and active participation in the network of Erasmus+ organisations?', 'char_limit' => 4500, 'guidance' => 'Address every listed basic principle concretely.'],
            ['key' => 'project-team', 'category' => 'Follow-up', 'title' => 'Please describe your project team and the division of tasks in it. Who will participate in the project team – please mention the persons’ roles, positions and expertise, not their names. How will the key project tasks be divided among the project team: selection of participants, preparation of participants, supporting participants during the activity, defining the learning programmes, recognition of learning outcomes, overall supervision and ensuring the respect of quality standards.', 'char_limit' => 5000, 'guidance' => 'Use roles and expertise, not personal names.'],
            ['key' => 'integrate-results', 'category' => 'Follow-up', 'title' => 'What will you do to integrate the results of implemented mobility activities in your organisation’s regular work?', 'char_limit' => 3500, 'guidance' => 'Explain how learning becomes part of regular practice.'],
            ['key' => 'share-results', 'category' => 'Follow-up', 'title' => 'What will your organisation do to share the results of its activities and knowledge about the Programme?', 'char_limit' => 3500, 'guidance' => 'Introduce the sharing strategy.'],
            ['key' => 'share-internal', 'category' => 'Follow-up', 'title' => 'i. To share results within your organisation', 'char_limit' => 2500, 'guidance' => 'Describe internal sharing.'],
            ['key' => 'share-public', 'category' => 'Follow-up', 'title' => 'ii. To share results with other organisations and the public', 'char_limit' => 2500, 'guidance' => 'Describe external sharing.'],
            ['key' => 'acknowledge-eu-funding', 'category' => 'Follow-up', 'title' => 'iii. To publicly acknowledge European Union funding', 'char_limit' => 2000, 'guidance' => 'Describe visibility and acknowledgement measures.'],
        ];

        $ka121Sections = [
            ['key' => 'supporting-organisations', 'category' => 'Participating organisations', 'title' => 'My organisation plans to work with other supporting organisations that are not going to host our participants, but are going to help with the implementation of activities.', 'char_limit' => null, 'guidance' => 'Official field label from the accredited mobility form.'],
            ['key' => 'budget-request', 'category' => 'Activities', 'title' => 'How does the budget request work?', 'char_limit' => null, 'guidance' => 'Official explanatory prompt before the activity request table.'],
            ['key' => 'activity-table-instruction', 'category' => 'Activities', 'title' => 'Please choose the types of activities you would like to implement in your project and complete the following table with the number of participants, duration and other information.', 'char_limit' => null, 'guidance' => 'Use the activity table for participant numbers, duration and activity types.'],
            ['key' => 'exceptional-inclusion-support', 'category' => 'Exceptional costs and inclusion support for participants', 'title' => 'In this section you can request Exceptional costs and Inclusion support for participants. As opposed to standardised unit costs applicable for other types of costs, these budget categories are funded based on actual expenses. These non-standard costs require specific description and justification in order to be approved. Before making a request, please read the funding rules in the Programme Guide to make sure the type of expense you are requesting is eligible.', 'char_limit' => null, 'guidance' => 'Use the exceptional costs table for cost type, activity type, description, justification and estimate.'],
        ];

        $sectors = [
            'sch' => [
                'label' => 'School education',
                'suffix' => 'SCH',
                'ka122_form' => 'KA122-SCH-A83773DB',
                'ka122_year' => 2026,
                'ka122_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Short-term%20projects%20for%20mobility%20of%20learners%20and%20staff%20in%20school%20education%20%28KA122-SCH%29_watermark.pdf',
                'ka121_form' => 'KA121-SCH-F54D6441',
                'ka121_year' => 2026,
                'ka121_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Accredited%20projects%20for%20mobility%20of%20learners%20and%20staff%20in%20school%20education%20%28KA121-SCH%29_watermark.pdf',
            ],
            'vet' => [
                'label' => 'Vocational education and training',
                'suffix' => 'VET',
                'ka122_form' => 'KA122-VET-AAA411FF',
                'ka122_year' => 2026,
                'ka122_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Short-term%20projects%20for%20mobility%20of%20learners%20and%20staff%20in%20vocational%20education%20and%20training%20%28KA122-VET%29_watermark.pdf',
                'ka121_form' => 'KA121-VET-92A4EEB1',
                'ka121_year' => 2026,
                'ka121_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Accredited%20projects%20for%20mobility%20of%20learners%20and%20staff%20in%20vocational%20education%20and%20training%20%28KA121-VET%29_watermark.pdf',
            ],
            'adu' => [
                'label' => 'Adult education',
                'suffix' => 'ADU',
                'ka122_form' => 'KA122-ADU-5BC59F23',
                'ka122_year' => 2025,
                'ka122_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2024-11/2025-eplus-call-template-KA122-ADU.pdf',
                'ka121_form' => 'KA121-ADU-97A50C44',
                'ka121_year' => 2026,
                'ka121_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Accredited%20projects%20for%20mobility%20of%20learners%20and%20staff%20in%20adult%20education%20%28KA121-ADU%29_watermark.pdf',
            ],
        ];

        $templates = [];

        foreach ($sectors as $key => $sector) {
            $templates['ka122-'.$key] = [
                'label' => 'KA122-'.$sector['suffix'].' - Short-term mobility projects in '.$sector['label'],
                'action' => 'KA122-'.$sector['suffix'],
                'call_year' => $sector['ka122_year'],
                'form_id' => $sector['ka122_form'],
                'source_url' => $sector['ka122_url'],
                'family' => 'mobility',
                'sector' => $key,
                'description' => 'Official short-term mobility application form for '.$sector['label'].'.',
                'officially_verified' => true,
                'sections' => $ka122Sections,
            ];

            $templates['ka121-'.$key] = [
                'label' => 'KA121-'.$sector['suffix'].' - Accredited mobility projects in '.$sector['label'],
                'action' => 'KA121-'.$sector['suffix'],
                'call_year' => $sector['ka121_year'],
                'form_id' => $sector['ka121_form'],
                'source_url' => $sector['ka121_url'],
                'family' => 'mobility',
                'sector' => $key,
                'description' => 'Official accredited mobility budget request form for '.$sector['label'].'.',
                'officially_verified' => true,
                'sections' => $ka121Sections,
            ];
        }

        return $templates;
    }

    protected static function officialPartnershipTemplates(): array
    {
        $ka210Sections = [
            ['key' => 'objectives-results-priorities', 'category' => 'Project description', 'title' => "What are the concrete objectives you would like to achieve and 'outcomes or results you would like to realise'? How are these objectives linked to the priorities you have selected?", 'char_limit' => 4000, 'guidance' => 'Build a clear chain from priorities to objectives and results.'],
            ['key' => 'target-groups-needs', 'category' => 'Project description', 'title' => 'Please outline the target groups of your project and describe their identified needs', 'char_limit' => 3500, 'guidance' => 'Segment target groups and describe evidence of need.'],
            ['key' => 'motivation-funded', 'category' => 'Project description', 'title' => 'Please describe the motivation for your project and explain why it should be funded', 'char_limit' => 3500, 'guidance' => 'Explain why this project is necessary and proportionate.'],
            ['key' => 'needs-goals', 'category' => 'Project description', 'title' => 'How does the project address the needs and goals of the participating organisations and the target groups?', 'char_limit' => 4000, 'guidance' => 'Connect organisations, target groups, activities and expected changes.'],
            ['key' => 'transnational-benefits', 'category' => 'Project description', 'title' => 'What will be the benefits of cooperating with transnational partners to achieve the project objectives?', 'char_limit' => 3500, 'guidance' => 'Explain the European added value of cooperation.'],
            ['key' => 'horizontal-priorities', 'category' => 'Project description', 'title' => 'How does the project address the horizontal priorities?', 'char_limit' => 3000, 'guidance' => 'Address only priorities that are genuinely embedded in the project.'],
            ['key' => 'partnership-formation', 'category' => 'Cooperation arrangements', 'title' => 'How was the partnership formed? What are the strengths that each partner will bring to the project?', 'char_limit' => 3500, 'guidance' => 'Show complementarity and relevance.'],
            ['key' => 'management-cooperation', 'category' => 'Cooperation arrangements', 'title' => 'How will you ensure sound management of the project and good cooperation and communication between partners during project implementation?', 'char_limit' => 4000, 'guidance' => 'Define governance, communication and monitoring.'],
            ['key' => 'erasmus-platforms', 'category' => 'Cooperation arrangements', 'title' => 'Please describe how you will use Erasmus+ platforms for preparation, implementation or follow-up of your project?', 'char_limit' => 2500, 'guidance' => 'Mention platforms only where they have a concrete role.'],
            ['key' => 'partner-tasks', 'category' => 'Cooperation arrangements', 'title' => 'Please describe the tasks and responsibilities of each partner organisation in the project.', 'char_limit' => 4000, 'guidance' => 'Match tasks to capacity and contribution.'],
            ['key' => 'achieved-objectives', 'category' => 'Impact and follow-up', 'title' => 'How will you know if the project has achieved its objectives? Please explain how you will measure it.', 'char_limit' => 3500, 'guidance' => 'Define indicators, evidence and review moments.'],
            ['key' => 'long-term-development', 'category' => 'Impact and follow-up', 'title' => "How will the participation in this project contribute to the development of the involved organisations in the long-term? Do you have plans to continue using the results of the project or continue to implement some of the activities after the project's end?", 'char_limit' => 4000, 'guidance' => 'Explain sustainability and organisational development.'],
            ['key' => 'sharing-use', 'category' => 'Impact and follow-up', 'title' => 'Please describe your plans for sharing and use of project results.', 'char_limit' => 3000, 'guidance' => 'Introduce your dissemination and use strategy.'],
            ['key' => 'sharing-results', 'category' => 'Impact and follow-up', 'title' => 'How will you make the results of your project known within your partnership, in your local communities and in the wider public? Who are the main target groups you would like to share your results with?', 'char_limit' => 3500, 'guidance' => 'Define audiences, channels and messages.'],
            ['key' => 'other-beneficiaries', 'category' => 'Impact and follow-up', 'title' => 'Are there other groups or organisations that will benefit from your project? Please explain how.', 'char_limit' => 2500, 'guidance' => 'Mention realistic wider beneficiaries.'],
            ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'Objectives: What do you want to achieve by implementing the project?', 'char_limit' => 2000, 'guidance' => 'Public summary objective statement.'],
            ['key' => 'summary-implementation', 'category' => 'Project summary', 'title' => 'Implementation: What activities are you going to implement?', 'char_limit' => 2000, 'guidance' => 'Public summary of activities.'],
            ['key' => 'summary-results', 'category' => 'Project summary', 'title' => 'Results: What results do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Public summary of expected results.'],
        ];

        $ka220CommonSections = [
            ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'Objectives: What do you want to achieve by implementing the project?', 'char_limit' => 2000, 'guidance' => 'Public summary objective statement.'],
            ['key' => 'summary-implementation', 'category' => 'Project summary', 'title' => 'Implementation: What activities are you going to implement?', 'char_limit' => 2000, 'guidance' => 'Public summary of activities.'],
            ['key' => 'summary-results', 'category' => 'Project summary', 'title' => 'Results: What project results and other outcomes do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Public summary of expected project results and outcomes.'],
            ['key' => 'selected-priorities', 'category' => 'Relevance of the project', 'title' => 'How does the project address the selected priorities ?', 'char_limit' => 3500, 'guidance' => 'Link priorities to concrete project choices.'],
            ['key' => 'motivation-funded', 'category' => 'Relevance of the project', 'title' => 'Please describe the motivation for your project and explain why it should be funded.', 'char_limit' => 3500, 'guidance' => 'Explain the problem, opportunity and funding rationale.'],
            ['key' => 'objectives-results-priorities', 'category' => 'Relevance of the project', 'title' => 'What are the objectives you would like to achieve and which concrete results you would like to produce? How are these objectives linked to the priorities you have selected ?', 'char_limit' => 4500, 'guidance' => 'Connect objectives, results and priorities.'],
            ['key' => 'innovation', 'category' => 'Relevance of the project', 'title' => 'What makes your proposal innovative?', 'char_limit' => 3000, 'guidance' => 'Explain what is new or improved compared with current practice.'],
            ['key' => 'synergies-impact-fields', 'category' => 'Relevance of the project', 'title' => 'How is your proposal suitable for creating synergies between different fields of education, training, youth and sport or how does it have a strong potential impact on one or more of those fields?', 'char_limit' => 3500, 'guidance' => 'Explain cross-field synergies or strong field-specific impact.'],
            ['key' => 'eu-added-value', 'category' => 'Relevance of the project', 'title' => 'How does the proposal bring added value at European level through results that would not be attained by activities carried out in a single country?', 'char_limit' => 3500, 'guidance' => 'Explain why transnational cooperation is necessary.'],
            ['key' => 'erasmus-follow-up', 'category' => 'Relevance of the project', 'title' => 'Does this project proposal represent a follow-up or evolution of a previous project or projects funded by Erasmus+ ?', 'char_limit' => null, 'guidance' => 'If yes, explain the link and evolution.'],
            ['key' => 'other-funding-follow-up', 'category' => 'Relevance of the project', 'title' => 'Is the project proposal the follow-up or the evolution of a previous project or projects funded under other funding instruments/programmes at EU, national, regional, international level ?', 'char_limit' => null, 'guidance' => 'If yes, explain continuity and added value.'],
            ['key' => 'synergy-initiatives', 'category' => 'Relevance of the project', 'title' => 'Is the project proposal in synergy with other initiatives or funding instruments ?', 'char_limit' => null, 'guidance' => 'If yes, explain complementarity.'],
            ['key' => 'needs', 'category' => 'Relevance of the project', 'title' => 'What needs do you want to address by implementing your project?', 'char_limit' => 4000, 'guidance' => 'Use evidence, not generic claims.'],
            ['key' => 'target-groups', 'category' => 'Relevance of the project', 'title' => 'What are the target groups of the project? How do the participating organisations engage with the project target groups in their activities?', 'char_limit' => 4000, 'guidance' => 'Define target groups and partner access to them.'],
            ['key' => 'needs-identification', 'category' => 'Relevance of the project', 'title' => 'How did you identify the needs of your partnership and those of your target groups?', 'char_limit' => 3500, 'guidance' => 'Explain evidence and consultation.'],
            ['key' => 'needs-addressing', 'category' => 'Relevance of the project', 'title' => 'How will this project address these needs?', 'char_limit' => 4000, 'guidance' => 'Connect needs to activities and results.'],
            ['key' => 'partnership-formation', 'category' => 'Partnership and cooperation arrangements', 'title' => 'How did you form your partnership? How does the mix of participating organisations complement each other and what will be the added value of their collaboration in the framework of the project? If applicable, please list and describe the associated partners involved in the project and their added-value. If applicable, please list and describe the associated partners involved in the project and their added-value to the project.', 'char_limit' => 4500, 'guidance' => 'Show complementarity, added value and associated partners where relevant.'],
            ['key' => 'task-allocation', 'category' => 'Partnership and cooperation arrangements', 'title' => 'What is the task allocation and how does it reflect the commitment and active contribution of all participating organisations (including the associated partners, if applicable) ?', 'char_limit' => 4000, 'guidance' => 'Match tasks with capacity and commitment.'],
            ['key' => 'coordination-communication', 'category' => 'Partnership and cooperation arrangements', 'title' => 'Describe the mechanism for coordination and communication between the participating organisations (including the associated partners, if applicable).', 'char_limit' => 3500, 'guidance' => 'Describe governance, channels, rhythm and decisions.'],
            ['key' => 'impact-assessment', 'category' => 'Impact and dissemination', 'title' => 'How are you going to assess if the project objectives have been achieved?', 'char_limit' => 3500, 'guidance' => 'Define indicators, evidence and evaluation moments.'],
            ['key' => 'sustainability', 'category' => 'Impact and dissemination', 'title' => "Explain how you will ensure the sustainability of the project: How will the participation in this project contribute to the development of the involved organisations in the long-term? How do you plan to continue using the project results or implement some of the activities after the project's end?", 'char_limit' => 4500, 'guidance' => 'Explain long-term use, ownership and continuation.'],
            ['key' => 'dissemination', 'category' => 'Impact and dissemination', 'title' => 'How do you plan to disseminate the result of the project?', 'char_limit' => 3500, 'guidance' => 'Define audiences, channels and responsibilities.'],
            ['key' => 'impact-level', 'category' => 'Impact and dissemination', 'title' => 'At which level will the results of your project generate impact?', 'char_limit' => null, 'guidance' => 'Select relevant impact levels.'],
            ['key' => 'impact-explanation', 'category' => 'Impact and dissemination', 'title' => 'Please explain in what way the expected results will generate impact for the chosen level(s).', 'char_limit' => 3500, 'guidance' => 'Explain the mechanism of impact by level.'],
            ['key' => 'wp1-monitoring', 'category' => 'Project design and implementation', 'title' => 'How will the progress, quality and achievement of project activities be monitored? Please give information about the involved staff, as well as the timing and frequency of the monitoring activities.', 'char_limit' => 4000, 'guidance' => 'Describe monitoring roles, cadence and evidence.'],
            ['key' => 'wp1-budget-time', 'category' => 'Project design and implementation', 'title' => 'How will you ensure proper budget control and time management in your project?', 'char_limit' => 3000, 'guidance' => 'Describe budget and time controls.'],
            ['key' => 'wp1-risks', 'category' => 'Project design and implementation', 'title' => 'What are your plans for handling risks for project implementation (e.g. delays, budget, conflicts, etc.)?', 'char_limit' => 3000, 'guidance' => 'Describe risk identification, owners and mitigation.'],
            ['key' => 'wp1-accessible-inclusive', 'category' => 'Project design and implementation', 'title' => 'How will you ensure that the activities are designed in an accessible and inclusive way?', 'char_limit' => 3000, 'guidance' => 'Describe accessibility and inclusion by design.'],
            ['key' => 'wp1-digital', 'category' => 'Project design and implementation', 'title' => 'How does the project incorporate the use of digital tools and learning methods to complement the physical activities and to improve cooperation between partner organisations?', 'char_limit' => 3000, 'guidance' => 'Explain digital tools and their purpose.'],
            ['key' => 'wp1-green', 'category' => 'Project design and implementation', 'title' => 'How does the project incorporate green practices in different project phases?', 'char_limit' => 3000, 'guidance' => 'Describe concrete green practices.'],
            ['key' => 'wp1-participation', 'category' => 'Project design and implementation', 'title' => 'How does the project encourage participation and civic engagement in different project phases?', 'char_limit' => 3000, 'guidance' => 'Explain participation and civic engagement mechanisms.'],
            ['key' => 'wp2-objectives', 'category' => 'Work package', 'title' => 'What are the specific objectives of this work package and how do they contribute to the general objectives of the project?', 'char_limit' => 3500, 'guidance' => 'Connect work package objectives to project objectives.'],
            ['key' => 'wp2-results', 'category' => 'Work package', 'title' => 'What will be the main results of this work package?', 'char_limit' => 3000, 'guidance' => 'Define concrete work package results.'],
            ['key' => 'wp2-qualitative-indicators', 'category' => 'Work package', 'title' => 'What qualitative indicators will you use to measure the level of the achievement of the work package objectives and the quality of the results?', 'char_limit' => 3000, 'guidance' => 'Define qualitative indicators.'],
            ['key' => 'wp2-quantitative-indicators', 'category' => 'Work package', 'title' => 'What quantitative indicators will you use to measure the level of the achievement of the work package objectives and the quality of the results?', 'char_limit' => 3000, 'guidance' => 'Define quantitative indicators.'],
            ['key' => 'wp2-partner-tasks', 'category' => 'Work package', 'title' => 'Please describe the tasks and responsibilities of each partner organisation in the work package.', 'char_limit' => 4000, 'guidance' => 'Assign tasks to partner organisations.'],
            ['key' => 'wp2-cost-effectiveness', 'category' => 'Work package', 'title' => 'How did you determine the amount allocated to this work package? How did you verify that it is cost-effective?', 'char_limit' => 3000, 'guidance' => 'Explain budget logic and cost-effectiveness.'],
        ];

        $ka220YouthSections = [
            ['key' => 'summary-objectives', 'category' => 'Project summary', 'title' => 'Objectives: What do you want to achieve by implementing the project?', 'char_limit' => 2000, 'guidance' => 'Public summary objective statement.'],
            ['key' => 'summary-implementation', 'category' => 'Project summary', 'title' => 'Implementation: What activities are you going to implement?', 'char_limit' => 2000, 'guidance' => 'Public summary of activities.'],
            ['key' => 'summary-results', 'category' => 'Project summary', 'title' => 'Results: What project results and other outcomes do you expect your project to have?', 'char_limit' => 2000, 'guidance' => 'Public summary of expected project results and outcomes.'],
            ['key' => 'selected-priorities', 'category' => 'Relevance of the project', 'title' => 'How does the project address the selected priorities ?', 'char_limit' => 3500, 'guidance' => 'Link priorities to concrete project choices.'],
            ['key' => 'motivation-funded', 'category' => 'Relevance of the project', 'title' => 'Please describe the motivation for your project and explain why it should be funded.', 'char_limit' => 3500, 'guidance' => 'Explain the problem, opportunity and funding rationale.'],
            ['key' => 'objectives-results-priorities', 'category' => 'Relevance of the project', 'title' => 'What are the objectives you would like to achieve and concrete results you would like to produce? How are these objectives linked to the priorities you have selected ?', 'char_limit' => 4500, 'guidance' => 'Connect objectives, results and priorities.'],
            ['key' => 'innovation', 'category' => 'Relevance of the project', 'title' => 'What makes your proposal innovative?', 'char_limit' => 3000, 'guidance' => 'Explain what is new or improved compared with current practice.'],
            ['key' => 'complementary', 'category' => 'Relevance of the project', 'title' => 'How is this project complementary to other initiatives already carried out by the participating organisations?', 'char_limit' => 3000, 'guidance' => 'Explain complementarity with previous or current initiatives.'],
            ['key' => 'synergies-impact-fields', 'category' => 'Relevance of the project', 'title' => 'How is your proposal suitable for creating synergies between different fields of education, training, youth and sport or how does it have a strong potential impact on one or more of those fields?', 'char_limit' => 3500, 'guidance' => 'Explain cross-field synergies or strong field-specific impact.'],
            ['key' => 'eu-added-value', 'category' => 'Relevance of the project', 'title' => 'How does the proposal bring added value at European level through results that would not be attained by activities carried out in a single country?', 'char_limit' => 3500, 'guidance' => 'Explain why transnational cooperation is necessary.'],
            ['key' => 'needs', 'category' => 'Relevance of the project', 'title' => 'What needs do you want to address by implementing your project?', 'char_limit' => 4000, 'guidance' => 'Use evidence, not generic claims.'],
            ['key' => 'target-groups', 'category' => 'Relevance of the project', 'title' => 'What are the target groups of the project? How do the participating organisations engage with the project target groups in their activities?', 'char_limit' => 4000, 'guidance' => 'Define target groups and partner access to them.'],
            ['key' => 'needs-identification', 'category' => 'Relevance of the project', 'title' => 'How did you identify the needs of your partnership and those of your target groups?', 'char_limit' => 3500, 'guidance' => 'Explain evidence and consultation.'],
            ['key' => 'needs-addressing', 'category' => 'Relevance of the project', 'title' => 'How will this project address these needs?', 'char_limit' => 4000, 'guidance' => 'Connect needs to activities and results.'],
            ['key' => 'partnership-formation', 'category' => 'Partnership and cooperation arrangements', 'title' => 'How did you form your partnership? How does the mix of participating organisations complement each other and what will be the added value of their collaboration in the framework of the project? If applicable, please list and describe the associated partners involved in the project.', 'char_limit' => 4500, 'guidance' => 'Show complementarity, added value and associated partners where relevant.'],
            ['key' => 'task-allocation', 'category' => 'Partnership and cooperation arrangements', 'title' => 'What is the task allocation and how does it reflect the commitment and active contribution of all participating organisations (including the associated partners, if applicable) ?', 'char_limit' => 4000, 'guidance' => 'Match tasks with capacity and commitment.'],
            ['key' => 'coordination-communication', 'category' => 'Partnership and cooperation arrangements', 'title' => 'Describe the mechanism for coordination and communication between the participating organisations (including the associated partners, if applicable)', 'char_limit' => 3500, 'guidance' => 'Describe governance, channels, rhythm and decisions.'],
            ['key' => 'wp1-monitoring', 'category' => 'Project design and implementation', 'title' => 'How will the progress, quality and achievement of project activities be monitored? Please give information about the involved staff, as well as the timing and frequency of the monitoring activities.', 'char_limit' => 4000, 'guidance' => 'Describe monitoring roles, cadence and evidence.'],
            ['key' => 'wp1-budget-time', 'category' => 'Project design and implementation', 'title' => 'How will you ensure proper budget control and time management in your project?', 'char_limit' => 3000, 'guidance' => 'Describe budget and time controls.'],
            ['key' => 'wp1-risks', 'category' => 'Project design and implementation', 'title' => 'What are your plans for handling risks for project implementation (e.g. delays, budget, conflicts, etc.)?', 'char_limit' => 3000, 'guidance' => 'Describe risk identification, owners and mitigation.'],
            ['key' => 'wp1-accessible-inclusive', 'category' => 'Project design and implementation', 'title' => 'How will you ensure that the activities are designed in an accessible and inclusive way?', 'char_limit' => 3000, 'guidance' => 'Describe accessibility and inclusion by design.'],
            ['key' => 'wp1-digital', 'category' => 'Project design and implementation', 'title' => 'How does the project incorporate the use of digital tools and learning methods to complement the physical activities and to improve cooperation between partner organisations?', 'char_limit' => 3000, 'guidance' => 'Explain digital tools and their purpose.'],
            ['key' => 'wp1-green', 'category' => 'Project design and implementation', 'title' => 'How does the project incorporate green practices in different project phases?', 'char_limit' => 3000, 'guidance' => 'Describe concrete green practices.'],
            ['key' => 'wp1-participation', 'category' => 'Project design and implementation', 'title' => 'How does the project encourage participation and civic engagement in different project phases?', 'char_limit' => 3000, 'guidance' => 'Explain participation and civic engagement mechanisms.'],
            ['key' => 'wp2-objectives', 'category' => 'Work package', 'title' => 'What are the specific objectives of this work package and how do they contribute to the general objectives of the project?', 'char_limit' => 3500, 'guidance' => 'Connect work package objectives to project objectives.'],
            ['key' => 'wp2-results', 'category' => 'Work package', 'title' => 'What will be the main results of this work package?', 'char_limit' => 3000, 'guidance' => 'Define concrete work package results.'],
            ['key' => 'wp2-indicators', 'category' => 'Work package', 'title' => 'What qualitative and quantitative indicators will you use to measure the level of the achievement of the work package objectives and the quality of the results?', 'char_limit' => 3500, 'guidance' => 'Define qualitative and quantitative indicators.'],
            ['key' => 'wp2-partner-tasks', 'category' => 'Work package', 'title' => 'Please describe the tasks and responsibilities of each partner organisation in the work package.', 'char_limit' => 4000, 'guidance' => 'Assign tasks to partner organisations.'],
            ['key' => 'wp2-cost-effectiveness', 'category' => 'Work package', 'title' => 'Please explain how you define the amount dedicated to the work package and how the work package is cost-effective ?', 'char_limit' => 3000, 'guidance' => 'Explain budget logic and cost-effectiveness.'],
        ];

        $sectors = [
            'you' => [
                'label' => 'Youth',
                'suffix' => 'YOU',
                'ka210_form' => 'KA210-YOU-195B8AC7',
                'ka220_form' => 'KA220-YOU-96F5B805',
                'ka220_year' => 2025,
                'ka220_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2024-11/2025-eplus-call-template-KA220-YOU.pdf',
            ],
            'sch' => [
                'label' => 'School education',
                'suffix' => 'SCH',
                'ka210_form' => 'KA210-SCH-3D084792',
                'ka220_form' => 'KA220-SCH-1F6A066C',
                'ka220_year' => 2026,
                'ka220_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Cooperation%20partnerships%20in%20school%20education%20%28KA220-SCH%29_watermark.pdf',
            ],
            'vet' => [
                'label' => 'Vocational education and training',
                'suffix' => 'VET',
                'ka210_form' => 'KA210-VET-D89A170E',
                'ka220_form' => 'KA220-VET-FFC6948F',
                'ka220_year' => 2026,
                'ka220_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2026-01/Call%202026%20Cooperation%20partnerships%20in%20vocational%20education%20and%20training%20%28KA220-VET%29_watermark.pdf',
            ],
            'adu' => [
                'label' => 'Adult education',
                'suffix' => 'ADU',
                'ka210_form' => 'KA210-ADU-5F04AA64',
                'ka220_form' => 'KA220-ADU-CA0DA907',
                'ka220_year' => 2026,
                'ka220_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Cooperation%20partnerships%20in%20adult%20education%20%28KA220-ADU%29_watermark.pdf',
            ],
        ];

        $templates = [];

        foreach ($sectors as $key => $sector) {
            $templates['ka210-'.$key] = [
                'label' => 'KA210-'.$sector['suffix'].' - Small-scale partnerships in '.$sector['label'],
                'action' => 'KA210-'.$sector['suffix'],
                'call_year' => 2026,
                'form_id' => $sector['ka210_form'],
                'source_url' => 'https://erasmus-plus.ec.europa.eu/sites/default/files/2025-11/Call%202026%20Small-scale%20partnerships%20in%20'.str_replace(' ', '%20', strtolower($sector['label'])).'%20%28KA210-'.$sector['suffix'].'%29_watermark.pdf',
                'family' => 'partnerships',
                'sector' => $key,
                'description' => 'Official small-scale partnership form for '.$sector['label'].'.',
                'officially_verified' => true,
                'sections' => $ka210Sections,
            ];

            $templates['ka220-'.$key] = [
                'label' => 'KA220-'.$sector['suffix'].' - Cooperation partnerships in '.$sector['label'],
                'action' => 'KA220-'.$sector['suffix'],
                'call_year' => $sector['ka220_year'],
                'form_id' => $sector['ka220_form'],
                'source_url' => $sector['ka220_url'],
                'family' => 'partnerships',
                'sector' => $key,
                'description' => 'Official cooperation partnership form for '.$sector['label'].'.',
                'officially_verified' => true,
                'sections' => $key === 'you' ? $ka220YouthSections : $ka220CommonSections,
            ];
        }

        return $templates;
    }

    protected static function mobilityTemplate(string $action, string $label, array $sector, bool $accredited): array
    {
        $actionKey = strtolower($action).'-'.strtolower($sector['suffix']);

        return [
            'label' => $action.'-'.$sector['suffix'].' - '.$label,
            'action' => $action.'-'.$sector['suffix'],
            'call_year' => 2026,
            'form_id' => $action.'-'.$sector['suffix'],
            'source_url' => 'https://erasmus-plus.ec.europa.eu/resources-and-tools/documents-and-guidelines',
            'family' => 'mobility',
            'sector' => strtolower($sector['suffix']),
            'description' => ($accredited ? 'Accredited mobility structure' : 'Short-term mobility structure').' for '.$sector['label'].'.',
            'sections' => $accredited
                ? [
                    ['key' => 'accreditation-objectives', 'category' => 'Accreditation and objectives', 'title' => 'How will the planned mobilities contribute to your Erasmus accreditation objectives?', 'char_limit' => 4000, 'guidance' => 'Connect activities to the approved Erasmus Plan and '.$sector['quality'].'.'],
                    ['key' => 'activity-plan', 'category' => 'Activity plan', 'title' => 'Describe the planned activity types, destinations, duration and expected participant profiles.', 'char_limit' => 4500, 'guidance' => 'Keep activity scale, participant profiles and budget request consistent. Focus on '.$sector['audience'].'.'],
                    ['key' => 'selection-inclusion', 'category' => 'Participants', 'title' => 'How will participants be selected and how will participants with fewer opportunities be supported?', 'char_limit' => 3500, 'guidance' => 'Describe transparent selection, outreach, inclusion support and accessibility.'],
                    ['key' => 'preparation-support', 'category' => 'Quality standards', 'title' => 'How will participants be prepared, supported and monitored before, during and after mobility?', 'char_limit' => 4000, 'guidance' => 'Cover practical, linguistic, intercultural, professional and safeguarding support.'],
                    ['key' => 'learning-recognition', 'category' => 'Quality standards', 'title' => 'How will learning outcomes be agreed, documented, recognised and transferred?', 'char_limit' => 3500, 'guidance' => 'Use learning agreements, reflection, Europass/Youthpass where relevant and organisational transfer measures.'],
                    ['key' => 'management-quality', 'category' => 'Project management', 'title' => 'How will you manage partners, logistics, risk, budget and Erasmus quality standards?', 'char_limit' => 4500, 'guidance' => 'Define roles, monitoring, risk procedures, host arrangements and financial controls.'],
                    ['key' => 'impact-dissemination', 'category' => 'Impact and follow-up', 'title' => 'How will results be evaluated, integrated into regular work and shared?', 'char_limit' => 4000, 'guidance' => 'Define indicators, evidence, internal transfer and dissemination audiences.'],
                ]
                : [
                    ['key' => 'needs-objectives', 'category' => 'Project objectives', 'title' => 'What organisational needs will the project address and what concrete objectives will it achieve?', 'char_limit' => 4000, 'guidance' => 'Use evidence from '.$sector['label'].' and link objectives to '.$sector['quality'].'.'],
                    ['key' => 'activity-plan', 'category' => 'Activities', 'title' => 'Describe the planned mobility activities and explain how they contribute to the objectives.', 'char_limit' => 5000, 'guidance' => 'Include activity type, participant profile, destination, duration and expected learning value for '.$sector['audience'].'.'],
                    ['key' => 'participants-selection', 'category' => 'Participants', 'title' => 'Describe participant profiles, selection criteria and involvement in preparation/follow-up.', 'char_limit' => 4000, 'guidance' => 'Explain fair selection, motivation, responsibilities and support needs.'],
                    ['key' => 'inclusion-accessibility', 'category' => 'Participants', 'title' => 'How will participants with fewer opportunities or additional support needs be identified and supported?', 'char_limit' => 3000, 'guidance' => 'Describe barriers, reasonable support, dignity and accessibility measures.'],
                    ['key' => 'preparation-support', 'category' => 'Quality', 'title' => 'How will participants be prepared and supported before, during and after mobility?', 'char_limit' => 4000, 'guidance' => 'Cover task-related, linguistic, intercultural, practical, safeguarding and mentoring support.'],
                    ['key' => 'learning-recognition', 'category' => 'Quality', 'title' => 'How will learning outcomes be defined, monitored, documented and recognised?', 'char_limit' => 3500, 'guidance' => 'Describe learning agreements, reflection, evidence and recognition instruments.'],
                    ['key' => 'management-logistics', 'category' => 'Quality', 'title' => 'How will responsibilities, practical arrangements, partners and quality standards be managed?', 'char_limit' => 4500, 'guidance' => 'Define roles, partner agreements, logistics, risk, insurance, accessibility and financial control.'],
                    ['key' => 'impact-dissemination', 'category' => 'Impact and follow-up', 'title' => 'What impact is expected and how will results be integrated, sustained and shared?', 'char_limit' => 4500, 'guidance' => 'Separate participant, organisational and wider target-group impact.'],
                ],
        ];
    }

    protected static function partnershipTemplate(string $action, string $label, array $sector, bool $cooperation): array
    {
        return [
            'label' => $action.'-'.$sector['suffix'].' - '.$label,
            'action' => $action.'-'.$sector['suffix'],
            'call_year' => 2026,
            'form_id' => $action.'-'.$sector['suffix'],
            'source_url' => 'https://erasmus-plus.ec.europa.eu/programme-guide/part-b/key-action-2',
            'family' => 'partnerships',
            'sector' => strtolower($sector['suffix']),
            'description' => ($cooperation ? 'Full cooperation partnership' : 'Small-scale partnership').' structure for '.$sector['label'].'.',
            'sections' => $cooperation
                ? [
                    ['key' => 'needs-objectives', 'category' => 'Relevance', 'title' => 'What needs, objectives, results and selected priorities define the project intervention logic?', 'char_limit' => 5000, 'guidance' => 'Ground the project in '.$sector['quality'].' and connect needs to measurable results.'],
                    ['key' => 'target-groups', 'category' => 'Relevance', 'title' => 'Who are the target groups and how does evidence show that they need this project?', 'char_limit' => 3500, 'guidance' => 'Segment '.$sector['audience'].' and avoid generic target-group claims.'],
                    ['key' => 'innovation-eu-value', 'category' => 'Relevance', 'title' => 'What is innovative and why is transnational cooperation necessary?', 'char_limit' => 4000, 'guidance' => 'Explain European added value and how the proposal complements existing initiatives.'],
                    ['key' => 'partnership', 'category' => 'Partnership', 'title' => 'How was the partnership formed and how do partners provide complementary expertise and reach?', 'char_limit' => 4500, 'guidance' => 'Connect each partner to responsibilities, target groups and sector-specific competence.'],
                    ['key' => 'management-quality', 'category' => 'Implementation', 'title' => 'How will you manage quality, risk, communication, time and budget across the partnership?', 'char_limit' => 5000, 'guidance' => 'Describe governance, decisions, monitoring cadence, risk ownership and financial control.'],
                    ['key' => 'work-packages', 'category' => 'Implementation', 'title' => 'Describe work packages, activities, outputs, milestones, responsibilities and budget allocation.', 'char_limit' => 7000, 'guidance' => 'Make dependencies, acceptance criteria and ownership visible.'],
                    ['key' => 'horizontal-priorities', 'category' => 'Implementation', 'title' => 'How are inclusion, digital practice, sustainability and participation embedded in delivery?', 'char_limit' => 3500, 'guidance' => 'Describe practical design choices, not slogans.'],
                    ['key' => 'evaluation-impact', 'category' => 'Impact', 'title' => 'How will results and impact on participants, organisations and target groups be evaluated?', 'char_limit' => 4500, 'guidance' => 'Combine output, outcome and longer-term impact indicators relevant to '.$sector['label'].'.'],
                    ['key' => 'sustainability', 'category' => 'Impact', 'title' => 'How will results be used and sustained after the project ends?', 'char_limit' => 3500, 'guidance' => 'Name owners, resources, integration points and access conditions for each major result.'],
                    ['key' => 'dissemination', 'category' => 'Impact', 'title' => 'Describe dissemination, exploitation, open access and target-audience engagement.', 'char_limit' => 4500, 'guidance' => 'Plan differentiated audiences, messages, channels and uptake evidence.'],
                ]
                : [
                    ['key' => 'objectives-priorities', 'category' => 'Relevance', 'title' => 'What concrete objectives and results will the project achieve, and how are they linked to the selected priorities?', 'char_limit' => 4000, 'guidance' => 'Build a clear chain from '.$sector['quality'].' to activities and results.'],
                    ['key' => 'target-groups', 'category' => 'Relevance', 'title' => 'Who are the target groups and what evidence supports their needs?', 'char_limit' => 3000, 'guidance' => 'Describe '.$sector['audience'].' and why the project scale is appropriate.'],
                    ['key' => 'motivation-eu-value', 'category' => 'Relevance', 'title' => 'Why is the project needed and what European added value will it create?', 'char_limit' => 3500, 'guidance' => 'Explain why cooperation adds value beyond local action.'],
                    ['key' => 'partnership', 'category' => 'Partnership', 'title' => 'How do partners complement each other and how will tasks and decisions be shared?', 'char_limit' => 4000, 'guidance' => 'Match responsibilities to demonstrated expertise and capacity.'],
                    ['key' => 'activities-methodology', 'category' => 'Implementation', 'title' => 'Describe the activities, methodology, responsibilities, timetable and budget logic.', 'char_limit' => 6000, 'guidance' => 'Make every activity necessary for an objective and attach owner, timing and result.'],
                    ['key' => 'evaluation', 'category' => 'Impact', 'title' => 'How will you assess whether objectives and expected results have been achieved?', 'char_limit' => 3500, 'guidance' => 'Define indicators, evidence and review moments.'],
                    ['key' => 'sustainability', 'category' => 'Impact', 'title' => 'What impact is expected and how will results remain useful after funding ends?', 'char_limit' => 4000, 'guidance' => 'State who will use each result, in what setting and with what resources.'],
                    ['key' => 'dissemination', 'category' => 'Impact', 'title' => 'How will results be shared within and outside the partnership?', 'char_limit' => 3500, 'guidance' => 'Define audiences, channels, timing, owners and reach indicators.'],
                ],
        ];
    }

    protected static function templateFamily(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'ka15') => 'youth',
            str_starts_with($key, 'ka12') => 'mobility',
            str_starts_with($key, 'ka21'), str_starts_with($key, 'ka22') => 'partnerships',
            default => 'generic',
        };
    }

    protected static function templateSector(string $key): ?string
    {
        $parts = explode('-', $key);

        return count($parts) > 1 ? end($parts) : null;
    }
}
