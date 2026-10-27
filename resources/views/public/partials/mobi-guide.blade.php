<aside class="mc-mobi-intro" aria-labelledby="mobi-intro-title">
    <div class="mc-mobi-heading">
        <img src="{{ asset('brand/mobi-welcome.png') }}" alt="Mobi, your guide companion" width="104" height="104">
        <div><p class="mc-eyebrow">A little help along the way</p><h2 id="mobi-intro-title">Hi, I’m Mobi.</h2></div>
    </div>
    <p>Tell me what you’re trying to do. I’ll find the steps in this guide and help you choose what to read next.</p>
    <button type="button" class="mc-mobi-launch" data-mobi-open hidden>Ask Mobi <span aria-hidden="true">↗</span></button>
    <div class="mc-mobi-quick" aria-label="Popular guide questions">
        <a href="#answer-archive" data-mobi-question="archive">How do I get my final ZIP? <span aria-hidden="true">→</span></a>
        <a href="#answer-csv" data-mobi-question="csv">Can I import my participants? <span aria-hidden="true">→</span></a>
        <a href="#answer-price" data-mobi-question="price">When do I pay the fee? <span aria-hidden="true">→</span></a>
    </div>
    <small>Answers from the guide, with a link to every source.</small>
</aside>
<dialog id="mobi-dialog" aria-labelledby="mobi-title">
    <div class="mc-mobi" data-mobi>
        <div class="mc-mobi-dialog-head"><div class="mc-mobi-heading"><img src="{{ asset('brand/mobi-welcome.png') }}" alt="" width="48" height="48"><div><p class="mc-eyebrow">Guide companion</p><h2 id="mobi-title">Ask Mobi</h2></div></div><button type="button" id="mobi-close" aria-label="Close Mobi">✕</button></div>
        <p>Ask about a task in English or Romanian. Answers come from the English guide; I cannot see your account or change a project.</p>
        <form id="mobi-form" role="search" aria-label="Ask Mobi about MobilityCloud">
            <label for="mobi-question">What would you like to do?</label>
            <div class="mc-mobi-input"><input id="mobi-question" type="search" maxlength="240" autocomplete="off" placeholder="e.g. My ZIP is out of date" aria-describedby="mobi-help"><button type="submit">Find answer</button></div>
            <small id="mobi-help">Try a task or problem, or choose a topic below.</small>
        </form>
        <div class="mc-mobi-suggestions" aria-label="Suggested questions">
            <button type="button" data-mobi-question="start">Start a project</button>
            <button type="button" data-mobi-question="csv">Import participants</button>
            <button type="button" data-mobi-question="archive">Download ZIP</button>
            <button type="button" data-mobi-question="price">Understand fees</button>
        </div>
        <button type="button" id="mobi-back" hidden>← Previous answer</button>
        <div id="mobi-response" class="mc-mobi-response" role="status" aria-live="polite" aria-atomic="true"></div>
        <div class="mc-mobi-footer"><span>Guide answers · No external AI service</span><button id="mobi-clear" type="button" hidden>Clear question</button></div>
    </div>
</dialog>
<button type="button" class="mc-mobi-floating" data-mobi-open hidden aria-label="Open Mobi guide companion"><img src="{{ asset('brand/mobi-welcome.png') }}" alt="" width="36" height="36">Ask Mobi</button>
<noscript><p>Mobi needs JavaScript to match questions. All answers remain available in the chapters below.</p></noscript>
<script id="mobi-knowledge" type="application/json">{!! json_encode($guideAnswers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
<script type="module" src="{{ asset('js/guide-helper.js') }}?v={{ filemtime(public_path('js/guide-helper.js')) }}"></script>
