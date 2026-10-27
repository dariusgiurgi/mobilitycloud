// Public guide retrieval only: no network requests, account access or stored conversations.
export const normalise = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
const stopWords = new Set('a an the i me my we our you your how do does did can could should would what where when why is are am be been to of in on for from with and or it its this that have has as at about please want need tell know help nu cum ce de din la pe cu si sa imi mi ma eu un o al ale este sunt pot vreau vrea care cand unde meu mea proiect project platform mobilitycloud'.split(' '));
const aliases = new Map();
for (const group of [
    ['project', 'projects', 'proiectul', 'proiectului', 'proiecte'],
    ['participant', 'participants', 'participantii', 'participanti', 'participantilor'],
    ['document', 'documents', 'documente', 'documentele'], ['file', 'files', 'fisiere', 'fisierele'],
    ['archive', 'archives', 'arhiva', 'arhivei'], ['download', 'downloading', 'descarc', 'descarca', 'descarcare'],
    ['save', 'saving', 'saved', 'salva', 'salvez', 'salveaza', 'salvare'],
    ['invite', 'inviting', 'invitation', 'invita', 'invit', 'invitatie'],
    ['expired', 'expiry', 'expires', 'expirat', 'expira'], ['fee', 'fees', 'taxa', 'taxe'],
]) for (const word of group) aliases.set(word, group[0]);
const tokens = value => [...new Set(normalise(value).split(' ').map(word => aliases.get(word) || word).filter(word => word.length > 1 && !stopWords.has(word)))];
function nearWord(a, b) {
    if (Math.min(a.length, b.length) < 5 || Math.abs(a.length - b.length) > 1) return false;
    let i = 0; while (i < Math.min(a.length, b.length) && a[i] === b[i]) i++;
    if (a.length === b.length) return a.slice(i + 1) === b.slice(i + 1) || (a[i] === b[i + 1] && a[i + 1] === b[i] && a.slice(i + 2) === b.slice(i + 2));
    return a.length > b.length ? a.slice(i + 1) === b.slice(i) : a.slice(i) === b.slice(i + 1);
}
export function matchAnswers(query, answers) {
    const words = tokens(query);
    if (!words.length) return [];
    const records = answers.map(answer => ({ answer, index: tokens(`${answer.question} ${answer.keywords} ${(answer.examples || []).join(' ')}`) }));
    const frequency = word => records.filter(record => record.index.includes(word)).length;
    return records.map(({ answer, index }) => {
        let matched = 0, score = 0;
        for (const word of words) {
            const hit = index.includes(word) ? word : index.find(candidate => nearWord(word, candidate));
            if (!hit) continue;
            matched++;
            score += (1 + Math.log(1 + answers.length / (1 + frequency(hit)))) * (hit === word ? 1 : .8);
        }
        const exact = [answer.question, ...(answer.examples || [])].some(example => normalise(example) === normalise(query));
        const coverage = matched / words.length;
        return { answer, score: score + coverage * 5 + (exact ? 30 : 0), coverage, exact };
    }).filter(result => result.coverage >= 2 / 3 || result.exact)
        .sort((a, b) => b.score - a.score).slice(0, 3);
}

