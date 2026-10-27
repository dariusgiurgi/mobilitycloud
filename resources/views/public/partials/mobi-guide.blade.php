<aside class="mc-mobi" aria-labelledby="mobi-title" data-mobi hidden>
    <div class="mc-mobi-heading">
        <img src="{{ asset('brand/mobi-welcome.png') }}" alt="Mobi, the MobilityCloud guide assistant" width="88" height="88">
        <div><p class="mc-eyebrow">Your guide companion</p><h2 id="mobi-title">Ask Mobi</h2></div>
    </div>
    <p>Find practical answers from this guide. I cannot see your account or change your projects.</p>
    <form id="mobi-form" role="search" aria-label="Ask Mobi about MobilityCloud">
        <label for="mobi-question">What would you like to do?</label>
        <div class="mc-mobi-input"><input id="mobi-question" type="search" maxlength="240" autocomplete="off" placeholder="How do I download the final archive?" aria-describedby="mobi-help"><button type="submit">Ask Mobi</button></div>
        <small id="mobi-help">Ask in English or Romanian. Answers use the English guide text.</small>
    </form>
    <div class="mc-mobi-suggestions" aria-label="Suggested questions">
        <button type="button" data-mobi-question="start">Start a project</button>
        <button type="button" data-mobi-question="csv">Import participants</button>
        <button type="button" data-mobi-question="archive">Download ZIP</button>
        <button type="button" data-mobi-question="price">Understand fees</button>
    </div>
    <div id="mobi-response" class="mc-mobi-response" role="status" aria-live="polite" aria-atomic="true"></div>
    <div class="mc-mobi-footer"><span>Guide answers · No external AI service</span><button id="mobi-clear" type="button" hidden>Clear question</button></div>
</aside>
<noscript><aside class="mc-guide-route"><h2>Find your answer in the guide</h2><p>Mobi needs JavaScript to match questions. All answers remain available in the chapters below.</p><a href="#account-guide">Open practical instructions →</a></aside></noscript>
<script id="mobi-knowledge" type="application/json">{!! json_encode($guideAnswers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
<script type="module" src="{{ asset('js/guide-helper.js') }}?v={{ filemtime(public_path('js/guide-helper.js')) }}"></script>
