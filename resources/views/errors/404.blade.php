@include('errors._layout', [
    'status' => 404,
    'eyebrow' => 'Page not found',
    'title' => 'This page is not available.',
    'message' => 'The link may be outdated, the record may have been removed, or the address may be incorrect.',
    'details' => 'Use the dashboard to continue working from a known place.',
    'accent' => '#06b6d4',
])
