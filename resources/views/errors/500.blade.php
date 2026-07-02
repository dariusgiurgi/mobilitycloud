@include('errors._layout', [
    'status' => 500,
    'eyebrow' => 'Temporary platform issue',
    'title' => 'Something went wrong while loading this page.',
    'message' => 'The platform could not complete the request. Your data should remain safe; please try again in a moment.',
    'details' => 'If the issue persists, contact support and mention what you were trying to open.',
    'accent' => '#ef4444',
])