if (typeof document !== 'undefined') {
    const node = (tag, text) => { const element = document.createElement(tag); element.textContent = text; return element; };
    const chapters = [...document.querySelectorAll('.mc-guide-chapter')];
    const chapterContainer = document.querySelector('.mc-guide-chapters');
    const toc = document.querySelector('.mc-guide-toc');
    const chapterLinks = chapters.map(chapter => toc?.querySelector(`a[href="#${chapter.id}"]`));
    const paths = {
        writing: ['account-guide', 'prepare', 'create', 'write', 'table-models', 'team-guide', 'troubleshooting-guide'],
        managing: ['budget-guide', 'billing-guide', 'team-guide', 'participants-guide', 'documents-guide', 'mobility-guide', 'tasks-guide'],
        exporting: ['export-guide', 'documents-guide', 'participants-guide', 'tasks-guide', 'troubleshooting-guide'],
    };
    const pathNames = { writing: 'Writing a project', managing: 'Managing an approved project', exporting: 'Preparing the final ZIP' };
    const resetButtons = [...document.querySelectorAll('#guide-show-all, [data-guide-reset]')];
    function setPath(path) {
        const ids = paths[path];
        const ordered = ids ? ids.map(id => chapters.find(chapter => chapter.id === id)) : chapters;
        chapters.forEach((chapter, index) => { chapter.hidden = !!ids && !ids.includes(chapter.id); if (chapterLinks[index]) chapterLinks[index].hidden = chapter.hidden; });
        ordered.forEach(chapter => { chapterContainer.append(chapter); const link = chapterLinks[chapters.indexOf(chapter)]; if (link) toc.insertBefore(link, toc.querySelector('.mc-guide-toc-help')); });
        resetButtons.forEach(button => button.hidden = !ids);
        document.querySelector('.mc-guide-start').hidden = !!ids;
        document.querySelectorAll('[data-guide-path]').forEach(link => { if (link.dataset.guidePath === path) link.setAttribute('aria-current', 'true'); else link.removeAttribute('aria-current'); });
        document.getElementById('guide-path-status').textContent = ids ? `${pathNames[path]} · ${ids.length} focused chapters. You can return to all chapters at any time.` : 'Showing all chapters. Choose a path above to focus the guide.';
    }
    document.querySelectorAll('[data-guide-path]').forEach(link => link.addEventListener('click', () => setPath(link.dataset.guidePath)));
    resetButtons.forEach(button => button.addEventListener('click', () => setPath(null)));
    function revealSource(id) {
        const target = document.getElementById(id);
        if (!target) return;
        if (target.closest('.mc-guide-chapter')?.hidden) setPath(null);
        if (target.tagName === 'DETAILS') target.open = true;
        chapterLinks.forEach(link => { if (!link) return; if (link.hash === `#${target.closest('.mc-guide-chapter')?.id}`) link.setAttribute('aria-current', 'location'); else link.removeAttribute('aria-current'); });
    }
    window.addEventListener('hashchange', () => revealSource(location.hash.slice(1)));
    revealSource(location.hash.slice(1));

    const knowledge = document.getElementById('mobi-knowledge');
    const dialog = document.getElementById('mobi-dialog');
    if (knowledge && dialog) {
        const answers = JSON.parse(knowledge.textContent);
        const byId = new Map(answers.map(answer => [answer.id, answer]));
        const input = document.getElementById('mobi-question');
        const response = document.getElementById('mobi-response');
        const clear = document.getElementById('mobi-clear');
        const back = document.getElementById('mobi-back');
        let history = [];
        function openMobi() { if (!dialog.open) dialog.showModal(); input.focus({ preventScroll: true }); }
        document.querySelectorAll('[data-mobi-open]').forEach(button => { button.hidden = false; button.addEventListener('click', openMobi); });
        document.getElementById('mobi-close').addEventListener('click', () => dialog.close());
        function answerButton(answer) {
            const button = node('button', answer.question); button.type = 'button';
            button.addEventListener('click', () => showAnswer(answer)); return button;
        }
        function showAnswer(answer, remember = true) {
            if (remember && history.at(-1) !== answer.id) history = [...history, answer.id].slice(-8);
            back.hidden = history.length < 2;
            input.value = answer.question; clear.hidden = false;
            response.replaceChildren(node('p', 'Mobi · From the guide'), node('h3', answer.question), node('p', answer.answer));
            const steps = document.createElement('ol'); answer.steps.forEach(step => steps.append(node('li', step)));
            const source = node('a', 'Read in guide →'); source.href = `#answer-${answer.id}`;
            source.addEventListener('click', () => { revealSource(`answer-${answer.id}`); dialog.close(); });
            response.append(steps, source);
            const related = (answer.related || []).map(id => byId.get(id)).filter(Boolean);
            if (related.length) { const section = document.createElement('div'); section.className = 'mc-mobi-related'; section.append(node('h4', 'What would you like to know next?')); related.forEach(item => section.append(answerButton(item))); response.append(section); }
            // Keep the next answer visible when a follow-up was selected near the bottom.
            dialog.scrollTop = 0;
        }
        back.addEventListener('click', () => { history.pop(); showAnswer(byId.get(history.at(-1)), false); });
        document.getElementById('mobi-form').addEventListener('submit', event => {
            event.preventDefault(); const query = input.value.trim(); response.replaceChildren();
            if (!query) { response.append(node('p', 'Type a question or choose one of the suggestions.')); input.focus(); return; }
            clear.hidden = false;
            const matches = matchAnswers(query, answers);
            if (!matches.length) {
                response.append(node('h3', 'I could not find a reliable answer in the guide.'), node('p', 'Choose a topic below, or try a task such as “import participants” or “ZIP expired”. For help with your own account, contact support.'));
                ['start', 'csv', 'archive', 'invoice'].forEach(id => response.append(answerButton(byId.get(id))));
                const support = node('a', 'Contact support →'); support.href = '/contact'; response.append(support);
            } else if (matches.length === 1 || matches[0].exact || matches[0].score - matches[1].score >= 3) showAnswer(matches[0].answer);
            else { response.append(node('h3', 'Which question did you mean?')); matches.forEach(match => response.append(answerButton(match.answer))); }
        });
        document.querySelectorAll('[data-mobi-question]').forEach(button => button.addEventListener('click', event => { event.preventDefault(); openMobi(); showAnswer(byId.get(button.dataset.mobiQuestion)); }));
        clear.addEventListener('click', () => { input.value = ''; response.replaceChildren(); history = []; back.hidden = true; clear.hidden = true; input.focus(); });
    }

    const input = document.getElementById('mc-guide-search-input');
    const clear = document.getElementById('mc-guide-search-clear');
    const results = document.getElementById('mc-guide-search-results');
    if (input && clear && results) {
        const entries = [...document.querySelectorAll('[data-guide-search-item]')].map(element => ({ anchor: element.dataset.guideAnchor || element.id, title: element.dataset.guideSearchTitle, text: tokens(`${element.textContent} ${element.dataset.guideKeywords || ''}`) }));
        const render = () => {
            const words = tokens(input.value); results.replaceChildren(); clear.hidden = !input.value;
            if (normalise(input.value).length < 2 || !words.length) { results.hidden = true; return; }
            const matches = entries.filter(entry => words.every(word => entry.text.some(term => term.includes(word) || nearWord(word, term))))
                .sort((a, b) => Number(b.anchor.startsWith('answer-')) - Number(a.anchor.startsWith('answer-'))).slice(0, 10);
            const summary = node('p', matches.length ? `${matches.length} matching guide items` : 'No matching guide items'); summary.className = 'mc-guide-search-summary'; results.append(summary);
            const list = document.createElement('div'); list.className = 'mc-guide-search-list';
            matches.forEach(entry => { const link = node('a', entry.title); link.href = `#${entry.anchor}`; link.addEventListener('click', () => { revealSource(entry.anchor); results.hidden = true; }); list.append(link); });
            results.append(list);
            if (!matches.length) results.append(node('small', 'Try a module name or ask Mobi a specific question.'));
            results.hidden = false;
        };
        input.addEventListener('input', render);
        input.addEventListener('keydown', event => { if (event.key === 'Escape') results.hidden = true; });
        clear.addEventListener('click', () => { input.value = ''; render(); input.focus(); });
    }
}
