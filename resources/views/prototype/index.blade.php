@php
$navbarView = 'layouts/sections/navbar/navbar-empty';
$footerView = 'layouts/sections/footer/footer-empty';
$isMenu = false;
$isNavbar = false;
$isFooter = false;
$loans = $scenario['loans'] ?? [];
$application = $scenario['application'] ?? null;
$offer = $scenario['offer'] ?? null;
$wellness = $scenario['financial_wellness'] ?? [];
$vehicle = $scenario['assets']['vehicles'][0] ?? null;
$branch = $scenario['branch'] ?? [];
$firstLoan = $loans[0] ?? null;
$savings = $scenario['products']['savings'] ?? null;
$creditCard = $scenario['products']['credit_card'] ?? null;
$pendingFunding = ($application['status'] ?? null) === 'pending_funding' ? $application : null;
$accountCount = count($loans) + ($savings ? 1 : 0) + ($creditCard ? 1 : 0) + ($pendingFunding ? 1 : 0);
$compactAccounts = $accountCount > 1;
$secureLoanUrl = route('prototype.offers');
$scheduledPayment = $scheduledPayment ?? ($scenario['payments']['pending'] ?? null);
$money = fn ($value) => '$' . number_format((float) $value, 2);
$date = fn ($value) => \Carbon\Carbon::parse($value)->format('M j, Y');
$servicingAlert = $scenario['servicing_alert'] ?? [];
$formatServicingText = function (?string $text) use ($firstLoan, $money, $date) {
    return strtr($text ?? '', [
        '$pastDue' => $money($firstLoan['past_due_amount'] ?? 0),
        '$dueDate' => $date($firstLoan['next_payment_date'] ?? now()),
    ]);
};
$servicingCta = $servicingAlert['cta'] ?? 'Make a payment';
$servicingUrl = str_contains(strtolower($servicingCta), 'contact') ? route('prototype.support') : route('prototype.payment');
$highlightCards = [];
if ($firstLoan && ! ($scenario['alerts']['late_payment'] ?? false)) {
    $highlightCards[] = [
        'icon' => 'ti-calendar-dollar',
        'label' => 'Upcoming payment',
        'title' => $money($firstLoan['next_payment_amount'] ?? 0) . ' due ' . $date($firstLoan['next_payment_date'] ?? now()),
        'body' => ($firstLoan['autopay_enabled'] ?? false) ? 'AutoPay is on for this loan.' : 'Schedule a payment or turn on AutoPay.',
        'url' => route('prototype.payment'),
        'cta' => 'View payment',
    ];
}
if (($offer['status'] ?? null) === 'available') {
    $highlightCards[] = [
        'icon' => ($offer['type'] ?? '') === 'prequalified' ? 'ti-award' : 'ti-sparkles',
        'label' => ($offer['type'] ?? '') === 'prequalified' ? 'Personalized offer' : 'Explore options',
        'title' => ($offer['type'] ?? '') === 'prequalified' ? 'You may be prequalified for ' . ('$' . number_format((float) $offer['amount'])) : 'See loan options in minutes',
        'body' => 'Checking will not impact your credit score.',
        'url' => $secureLoanUrl,
        'cta' => ($offer['type'] ?? '') === 'prequalified' ? 'View offer' : 'Check offers',
    ];
}
$highlightCards[] = [
    'icon' => 'ti-folder',
    'label' => 'Document Center',
    'title' => $firstLoan ? 'Loan documents are ready' : 'Application documents in one place',
    'body' => $firstLoan ? 'View agreements, schedules, and statements.' : 'Keep application documents organized as you apply.',
    'url' => route('prototype.documents'),
    'cta' => 'Open documents',
];
$highlightCards[] = [
    'icon' => 'ti-map-pin',
    'label' => 'Your branch',
    'title' => $branch['name'] ?? 'Regional Finance branch',
    'body' => ($branch['hours'] ?? 'Branch hours available') . ' · ' . ($branch['phone'] ?? ''),
    'url' => route('prototype.support'),
    'cta' => 'Get support',
];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Regional Finance Mobile Prototype')

