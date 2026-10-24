@props([
    'status',
    'label' => null,
])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $tones = [
        'draft' => 'bg-ink-100 text-ink-700',
        'submitted' => 'bg-info-50 text-info-700',
        'published' => 'bg-success-50 text-success-700',
        'archived' => 'bg-ink-100 text-ink-600',
        'scheduled' => 'bg-info-50 text-info-700',
        'open' => 'bg-success-50 text-success-700',
        'closed' => 'bg-warning-50 text-warning-700',
        'announced' => 'bg-primary-50 text-primary-800',
        'payment_pending' => 'bg-warning-50 text-warning-700',
        'pending_payment' => 'bg-warning-50 text-warning-700',
        'pending_verification' => 'bg-warning-50 text-warning-700',
        'manual_review' => 'bg-warning-50 text-warning-700',
        'documents_pending' => 'bg-info-50 text-info-700',
        'documents_approved' => 'bg-success-50 text-success-700',
        'documents_rejected' => 'bg-danger-50 text-danger-700',
        'completed' => 'bg-primary-50 text-primary-800',
        'expired' => 'bg-ink-100 text-ink-600',
        'uploaded' => 'bg-info-50 text-info-700',
        'under_review' => 'bg-info-50 text-info-700',
        'approved' => 'bg-success-50 text-success-700',
        'needs_revision' => 'bg-warning-50 text-warning-700',
        'rejected' => 'bg-danger-50 text-danger-700',
        'confirmed' => 'bg-success-50 text-success-700',
        'failed' => 'bg-danger-50 text-danger-700',
        'accepted' => 'bg-success-50 text-success-700',
        'not_accepted' => 'bg-danger-50 text-danger-700',
        'waitlisted' => 'bg-warning-50 text-warning-700',
        'pending' => 'bg-ink-100 text-ink-700',
    ];

    $tone = $tones[$value] ?? 'bg-ink-100 text-ink-700';
    $text = $label ?? (is_object($status) && method_exists($status, 'label') ? $status->label() : $value);@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$tone]) }}>
    {{ $text }}
</span>
