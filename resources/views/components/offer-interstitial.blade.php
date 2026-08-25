@props(['offer'])

@php
$isInvite = ($offer['type'] ?? '') === 'invite_to_apply';
@endphp

<div class="prototype-modal-backdrop" role="presentation"></div>
<section class="prototype-modal offer-interstitial {{ $isInvite ? 'invite' : 'prequalified' }}" role="dialog" aria-modal="true" aria-labelledby="offer-interstitial-title">
  <form method="POST" action="{{ route('prototype.interstitial.dismiss') }}">
    @csrf
    <button class="modal-close" type="submit" aria-label="Dismiss offer"><i class="ti ti-x"></i></button>
  </form>
  <div class="modal-icon-wrap" aria-hidden="true"><i class="ti {{ $isInvite ? 'ti-sparkles' : 'ti-award' }} modal-icon"></i></div>
  <span class="eyebrow">{{ $offer['eyebrow'] ?? 'For you' }}</span>
  <h2 id="offer-interstitial-title">{{ $offer['headline'] }}</h2>
  <p>{{ $offer['body'] }}</p>
  <div class="interstitial-highlight"><i class="ti ti-shield-check"></i>{{ $offer['highlight'] }}</div>
  <form method="POST" action="{{ route('prototype.application.start') }}">
    @csrf
    <button class="btn btn-primary modal-primary-action w-100" type="submit">{{ $offer['cta'] }}</button>
  </form>
  <form method="POST" action="{{ route('prototype.interstitial.dismiss') }}">
    @csrf
    <button class="btn modal-secondary-action w-100 mt-2" type="submit">{{ $isInvite ? 'Not now' : 'Maybe later' }}</button>
  </form>
</section>