@section('page-style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}?v=20260827application-cta">
@endsection

@section('content')
<div class="prototype-shell" data-prototype-theme="light">
  <aside class="prototype-context d-none d-xl-block">
    <span class="eyebrow">Selected scenario</span>
    <h2>{{ $scenario['name'] }}</h2>
    <p>{{ $scenario['description'] }}</p>
    <a class="btn btn-outline-primary" href="{{ route('prototype.scenarios') }}">Change scenario</a>
  </aside>

  <main class="phone-frame" aria-label="Regional Finance mobile app prototype">
    <header class="app-header">
      <nav class="top-navbar" aria-label="App actions">
        <button class="top-nav-btn app-menu-button" type="button" aria-label="Open menu" aria-controls="mobile-side-menu" aria-expanded="false" data-menu-toggle>
          <span class="hamburger-icon" aria-hidden="true"></span>
        </button>
        <a class="top-logo-link" href="{{ route('prototype.index') }}" aria-label="Regional Finance home">
          <img class="regional-logo" src="{{ asset('assets/img/branding/regionals-logo.svg') }}" alt="Regional Finance">
        </a>
      </nav>
      <div class="brand-block">
        <h1>Hi, {{ $scenario['customer']['first_name'] }}</h1>
      </div>
    </header>

    <div class="side-menu-backdrop" hidden data-menu-close></div>
    <aside class="side-menu" id="mobile-side-menu" aria-label="Account menu" aria-hidden="true">
      <div class="side-menu-header">
        <img class="regional-logo" src="{{ asset('assets/img/branding/regionals-logo.svg') }}" alt="Regional Finance">
        <button class="top-nav-btn" type="button" aria-label="Close menu" data-menu-close>
          <i class="ti ti-x"></i>
        </button>
      </div>
      <a class="side-menu-profile" href="{{ route('prototype.profile') }}">
        <span class="profile-avatar"><i class="ti ti-user-circle"></i></span>
        <span>
          <strong>{{ $scenario['customer']['first_name'] }} {{ $scenario['customer']['last_name'] ?? '' }}</strong>
          <small>View profile</small>
        </span>
        <i class="ti ti-chevron-right"></i>
      </a>
      <nav class="side-menu-nav" aria-label="Menu links">
        @if($firstLoan)
          <a href="{{ route('prototype.loan', $firstLoan['id']) }}"><i class="ti ti-wallet"></i><span>{{ count($loans) > 1 ? 'Loans' : 'Loan' }}</span></a>
        @elseif($application)
          <a href="{{ route('prototype.application', $application['id']) }}"><i class="ti ti-clipboard-list"></i><span>Application</span></a>
        @else
          <a href="{{ route('prototype.offers') }}"><i class="ti ti-sparkles"></i><span>Explore options</span></a>
        @endif
        <a href="{{ route('prototype.documents') }}"><i class="ti ti-folder"></i><span>Document Center</span></a>
        <a href="{{ route('prototype.settings') }}"><i class="ti ti-settings"></i><span>Settings</span></a>
        <a href="{{ route('prototype.chat') }}"><i class="ti ti-message-chatbot"></i><span>AI Assistant</span></a>
        <a href="{{ route('prototype.notifications') }}"><i class="ti ti-bell"></i><span>Notifications</span></a>
        <a href="{{ route('prototype.support') }}"><i class="ti ti-headset"></i><span>Support</span></a>
      </nav>
      <div class="side-menu-footer">
        <span>Your branch</span>
        <strong>{{ $branch['name'] ?? 'Regional Finance branch' }}</strong>
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $branch['phone'] ?? '') }}"><i class="ti ti-phone"></i>{{ $branch['phone'] ?? '(800) 000-0000' }}</a>
      </div>
    </aside>

    @if(! $modules['show_late_banner'] && $modules['show_application'] && $modules['next_best_action'])
      <x-next-best-action-card :action="$modules['next_best_action']" />
    @endif

    <aside class="scenario-floater" aria-label="Prototype scenario controls">
      <button type="button" aria-label="Show current prototype scenario">
        <i class="ti ti-flask"></i>
      </button>
      <div class="scenario-floater-panel">
        <span class="eyebrow">Scenario lab</span>
        <strong>{{ $scenario['name'] }}</strong>
        <a href="{{ route('prototype.scenarios') }}"><i class="ti ti-adjustments-horizontal"></i>Open builder</a>
      </div>
    </aside>

    @if($scheduledPayment)
      <section class="pending-payment-dashboard" role="status">
        <div class="pending-payment-dashboard-icon"><i class="ti ti-clock-check"></i></div>
        <div>
          <span class="eyebrow">Pending payment</span>
          <strong>{{ $money($scheduledPayment['amount']) }} scheduled</strong>
          <small>{{ $date($scheduledPayment['payment_date']) }} from {{ $scheduledPayment['account']['label'] }}</small>
        </div>
        <a href="{{ route('prototype.payment') }}" aria-label="View pending payment"><i class="ti ti-chevron-right"></i></a>
      </section>
    @endif

    @if($modules['show_late_banner'])
      <section class="alert-card urgent" role="status">
        <div>
          <strong>{{ $servicingAlert['title'] ?? 'Payment past due' }}</strong>
          <span>{{ $formatServicingText($servicingAlert['body'] ?? '$pastDue was due $dueDate.') }}</span>
        </div>
        <a href="{{ $servicingUrl }}" class="btn btn-danger btn-sm">{{ $servicingCta }}</a>
      </section>
    @elseif($modules['show_payment_due_banner'])
      <section class="alert-card warning" role="status">
        <div>
          <strong>Payment due soon</strong>
          <span>{{ $money($firstLoan['next_payment_amount'] ?? 0) }} is due {{ $date($firstLoan['next_payment_date']) }}.</span>
        </div>
        <a href="{{ route('prototype.payment') }}" class="btn btn-primary btn-sm">Make a payment</a>
      </section>
    @endif

    @if($accountCount === 0 && ! $modules['show_application'])
      <section class="app-card relationship-welcome-card">
        <div class="relationship-welcome-icon"><i class="ti ti-sparkles"></i></div>
        <div>
          <span class="eyebrow">Explore</span>
          <h2>See what you qualify for</h2>
          <p>Check personalized loan options in minutes.</p>
        </div>
      </section>
    @endif

    @if($accountCount > 0)
      <section class="section-block">
        <div class="section-title">
          <h2>My accounts</h2>
        </div>
        <div class="product-stack {{ $compactAccounts ? 'is-compact' : 'is-expanded' }}">
          @if($pendingFunding)
            <a class="app-card account-product-card compact pending-funding-product" href="{{ route('prototype.application', $pendingFunding['id']) }}">
              <span class="account-product-icon"><i class="ti ti-clock-check"></i></span>
              <span class="account-product-main">
                <span class="account-product-title">Personal loan <em>Approved</em></span>
                <strong>$3,500.00</strong>
                <small>Loan amount</small>
              </span>
              <span class="account-product-due"><small>Status</small><strong>Funding</strong><span>Within 1 business day</span></span>
              <i class="ti ti-chevron-right account-product-chevron"></i>
            </a>
          @endif
          @foreach($loans as $loan)
            @php
              $loanIsPastDue = $loan['status'] !== 'current';
              if ($loanIsPastDue) {
                $loanAlertTone = 'urgent';
                $loanAlertTitle = $servicingAlert['title'] ?? 'Payment past due';
                $loanAlertBody = $formatServicingText($servicingAlert['body'] ?? '$pastDue was due $dueDate.');
              } elseif ($modules['show_payment_due_banner']) {
                $loanAlertTone = 'warning';
                $loanAlertTitle = 'Payment due soon';
                $loanAlertBody = $money($loan['next_payment_amount']) . ' is due ' . $date($loan['next_payment_date']) . '.';
              } else {
                $loanAlertTone = 'success';
                $loanAlertTitle = "You're all caught up";
                $loanAlertBody = '$0 is due until your next statement cycle.';
              }
              $loanAutopayEnabled = (($autopayOverride['loan_id'] ?? null) === $loan['id'])
                ? (bool) $autopayOverride['enrolled']
                : (bool) ($loan['autopay_enabled'] ?? false);
              $loanPendingPayment = ($scheduledPayment['loan_id'] ?? null) === $loan['id'] ? $scheduledPayment : null;
              if ($loanPendingPayment) {
                $loanAlertTone = 'warning';
                $loanAlertTitle = 'Payment pending';
                $loanAlertBody = $money($loanPendingPayment['amount']) . ' is scheduled for ' . $date($loanPendingPayment['payment_date']) . '.';
              }
              $loanAmountDue = (float) ($loan['amount_due'] ?? 0);
            @endphp
            @if($compactAccounts)
              <a class="app-card account-product-card compact loan-product {{ $loanIsPastDue ? 'past-due' : '' }}" href="{{ route('prototype.loan', $loan['id']) }}">
                <span class="account-product-icon"><i class="ti ti-wallet"></i></span>
                <span class="account-product-main">
                  <span class="account-product-title">{{ $loan['name'] }} @if($loanIsPastDue)<em class="danger">Past due</em>@endif</span>
                  <strong>{{ $money($loan['balance']) }}</strong>
                  <small>Balance</small>
                </span>
                <span class="account-product-due">
                  <small>Amount due</small>
                  <strong>{{ $money($loanAmountDue) }}</strong>
                  <span>{{ $date($loan['next_payment_date']) }}</span>
                </span>
                <i class="ti ti-chevron-right account-product-chevron"></i>
              </a>
            @else
              <article class="app-card loan-card account-product-card expanded">
                <div class="card-heading">
                  <span class="card-title-with-icon"><i class="ti ti-wallet"></i>{{ $loan['name'] }}</span>
                  <span class="loan-status-pills">
                    <span class="status-pill {{ $loan['status'] === 'current' ? 'success' : 'danger' }}">{{ $loan['status'] === 'current' ? 'Current' : ($scenario['default_status']['label'] ?? 'Past due') }}</span>
                    <span class="status-pill autopay-pill {{ $loanAutopayEnabled ? 'success' : 'neutral' }}"><i class="ti ti-refresh"></i>AutoPay {{ $loanAutopayEnabled ? 'On' : 'Off' }}</span>
                    @if($loanPendingPayment)<span class="status-pill pending"><i class="ti ti-clock-check"></i>Payment pending</span>@endif
                  </span>
                </div>
                <div class="balance">{{ $money($loan['balance']) }}</div>
                <p class="muted">Current balance</p>
                <div class="loan-grid">
                  <div><i class="ti ti-cash"></i><span>Next payment</span><strong>{{ $money($loan['next_payment_amount']) }}</strong></div>
                  <div><i class="ti ti-calendar-due"></i><span>Due date</span><strong>{{ $date($loan['next_payment_date']) }}</strong></div>
                </div>
                <x-account-alert :tone="$loanAlertTone" :title="$loanAlertTitle" :body="$loanAlertBody" />
                <div class="button-row">
                  <a href="{{ route('prototype.payment') }}" class="btn btn-primary flex-fill"><i class="ti {{ $loanPendingPayment ? 'ti-clock-check' : 'ti-credit-card' }}"></i>{{ $loanPendingPayment ? 'View payment' : 'Make a payment' }}</a>
                  <a href="{{ route('prototype.loan', $loan['id']) }}" class="btn btn-outline-primary flex-fill"><i class="ti ti-chevron-right"></i>Details</a>
                </div>
              </article>
            @endif
          @endforeach

          @if($savings)
            @if($compactAccounts)
              <a class="app-card account-product-card compact savings-product" href="{{ route('prototype.product.savings') }}">
                <span class="account-product-icon"><i class="ti ti-pig-money"></i></span>
                <span class="account-product-main"><span class="account-product-title">{{ $savings['name'] }}</span><strong>{{ $money($savings['balance']) }}</strong><small>Balance</small></span>
                <span class="account-product-due"><small>Available</small><strong>{{ $money($savings['available_balance']) }}</strong><span>&bull;&bull;{{ $savings['last_four'] }}</span></span>
                <i class="ti ti-chevron-right account-product-chevron"></i>
              </a>
            @else
              <article class="app-card account-product-card expanded deposit-product-card">
                <div class="card-heading"><span class="card-title-with-icon"><i class="ti ti-pig-money"></i>{{ $savings['name'] }}</span><span class="status-pill success">Active</span></div>
                <div class="balance">{{ $money($savings['balance']) }}</div><p class="muted">Current balance &bull; &bull;&bull;{{ $savings['last_four'] }}</p>
                <div class="loan-grid"><div><i class="ti ti-cash"></i><span>Available balance</span><strong>{{ $money($savings['available_balance']) }}</strong></div><div><i class="ti ti-percentage"></i><span>APY</span><strong>{{ number_format($savings['apy'], 2) }}%</strong></div></div>
                <a href="{{ route('prototype.product.savings') }}" class="btn btn-outline-primary w-100"><i class="ti ti-chevron-right"></i>View savings</a>
              </article>
            @endif
          @endif

          @if($creditCard)
            @if($compactAccounts)
              <a class="app-card account-product-card compact card-product" href="{{ route('prototype.product.credit-card') }}">
                <span class="account-product-icon"><i class="ti ti-credit-card"></i></span>
                <span class="account-product-main"><span class="account-product-title">{{ $creditCard['name'] }}</span><strong>{{ $money($creditCard['balance']) }}</strong><small>Balance &bull; &bull;&bull;{{ $creditCard['last_four'] }}</small></span>
                <span class="account-product-due"><small>Amount due</small><strong>{{ $money($creditCard['amount_due']) }}</strong><span>{{ $date($creditCard['due_date']) }}</span></span>
                <i class="ti ti-chevron-right account-product-chevron"></i>
              </a>
            @else
              <article class="app-card account-product-card expanded credit-product-card">
                <div class="card-heading"><span class="card-title-with-icon"><i class="ti ti-credit-card"></i>{{ $creditCard['name'] }}</span><span class="status-pill success">Current</span></div>
                <div class="balance">{{ $money($creditCard['balance']) }}</div><p class="muted">Current balance &bull; &bull;&bull;{{ $creditCard['last_four'] }}</p>
                <div class="loan-grid"><div><i class="ti ti-cash"></i><span>Amount due</span><strong>{{ $money($creditCard['amount_due']) }}</strong></div><div><i class="ti ti-calendar-due"></i><span>Due date</span><strong>{{ $date($creditCard['due_date']) }}</strong></div></div>
                <x-account-alert tone="success" title="Nothing due right now" body="Your account is current through this statement cycle." />
                <a href="{{ route('prototype.product.credit-card') }}" class="btn btn-outline-primary w-100"><i class="ti ti-chevron-right"></i>View credit card</a>
              </article>
            @endif
          @endif
        </div>
      </section>
    @endif

    @if(! $modules['show_application'] && $modules['next_best_action'])
      <x-next-best-action-card :action="$modules['next_best_action']" />
    @endif

    @if($modules['show_credit_score'] || $modules['show_spending'])
      <section class="section-block">
        <div class="section-title">
          <h2><i class="ti ti-heart-rate-monitor"></i>Financial health</h2>
          <a href="{{ route('prototype.wellness') }}">View</a>
        </div>
        <div class="wellness-grid">
          @if($modules['show_credit_score'])
            <a class="app-card metric-card" href="{{ route('prototype.wellness') }}">
              <i class="ti ti-chart-line"></i>
              <span>Credit score</span>
              <strong>{{ $wellness['credit_score'] }}</strong>
              <small>{{ $wellness['credit_score_change'] > 0 ? 'Up ' : ($wellness['credit_score_change'] < 0 ? 'Down ' : '') }}{{ abs($wellness['credit_score_change']) }}{{ $wellness['credit_score_change'] == 0 ? 'No change' : ' points' }}</small>
            </a>
          @endif
          @if($modules['show_spending'])
            <a class="app-card metric-card" href="{{ route('prototype.wellness') }}">
              <i class="ti ti-receipt-2"></i>
              <span>Monthly spending</span>
              <strong>{{ $money($wellness['monthly_spending']) }}</strong>
              <small>{{ $wellness['cash_flow_status'] }}</small>
            </a>
          @endif
        </div>
      </section>
    @endif

    @if($modules['show_vehicle'])
      <section class="app-card vehicle-card">
        <div class="vehicle-card-heading">
          <div>
            <span class="vehicle-card-label"><i class="ti ti-car"></i><span class="eyebrow">Vehicle estimate</span></span>
            <h2>{{ $vehicle['year'] }} {{ $vehicle['make'] }} {{ $vehicle['model'] }}</h2>
          </div>
          <div class="vehicle-card-image">
            <img src="{{ asset('assets/img/illustrations/fleet-car.png') }}" alt="Top view of {{ $vehicle['year'] }} {{ $vehicle['make'] }} {{ $vehicle['model'] }}">
          </div>
        </div>
        <div class="loan-grid">
          <div><span>Estimated value</span><strong>{{ $money($vehicle['estimated_value']) }}</strong></div>
          <div><span>Estimated equity</span><strong>{{ $money($vehicle['estimated_equity']) }}</strong></div>
        </div>
        <p class="muted">Estimate last updated {{ $date($vehicle['last_updated']) }}. This is not a guaranteed value.</p>
        <a href="{{ route('prototype.assets') }}" class="btn btn-outline-primary w-100">View vehicle details</a>
      </section>
    @endif

    <section class="app-card branch-card branch-card-compact">
      <div class="card-heading">
        <span class="eyebrow">Your branch</span>
        <i class="ti ti-map-pin"></i>
      </div>
      <h2>{{ $branch['name'] ?? 'Regional Finance branch' }}</h2>
      <div
        class="branch-map branch-map-home"
        data-branch-map
        data-lat="{{ $branch['lat'] ?? 34.8334 }}"
        data-lng="{{ $branch['lng'] ?? -82.3075 }}"
        data-zoom="14"
        data-title="{{ $branch['name'] ?? 'Regional Finance branch' }}"
        data-map-url="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch['address'] ?? 'Regional Finance') }}"
        role="link"
        tabindex="0"
        aria-label="Open {{ $branch['name'] ?? 'Regional Finance branch' }} in Google Maps"
      >
        <div class="branch-map-fallback">
          <i class="ti ti-map-pin"></i>
          <span>{{ $branch['address'] ?? 'Find your nearest branch' }}</span>
        </div>
      </div>
      <p><i class="ti ti-map-2"></i>{{ $branch['address'] ?? 'Find your nearest branch' }}</p>
      <div class="button-row">
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $branch['phone'] ?? '') }}" class="btn btn-primary flex-fill"><i class="ti ti-phone"></i>Call</a>
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch['address'] ?? 'Regional Finance') }}" target="_blank" rel="noopener" class="btn btn-outline-primary flex-fill"><i class="ti ti-route"></i>Directions</a>
      </div>
      <a href="{{ route('prototype.support') }}" class="branch-details-link">View branch details<i class="ti ti-arrow-right"></i></a>
    </section>

    <nav class="bottom-nav" aria-label="Mobile app navigation">
      <a class="active" href="{{ route('prototype.index') }}"><i class="ti ti-home"></i><span>Home</span></a>
      <a href="{{ $firstLoan ? route('prototype.loan', $firstLoan['id']) : ($application ? route('prototype.application', $application['id']) : route('prototype.offers')) }}"><i class="ti {{ $firstLoan ? 'ti-wallet' : 'ti-clipboard-list' }}"></i><span>{{ $firstLoan ? (count($loans) > 1 ? 'Loans' : 'Loan') : ($application ? 'Apply' : 'Explore') }}</span></a>
      <a href="{{ route('prototype.offers') }}"><i class="ti ti-sparkles"></i><span>Explore</span></a>
      <a href="{{ route('prototype.wellness') }}"><i class="ti ti-heart-rate-monitor"></i><span>Money Hub</span></a>
    </nav>
  </main>
