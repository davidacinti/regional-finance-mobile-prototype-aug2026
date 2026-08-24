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
$activeLoanLabel = count($loans) === 0 ? 'No active loans' : (count($loans) > 1 ? count($loans) . ' active loans' : '1 active loan');
$vehicleValue = $vehicle['estimated_value'] ?? 21800;
$vehicleEquity = $vehicle['estimated_equity'] ?? 3900;
$trackedVehicles = $scenario['assets']['vehicles'] ?? [];
if (count($trackedVehicles) === 0) {
    $trackedVehicles = [
        ['year' => 2021, 'make' => 'Toyota', 'model' => 'Camry', 'estimated_value' => 21800, 'estimated_equity' => 3900, 'last_updated' => '2026-07-11'],
        ['year' => 2019, 'make' => 'Ford', 'model' => 'F-150', 'estimated_value' => 26750, 'estimated_equity' => 6450, 'last_updated' => '2026-07-08'],
        ['year' => 2020, 'make' => 'Honda', 'model' => 'CR-V', 'estimated_value' => 23125, 'estimated_equity' => 5125, 'last_updated' => '2026-07-10'],
    ];
}
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
        'url' => route('prototype.offers', ($offer['type'] ?? '') === 'prequalified' ? 'prequalified' : null),
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
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}?v=20260813b">
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
        <div class="top-nav-actions">
          <a class="top-nav-btn" href="{{ route('prototype.chat') }}" aria-label="Open AI chat">
            <i class="ti ti-message-chatbot"></i>
          </a>
          <a class="top-nav-btn notification-action" href="{{ route('prototype.notifications') }}" aria-label="View notifications">
            <i class="ti ti-bell"></i>
            <span></span>
          </a>
        </div>
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
        <a href="{{ route('prototype.notifications') }}"><i class="ti ti-bell"></i><span>Notifications</span></a>
        <a href="{{ route('prototype.support') }}"><i class="ti ti-headset"></i><span>Support</span></a>
      </nav>
      <div class="side-menu-footer">
        <span>Your branch</span>
        <strong>{{ $branch['name'] ?? 'Regional Finance branch' }}</strong>
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $branch['phone'] ?? '') }}"><i class="ti ti-phone"></i>{{ $branch['phone'] ?? '(800) 000-0000' }}</a>
      </div>
    </aside>

    @if(! $modules['show_late_banner'] && $modules['show_application'])
      <section class="message-card application-message-card {{ $application['urgency'] ?? $application['status'] }}">
        <i class="ti {{ ($application['status'] ?? '') === 'approved' ? 'ti-circle-check' : (($application['urgency'] ?? '') === 'urgent' ? 'ti-alert-circle' : 'ti-clipboard-list') }}"></i>
        <div>
          <div class="application-status-row">
            <span class="eyebrow">{{ ($application['status'] ?? '') === 'approved' ? 'Approved' : 'Application status' }}</span>
            <strong>{{ $application['progress_percent'] }}%</strong>
          </div>
          <h2>{{ $application['headline'] ?? $application['current_step'] }}</h2>
          <p>{{ $application['summary'] ?? $application['next_action'] }}</p>
          <div class="progress" aria-label="Application progress">
            <div class="progress-bar" style="width: {{ $application['progress_percent'] }}%"></div>
          </div>
          <div class="application-next-action">
            <span>{{ $application['next_action'] }}</span>
            @if(isset($application['due_by']))
              <small>Due by {{ $date($application['due_by']) }}</small>
            @elseif(isset($application['expires_at']))
              <small>Complete by {{ $date($application['expires_at']) }}</small>
            @endif
          </div>
          <a href="{{ route('prototype.application', $application['id']) }}" class="btn btn-primary w-100 mt-3">{{ $application['cta'] }}</a>
        </div>
      </section>
    @elseif(! $modules['show_late_banner'])
      <section class="highlights-carousel" aria-label="Latest account highlights">
        <div class="highlights-track">
          @foreach($highlightCards as $highlight)
            <article class="highlight-card">
              <div class="highlight-icon"><i class="ti {{ $highlight['icon'] }}"></i></div>
              <div>
                <span class="eyebrow">{{ $highlight['label'] }}</span>
                <h2>{{ $highlight['title'] }}</h2>
                <p>{{ $highlight['body'] }}</p>
              </div>
              <a href="{{ $highlight['url'] }}">{{ $highlight['cta'] }}<i class="ti ti-arrow-right"></i></a>
            </article>
          @endforeach
        </div>
        <div class="carousel-dots" aria-hidden="true">
          @foreach($highlightCards as $highlight)
            <span></span>
          @endforeach
        </div>
      </section>
    @endif

    <aside class="scenario-floater" aria-label="Prototype scenario controls">
      <button type="button" aria-label="Show current prototype scenario">
        <i class="ti ti-flask"></i>
      </button>
      <div class="scenario-floater-panel">
        <span class="eyebrow">Scenario lab</span>
        <strong>{{ $scenario['name'] }}</strong>
        <a href="{{ route('prototype.scenarios') }}"><i class="ti ti-layout-grid"></i>View scenarios</a>
      </div>
    </aside>

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

    @if($modules['show_loans'])
      <section class="section-block">
        <div class="section-title">
          <h2>My accounts</h2>
          @if(count($loans) > 1)
            <a href="{{ route('prototype.wellness') }}">View all accounts</a>
          @endif
        </div>
        <div class="{{ count($loans) > 1 ? 'loan-strip' : '' }}">
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
            @endphp
            <article class="app-card loan-card">
              <div class="card-heading">
                <span class="card-title-with-icon"><i class="ti ti-wallet"></i>{{ $loan['name'] }}</span>
                <span class="status-pill {{ $loan['status'] === 'current' ? 'success' : 'danger' }}">{{ $loan['status'] === 'current' ? 'Current' : ($scenario['default_status']['label'] ?? 'Past due') }}</span>
              </div>
              <div class="balance">{{ $money($loan['balance']) }}</div>
              <p class="muted">Current balance</p>
              <div class="loan-grid">
                <div><i class="ti ti-cash"></i><span>Next payment</span><strong>{{ $money($loan['next_payment_amount']) }}</strong></div>
                <div><i class="ti ti-calendar-due"></i><span>Due date</span><strong>{{ $date($loan['next_payment_date']) }}</strong></div>
                <div><i class="ti ti-refresh"></i><span>AutoPay</span><strong>{{ $loanAutopayEnabled ? 'On' : 'Off' }}</strong></div>
              </div>
              <x-account-alert :tone="$loanAlertTone" :title="$loanAlertTitle" :body="$loanAlertBody" />
              <div class="button-row">
                <a href="{{ route('prototype.payment') }}" class="btn {{ $loan['status'] === 'past_due' || $modules['show_payment_due_banner'] ? 'btn-primary' : 'btn-outline-primary' }} flex-fill"><i class="ti ti-credit-card"></i>Make a payment</a>
                <a href="{{ route('prototype.loan', $loan['id']) }}" class="btn btn-light flex-fill"><i class="ti ti-chevron-right"></i>Details</a>
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    @if($modules['show_offer'])
      <section class="app-card offer-card {{ ($offer['type'] ?? '') === 'prequalified' ? 'featured' : '' }}">
        <div class="offer-heading">
          <div class="offer-icon"><i class="ti ti-sparkles"></i></div>
          <span class="eyebrow">{{ ($offer['type'] ?? '') === 'prequalified' ? 'Personalized offer' : 'Explore options' }}</span>
        </div>
        @if(($offer['type'] ?? '') === 'prequalified')
          <h2>You may be prequalified for an additional loan.</h2>
          <div class="soft-credit-badge"><i class="ti ti-shield-check"></i>Checking will not impact your credit score</div>
          <div class="offer-amount">{{ '$' . number_format($offer['amount']) }}</div>
          <p>Review this personalized option in minutes. Offer expires {{ $date($offer['expires_at']) }}.</p>
          <a href="{{ route('prototype.offers', 'prequalified') }}" class="btn btn-primary w-100">View offer</a>
        @else
          <h2>See loan options in minutes.</h2>
          <div class="soft-credit-badge"><i class="ti ti-shield-check"></i>No impact to your credit score</div>
          <p>Check for available offers with no impact to your credit score.</p>
          <a href="{{ route('prototype.offers') }}" class="btn btn-primary w-100">Check for offers</a>
        @endif
      </section>
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
              <small>Up {{ $wellness['credit_score_change'] }} points</small>
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
        <span class="eyebrow">Vehicle estimate</span>
        <h2>{{ $vehicle['year'] }} {{ $vehicle['make'] }} {{ $vehicle['model'] }}</h2>
        <div class="loan-grid">
          <div><span>Estimated value</span><strong>{{ $money($vehicle['estimated_value']) }}</strong></div>
          <div><span>Estimated equity</span><strong>{{ $money($vehicle['estimated_equity']) }}</strong></div>
        </div>
        <p class="muted">Estimate last updated {{ $date($vehicle['last_updated']) }}. This is not a guaranteed value.</p>
        <a href="{{ route('prototype.assets') }}" class="btn btn-outline-primary w-100">View vehicle details</a>
      </section>
    @endif

    <section class="app-card branch-card">
      <div class="card-heading">
        <span class="eyebrow">Your branch</span>
        <i class="ti ti-map-pin"></i>
      </div>
      <h2>{{ $branch['name'] ?? 'Regional Finance branch' }}</h2>
      <div
        class="branch-map"
        data-branch-map
        data-lat="{{ $branch['lat'] ?? 34.8334 }}"
        data-lng="{{ $branch['lng'] ?? -82.3075 }}"
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
      <div class="branch-detail-list">
        <div>
          <i class="ti ti-map-2"></i>
          <span>Location</span>
          <strong>{{ $branch['address'] ?? 'Find your nearest branch' }}</strong>
        </div>
        <div>
          <i class="ti ti-clock-hour-4"></i>
          <span>Hours</span>
          <strong>{{ $branch['hours'] ?? 'Weekday hours available' }}</strong>
        </div>
        <div>
          <i class="ti ti-phone"></i>
          <span>Phone</span>
          <strong>{{ $branch['phone'] ?? '(800) 000-0000' }}</strong>
        </div>
        <div>
          <i class="ti ti-user-star"></i>
          <span>Branch manager</span>
          <strong>{{ $branch['manager'] ?? 'Branch team' }}</strong>
        </div>
      </div>
      <div class="button-row">
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $branch['phone'] ?? '') }}" class="btn btn-primary flex-fill"><i class="ti ti-phone"></i>Call</a>
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch['address'] ?? 'Regional Finance') }}" target="_blank" rel="noopener" class="btn btn-outline-primary flex-fill"><i class="ti ti-route"></i>Directions</a>
      </div>
      <a href="{{ route('prototype.support') }}" class="btn btn-outline-primary w-100 branch-details-link"><i class="ti ti-building-store"></i>View branch details</a>
    </section>

    <section class="app-card vehicle-teaser-card">
      <div class="card-heading">
        <span class="eyebrow">Track your cars</span>
        <i class="ti ti-car"></i>
      </div>
      <h2>Track the value of your cars</h2>
      <div class="vehicle-carousel" aria-label="Tracked vehicle values">
        @foreach($trackedVehicles as $trackedVehicle)
          <article class="vehicle-value-card">
            <strong>{{ $trackedVehicle['year'] }} {{ $trackedVehicle['make'] }} {{ $trackedVehicle['model'] }}</strong>
            <div class="vehicle-teaser-grid">
              <div><span>Estimated value</span><strong>{{ $money($trackedVehicle['estimated_value']) }}</strong></div>
              <div><span>Estimated equity</span><strong>{{ $money($trackedVehicle['estimated_equity']) }}</strong></div>
            </div>
            <small>Updated {{ $date($trackedVehicle['last_updated']) }}</small>
          </article>
        @endforeach
      </div>
      <p class="muted">Track value trends and equity estimates across your vehicles. Values are estimates, not guarantees.</p>
      <a href="{{ route('prototype.assets') }}" class="btn btn-outline-primary w-100"><i class="ti ti-car"></i>View vehicle details</a>
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
@endif
@endsection

@section('page-script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('assets/js/prototype-mobile.js') }}?v=20260813b"></script>
@endsection
