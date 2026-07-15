@props([
    'tone' => 'success',
    'icon' => null,
    'title',
    'body' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
])

@php
    $toneIcons = [
        'success' => 'ti-circle-check',
        'warning' => 'ti-clock-hour-4',
        'urgent' => 'ti-alert-triangle',
    ];
    $toneButtons = [
        'success' => 'btn-outline-primary',
        'warning' => 'btn-primary',
        'urgent' => 'btn-danger',
    ];
    $resolvedIcon = $icon ?? ($toneIcons[$tone] ?? 'ti-info-circle');
    $ctaClass = $toneButtons[$tone] ?? 'btn-outline-primary';
@endphp

<div {{ $attributes->merge(['class' => 'account-alert account-alert-' . $tone]) }} role="status">
  <i class="ti {{ $resolvedIcon }}"></i>
  <div class="account-alert-body">
    <strong>{{ $title }}</strong>
    @if($body)
      <span>{{ $body }}</span>
    @endif
  </div>
  @if($ctaLabel && $ctaUrl)
    <a href="{{ $ctaUrl }}" class="btn btn-sm {{ $ctaClass }}">{{ $ctaLabel }}</a>
  @endif
</div>
