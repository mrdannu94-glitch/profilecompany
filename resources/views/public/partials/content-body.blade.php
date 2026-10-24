@if (filled($text))
    <div class="space-y-4 text-base leading-relaxed text-ink-700">
        {!! nl2br(e($text)) !!}
    </div>
@endif
