@props(['action'])

<article class="app-card next-best-action {{ $action['variant'] ?? '' }}">
  <div class="next-best-icon" aria-hidden="true"><i class="ti {{ $action['icon'] ?? 'ti-sparkles' }}"></i></div>
  <div class="next-best-copy">
    <span class="eyebrow">{{ $action['eyebrow'] }}</span>
    <h2>{{ $action['headline'] }}</h2>
    <p>{{ $action['body'] }}</p>
  </div>
  @if(($action['method'] ?? 'get') === 'post')
    <form method="POST" action="{{ $action['url'] }}">
      @csrf
      <button class="next-best-link" type="submit"><span>{{ $action['cta'] }}</span><i class="ti ti-arrow-right"></i></button>
    </form>
  @else
    <a class="next-best-link" href="{{ $action['url'] }}"><span>{{ $action['cta'] }}</span><i class="ti ti-arrow-right"></i></a>
  @endif
</article>
