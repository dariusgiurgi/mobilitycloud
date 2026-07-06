@include('errors._layout', [
    'status' => 403,
    'eyebrow' => 'Access restricted',
    'title' => 'You do not have access to this area.',
    'message' => 'This section is reserved for accounts with the required platform or project permissions.',
    'details' => 'If you believe you should have access, contact the project owner or platform support.',
    'accent' => '#8b5cf6',
])