</div>

@if($modules['show_late_interstitial'])
  <div class="prototype-modal-backdrop" role="presentation"></div>
  <section class="prototype-modal" role="dialog" aria-modal="true" aria-labelledby="late-payment-title">
    <form method="POST" action="{{ route('prototype.interstitial.dismiss') }}">
      @csrf
      <button class="modal-close" type="submit" aria-label="Dismiss late payment message"><i class="ti ti-x"></i></button>
    </form>
    <div class="modal-icon-wrap" aria-hidden="true">
      <i class="ti ti-alert-triangle modal-icon"></i>
    </div>
    <h2 id="late-payment-title">{{ $servicingAlert['modal_title'] ?? 'Your payment is past due' }}</h2>
    <p>{{ $formatServicingText($servicingAlert['modal_body'] ?? '$pastDue was due $dueDate. Making a payment can help bring your account current.') }}</p>
    <a href="{{ $servicingUrl }}" class="btn btn-danger modal-primary-action w-100">{{ $servicingCta }}</a>
    <form method="POST" action="{{ route('prototype.interstitial.dismiss') }}">
      @csrf
      <button class="btn modal-secondary-action w-100 mt-2" type="submit">Not now</button>
    </form>
  </section>
@elseif($modules['show_offer_interstitial'])
  <x-offer-interstitial :offer="$offer" />
@endif
<script type="application/json" data-prototype-state>@json($appState)</script>
@endsection

@section('page-script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('assets/js/prototype-mobile.js') }}?v=20260825scenario-save"></script>
@endsection
