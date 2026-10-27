// Shared matching logic uses only the public guide. No network or storage calls.
const stopWords = new Set('a an the i me my we our you your how do does did can could should would what where when why is are am be been to of in on for from with and or it its this that have has as at about please want need not nu cum ce de din la pe cu si sa imi mi ma eu un o al ale este sunt pot vreau vrea care cand unde meu mea proiect project platform mobilitycloud'.split(' '));
export const normalise = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
const tokens = value => [...new Set(normalise(value).split(' ').filter(word => word.length > 1 && !stopWords.has(word)))];
export function matchAnswers(query, answers) {
    const words = tokens(query);
    if (!words.length) return [];
    return answers.map(answer => {
        const title = normalise(answer.question);
        const indexed = new Set(tokens(`${answer.question} ${answer.keywords}`));
        const matched = words.filter(word => indexed.has(word));
        const coverage = matched.length / words.length;
        const exact = title === normalise(query);
        return { answer, score: coverage * 10 + matched.length + (exact ? 20 : 0), coverage };
    }).filter(result => result.coverage >= .5)
        .sort((a, b) => b.score - a.score)
        .slice(0, 3);
}

if (typeof document !== 'undefined') {
    const knowledge = document.getElementById('mobi-knowledge');
    const mobi = document.querySelector('[data-mobi]');
    const node = (tag, text) => { const element = document.createElement(tag); element.textContent = text; return element; };
    const openSource = () => {
        const target = document.getElementById(location.hash.slice(1));
        if (target?.tagName === 'DETAILS') target.open = true;
    };
    window.addEventListener('hashchange', openSource);
    openSource();

    if (knowledge && mobi) {
        const answers = JSON.parse(knowledge.textContent);
        const byId = new Map(answers.map(answer => [answer.id, answer]));
        const input = document.getElementById('mobi-question');
        const response = document.getElementById('mobi-response');
        const clear = document.getElementById('mobi-clear');
        function answerButton(answer) {
            const button = node('button', answer.question);
            button.type = 'button';
            button.addEventListener('click', () => showAnswer(answer));
            return button;
        }
        function showAnswer(answer) {
            input.value = answer.question;
            clear.hidden = false;
            response.replaceChildren(node('p', 'Mobi · From the guide'), node('h3', answer.question), node('p', answer.answer));
            const steps = document.createElement('ol');
            answer.steps.forEach(step => steps.append(node('li', step)));
            const source = node('a', 'Read in guide →');
            source.href = `#answer-${answer.id}`;
            source.addEventListener('click', () => { document.getElementById(`answer-${answer.id}`).open = true; });
            response.append(steps, source);
        }
        document.getElementById('mobi-form').addEventListener('submit', event => {
            event.preventDefault();
            const query = input.value.trim();
            response.replaceChildren();
            if (!query) { response.append(node('p', 'Type a question or choose one of the suggestions.')); input.focus(); return; }
            clear.hidden = false;
            const matches = matchAnswers(query, answers);
            if (!matches.length) {
                response.append(node('h3', 'I could not find a reliable answer in the guide.'), node('p', 'Try a specific task such as importing participants or preparing an archive. For account-specific help, contact support.'));
                const support = node('a', 'Contact support →'); support.href = '/contact'; response.append(support);
            } else if (matches.length === 1 || matches[0].score - matches[1].score >= 3) {
                showAnswer(matches[0].answer);
            } else {
                response.append(node('h3', 'Which question did you mean?'));
                matches.forEach(match => response.append(answerButton(match.answer)));
            }
        });
        mobi.querySelectorAll('[data-mobi-question]').forEach(button => button.addEventListener('click', () => showAnswer(byId.get(button.dataset.mobiQuestion))));
        clear.addEventListener('click', () => { input.value = ''; response.replaceChildren(); clear.hidden = true; input.focus(); });
        mobi.hidden = false;
    }

    const input = document.getElementById('mc-guide-search-input');
    const clear = document.getElementById('mc-guide-search-clear');
    const results = document.getElementById('mc-guide-search-results');
    if (input && clear && results) {
        const entries = [...document.querySelectorAll('[data-guide-search-item]')].map(element => ({
            anchor: element.dataset.guideAnchor || element.id,
            title: element.dataset.guideSearchTitle,
            text: normalise(`${element.textContent} ${element.dataset.guideKeywords || ''}`),
        }));
        const render = () => {
            const query = normalise(input.value);
            const words = tokens(query);
            results.replaceChildren(); clear.hidden = !input.value;
            if (query.length < 2 || !words.length) { results.hidden = true; return; }
            const matches = entries.filter(entry => words.every(word => entry.text.includes(word))).slice(0, 10);
            const summary = node('p', matches.length ? `${matches.length} matching guide items` : 'No matching guide items');
            summary.className = 'mc-guide-search-summary'; results.append(summary);
            const list = document.createElement('div'); list.className = 'mc-guide-search-list';
            matches.forEach(entry => {
                const link = node('a', entry.title); link.href = `#${entry.anchor}`;
                link.addEventListener('click', () => { const target = document.getElementById(entry.anchor); if (target?.tagName === 'DETAILS') target.open = true; results.hidden = true; });
                list.append(link);
            });
            results.append(list);
            if (!matches.length) results.append(node('small', 'Try a module name or ask Mobi a specific question.'));
            results.hidden = false;
        };
        input.addEventListener('input', render);
        input.addEventListener('keydown', event => { if (event.key === 'Escape') { results.hidden = true; } });
        clear.addEventListener('click', () => { input.value = ''; render(); input.focus(); });
    }
}
