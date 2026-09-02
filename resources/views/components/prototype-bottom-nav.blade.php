@props(['scenario', 'current' => 'home'])

@php
  $loans = $scenario['loans'] ?? [];
  $firstLoan = $loans[0] ?? null;
  $application = $scenario['application'] ?? null;
  $savings = $scenario['products']['savings'] ?? null;
  $creditCard = $scenario['products']['credit_card'] ?? null;
  $wellness = $scenario['financial_wellness'] ?? [];
  $liteMode = ($scenario['experience']['mode'] ?? 'full') === 'origination_lite';
  $hasApplication = filled($application) && ($application['status'] ?? null) !== 'pending_funding';
  $items = [[
    'key' => 'home', 'label' => 'Home', 'icon' => 'ti-home', 'url' => route('prototype.index'),
    'active' => $current === 'home',
  ]];

  if ($firstLoan) {
    $items[] = [
      'key' => 'loan', 'label' => count($loans) > 1 ? 'Loans' : 'Loan', 'icon' => 'ti-wallet',
      'url' => route('prototype.loan', $firstLoan['id']),
      'active' => in_array($current, ['loan', 'payment', 'autopay'], true),
    ];
  } elseif ($application) {
    $applicationUrl = match ($application['next_step']['key'] ?? null) {
      'income' => route('prototype.lite.income'),
      'vehicle' => route('prototype.lite.vehicle'),
      'closing' => route('prototype.lite.closing'),
      default => route('prototype.application', $application['id']),
    };
    $items[] = [
      'key' => 'application', 'label' => ($application['status'] ?? null) === 'pending_funding' ? 'Funding' : 'Apply',
      'icon' => ($application['status'] ?? null) === 'pending_funding' ? 'ti-clock-check' : 'ti-clipboard-list',
      'url' => $applicationUrl,
      'active' => $current === 'application',
    ];
  }

  if (! $liteMode && $savings && count($items) < 4) {
    $items[] = ['key' => 'savings', 'label' => 'Savings', 'icon' => 'ti-pig-money', 'url' => route('prototype.product.savings'), 'active' => $current === 'savings'];
  }
  if (! $liteMode && $creditCard && count($items) < 4) {
    $items[] = ['key' => 'credit-card', 'label' => 'Card', 'icon' => 'ti-credit-card', 'url' => route('prototype.product.credit-card'), 'active' => $current === 'credit-card'];
  }

  if (! $liteMode && ! $hasApplication && count($items) < 4) {
    $items[] = ['key' => 'explore', 'label' => 'Explore', 'icon' => 'ti-sparkles', 'url' => route('prototype.offers'), 'active' => in_array($current, ['offer', 'protection'], true)];
  }
  if (! $liteMode && ! $hasApplication && count($items) < 4 && (($wellness['credit_monitoring_enabled'] ?? false) || ($wellness['bank_connected'] ?? false))) {
    $items[] = ['key' => 'wellness', 'label' => 'Money Hub', 'icon' => 'ti-heart-rate-monitor', 'url' => route('prototype.wellness'), 'active' => $current === 'wellness'];
  }
@endphp

<nav class="bottom-nav" aria-label="Mobile app navigation" style="--bottom-nav-items: {{ count($items) }}">
  @foreach($items as $item)
    <a class="{{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}" data-nav-item="{{ $item['key'] }}">
      <i class="ti {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
    </a>
  @endforeach
</nav>
