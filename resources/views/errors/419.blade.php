@include('errors._layout', [
    'status' => 419,
    'eyebrow' => 'Session expired',
    'title' => 'Your secure session needs to be refreshed.',
    'message' => 'For your account safety, MobilityCloud refreshes inactive sessions. Please sign in again and continue from where you left off.',
    'details' => 'If this happens repeatedly while you are active, refresh the page once and try again.',
    'accent' => '#f59e0b',
])
