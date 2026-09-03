@php
$navbarView = 'layouts/sections/navbar/navbar-empty';
$footerView = 'layouts/sections/footer/footer-empty';
$isMenu = false;
$isNavbar = false;
$isFooter = false;
$titles = [
  'loan' => 'Account details',
  'application' => 'Application',
  'offer' => 'Offers',
  'protection' => 'Protection & Benefits',
  'wellness' => 'Financial health',
  'assets' => 'Vehicle details',
  'profile' => 'Profile',
  'documents' => 'Document Center',
  'chat' => 'AI chat',
  'notifications' => 'Notifications',
  'support' => 'Support',
  'settings' => 'Settings',
  'payment' => 'Make a payment',
  'savings' => 'Savings',
  'credit-card' => 'Credit card',
];
$loans = $scenario['loans'] ?? [];
$branch = $scenario['branch'] ?? [];
$wellness = $scenario['financial_wellness'] ?? [];
$offer = $scenario['offer'] ?? [];
$application = $scenario['application'] ?? null;
$firstLoan = $loans[0] ?? null;
$loanNavUrl = $firstLoan ? route('prototype.loan', $firstLoan['id']) : ($application ? route('prototype.application', $application['id']) : route('prototype.offers'));
$loanNavLabel = $firstLoan ? (count($loans) > 1 ? 'Loans' : 'Loan') : ($application ? 'Continue' : 'Get a loan');
$loanNavIcon = $firstLoan ? 'ti-wallet' : ($application ? 'ti-clipboard-list' : 'ti-file-plus');
$loan = collect($loans)->firstWhere('id', (int) $id) ?? ($loans[0] ?? [
  'id' => $id ?? 1002841,
  'name' => 'Personal loan',
  'status' => 'current',
  'balance' => 4825.35,
  'next_payment_amount' => 214.00,
  'next_payment_date' => '2026-07-18',
  'past_due_amount' => 0,
  'autopay_enabled' => true,
]);
$money = fn ($value) => '$' . number_format((float) $value, 2);
$date = fn ($value) => \Carbon\Carbon::parse($value)->format('M j, Y');
$dateLong = fn ($value) => \Carbon\Carbon::parse($value)->format('F j, Y');
$dateInput = fn ($value) => \Carbon\Carbon::parse($value)->format('Y-m-d');
$showLoanSheet = request()->query('sheet') === 'details';
$amountDue = ($loan['past_due_amount'] ?? 0) > 0 ? $loan['past_due_amount'] : $loan['next_payment_amount'];
$paymentDate = now()->toDateString();
$scheduledPayment = $scheduledPayment ?? null;
$paymentStatus = $paymentStatus ?? null;
$currentDocuments = collect($loans)->map(fn ($loanItem, $index) => [
  'loan' => $loanItem,
  'documents' => [
    ['name' => 'Loan agreement', 'type' => 'PDF', 'date' => '2026-01-15', 'status' => 'Available', 'icon' => 'ti-file-text'],
    ['name' => 'Payment schedule', 'type' => 'PDF', 'date' => $loanItem['next_payment_date'] ?? '2026-07-18', 'status' => 'Updated', 'icon' => 'ti-calendar-dollar'],
    ['name' => 'Truth in Lending disclosure', 'type' => 'PDF', 'date' => '2026-01-15', 'status' => 'Available', 'icon' => 'ti-file-certificate'],
  ],
])->values()->all();
$pastLoanDocuments = [
  [
    'loan' => ['id' => 882104, 'name' => 'Personal loan', 'status' => 'paid_off', 'balance' => 0, 'closed_date' => '2024-11-22'],
    'documents' => [
      ['name' => 'Paid in full letter', 'type' => 'PDF', 'date' => '2024-11-22', 'status' => 'Archived', 'icon' => 'ti-circle-check'],
      ['name' => 'Final statement', 'type' => 'PDF', 'date' => '2024-11-22', 'status' => 'Archived', 'icon' => 'ti-receipt'],
    ],
  ],
  [
    'loan' => ['id' => 739518, 'name' => 'Auto-secured loan', 'status' => 'closed', 'balance' => 0, 'closed_date' => '2023-08-10'],
    'documents' => [
      ['name' => 'Lien release', 'type' => 'PDF', 'date' => '2023-08-10', 'status' => 'Archived', 'icon' => 'ti-car'],
      ['name' => 'Loan agreement', 'type' => 'PDF', 'date' => '2021-08-10', 'status' => 'Archived', 'icon' => 'ti-file-text'],
    ],
  ],
];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', $titles[$type] ?? 'Prototype Detail')

@section('page-style')
@if($type === 'support')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endif
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}?v=20260903locked-contact-layout">
@endsection

@section('content')
<div class="prototype-shell" data-prototype-theme="light">
  <main class="phone-frame detail-frame" aria-label="{{ $titles[$type] ?? 'Prototype detail' }}">
    <header class="detail-topbar">
      <nav class="top-navbar" aria-label="App actions">
        <a class="top-nav-btn" href="{{ route('prototype.index') }}" aria-label="Go back" data-back-button><i class="ti ti-arrow-left"></i></a>
        <a class="top-logo-link" href="{{ route('prototype.index') }}" aria-label="Regional Finance home">
          <img class="regional-logo" src="{{ asset('assets/img/branding/regionals-logo.svg') }}" alt="Regional Finance">
        </a>
      </nav>
    </header>

    @if($type === 'loan')
      @php
        $loanHasLatePayment = $scenario['alerts']['late_payment'] ?? false;
        $loanShowDueSoon = ($scenario['alerts']['payment_due_soon'] ?? false) && ! $loanHasLatePayment;
        $loanServicingAlert = $scenario['servicing_alert'] ?? [];
        $loanFormatServicingText = function (?string $text) use ($loan, $money, $date) {
          return strtr($text ?? '', [
            '$pastDue' => $money($loan['past_due_amount'] ?? 0),
            '$dueDate' => $date($loan['next_payment_date'] ?? now()),
          ]);
        };
        if ($loan['status'] !== 'current') {
          $loanAlertTone = 'urgent';
          $loanAlertTitle = $loanServicingAlert['title'] ?? 'Payment past due';
          $loanAlertBody = $loanFormatServicingText($loanServicingAlert['body'] ?? '$pastDue was due $dueDate.');
        } elseif ($loanShowDueSoon) {
          $loanAlertTone = 'warning';
          $loanAlertTitle = 'Payment due soon';
          $loanAlertBody = $money($loan['next_payment_amount']) . ' is due ' . $date($loan['next_payment_date']) . '.';
        } else {
          $loanAlertTone = 'success';
          $loanAlertTitle = "You're all caught up";
          $loanAlertBody = '$0 is due until your next statement cycle.';
        }
        $amountDue = ($loan['past_due_amount'] ?? 0) > 0 ? $loan['past_due_amount'] : $loan['next_payment_amount'];
        $originalPrincipal = max(($loan['balance'] ?? 0) + 1100, 16000);
        $paidOffPercent = min(100, max(0, round((($originalPrincipal - ($loan['balance'] ?? 0)) / $originalPrincipal) * 100)));
        $payoffAmount = ($loan['balance'] ?? 0) + 42.18;
        $loanPendingPayment = ($scheduledPayment['loan_id'] ?? null) === ($loan['id'] ?? null) ? $scheduledPayment : null;
        $activityRows = [
          ['date' => '2026-07-21', 'title' => 'One-time payment', 'source' => 'From Primary Checking - 4203', 'amount' => $loan['next_payment_amount'], 'balance' => $loan['balance']],
          ['date' => '2026-06-17', 'title' => 'One-time payment', 'source' => 'From Primary Checking - 4203', 'amount' => $loan['next_payment_amount'], 'balance' => ($loan['balance'] ?? 0) + 469.10],
          ['date' => '2026-05-28', 'title' => 'One-time payment', 'source' => 'From Primary Checking - 4203', 'amount' => $loan['next_payment_amount'], 'balance' => ($loan['balance'] ?? 0) + 948.70],
          ['date' => '2026-04-30', 'title' => 'One-time payment', 'source' => 'From Primary Checking - 4203', 'amount' => 200, 'balance' => ($loan['balance'] ?? 0) + 1389.76],
        ];
      @endphp

      <section class="loan-hero-summary">
        <span class="eyebrow">Loan #{{ $loan['id'] ?? '1002841' }}</span>
        <h1>{{ $loan['name'] ?? 'Personal loan' }}</h1>
        <div class="loan-hero-meta">
          <span><strong>{{ $money($loan['balance']) }}</strong> remaining principal</span>
          <span><strong>24.90%</strong> APR</span>
        </div>
        <div class="loan-status-pills">
          <span class="status-pill {{ $loan['status'] === 'current' ? 'success' : 'danger' }}">{{ $loan['status'] === 'current' ? 'Current' : ($scenario['default_status']['label'] ?? 'Past due') }}</span>
          <span class="status-pill autopay-pill {{ $loan['autopay_enabled'] ? 'success' : 'neutral' }}"><i class="ti ti-refresh"></i>AutoPay {{ $loan['autopay_enabled'] ? 'On' : 'Off' }}</span>
          @if($loanPendingPayment)<span class="status-pill pending"><i class="ti ti-clock-check"></i>Payment pending</span>@endif
        </div>
      </section>

      <section class="app-card loan-payment-card">
        <span class="repayment-pill">{{ $loan['status'] === 'current' ? 'In repayment' : ($scenario['default_status']['label'] ?? 'Past due') }}</span>
        <div class="loan-payment-amount">{{ $money($amountDue) }}</div>
        <p>Due {{ $dateLong($loan['next_payment_date']) }}</p>
        <div class="loan-payment-divider"></div>
        <div class="autopay-row">
          <strong>AutoPay {{ $loan['autopay_enabled'] ? 'on' : 'off' }}</strong>
          <a href="{{ route('prototype.payment') }}">{{ $loan['autopay_enabled'] ? 'Manage' : 'Set up' }}</a>
        </div>
        @if($loanPendingPayment)
          <a href="{{ route('prototype.payment') }}" class="loan-pending-summary">
            <i class="ti ti-clock-check"></i>
            <span><small>Pending payment</small><strong>{{ $money($loanPendingPayment['amount']) }} on {{ $date($loanPendingPayment['payment_date']) }}</strong></span>
            <i class="ti ti-chevron-right"></i>
          </a>
        @endif
        <x-account-alert :tone="$loanAlertTone" :title="$loanAlertTitle" :body="$loanAlertBody" />
        <a href="{{ route('prototype.payment') }}" class="btn btn-primary w-100"><i class="ti {{ $loanPendingPayment ? 'ti-clock-check' : 'ti-credit-card' }}"></i>{{ $loanPendingPayment ? 'View pending payment' : 'Make a payment' }}</a>
      </section>

      @if($application)
        <section class="loan-detail-section">
          <article class="app-card loan-context-action">
            <div><span class="eyebrow">Application in progress</span><h3>{{ $application['headline'] }}</h3></div>
            <a href="{{ route('prototype.application', $application['id']) }}">{{ $application['cta'] }}<i class="ti ti-arrow-right"></i></a>
          </article>
        </section>
      @elseif(($offer['status'] ?? null) === 'available')
        <section class="loan-detail-section">
          <article class="app-card loan-context-action">
            <div><span class="eyebrow">{{ $offer['eyebrow'] }}</span><h3>{{ $offer['headline'] }}</h3></div>
            <form method="POST" action="{{ route('prototype.application.start') }}">
              @csrf
              <button type="submit">{{ $offer['cta'] }}<i class="ti ti-arrow-right"></i></button>
            </form>
          </article>
        </section>
      @endif

      <section class="loan-detail-section">
        <div class="loan-shortcut-grid">
          <a href="{{ route('prototype.loan', ['loan' => $loan['id'], 'sheet' => 'details']) }}"><i class="ti ti-rosette-discount-check"></i><span>Loan details</span></a>
          <a href="{{ route('prototype.documents') }}"><i class="ti ti-file-text"></i><span>Documents</span></a>
        </div>
      </section>

      <section class="loan-detail-section">
        <h2>Progress</h2>
        <article class="app-card loan-progress-card">
          <div class="loan-progress-top">
            <strong>{{ $paidOffPercent }}% paid off</strong>
            <strong>{{ $money($originalPrincipal) }}</strong>
          </div>
          <div class="loan-progress-bar" aria-label="{{ $paidOffPercent }} percent paid off">
            <span style="width: {{ $paidOffPercent }}%"></span>
          </div>
          <p>This is based on the percentage of principal paid off.</p>
        </article>

        <article class="app-card loan-facts-card">
          <dl>
            <div><dt>Remaining principal</dt><dd>{{ $money($loan['balance']) }}</dd></div>
            <div><dt>Accrued interest</dt><dd>{{ $money(42.18) }}</dd></div>
            <div class="loan-facts-divider"></div>
            <div><dt>Origination date</dt><dd>Jan 15, 2024</dd></div>
            <div><dt>Maturity date</dt><dd>Jan 15, 2027</dd></div>
          </dl>
          <p>This includes principal and interest as of today. To pay off your loan early, <a href="{{ route('prototype.support') }}">contact your branch</a>.</p>
        </article>
      </section>

      <section class="loan-detail-section">
        <h2>Activity</h2>
        <article class="app-card loan-activity-card" data-loan-activity>
          <div class="activity-tabs" role="tablist" aria-label="Payment activity">
            <button type="button" class="active" data-activity-tab="recent">Recent</button>
            <button type="button" data-activity-tab="scheduled">Scheduled</button>
          </div>
          <div class="activity-list" data-activity-panel="recent">
            @foreach($activityRows as $activity)
              <div class="activity-date">{{ strtoupper(\Carbon\Carbon::parse($activity['date'])->format('F j, Y')) }}</div>
              <div class="activity-row">
                <div>
                  <strong>{{ $activity['title'] }}</strong>
                  <span>{{ $activity['source'] }}</span>
                </div>
                <div>
                  <strong class="activity-amount">-{{ $money($activity['amount']) }}</strong>
                  <span>{{ $money($activity['balance']) }}</span>
                </div>
                <i class="ti ti-chevron-down"></i>
              </div>
            @endforeach
          </div>
          <div class="scheduled-empty" data-activity-panel="scheduled" hidden>
            @if($loanPendingPayment)
              <i class="ti ti-clock-check"></i>
              <strong>{{ $money($loanPendingPayment['amount']) }} payment pending</strong>
              <span>Scheduled for {{ $date($loanPendingPayment['payment_date']) }} from {{ $loanPendingPayment['account']['label'] }}.</span>
              <a href="{{ route('prototype.payment') }}" class="btn btn-outline-primary btn-sm">View payment</a>
            @else
              <i class="ti ti-calendar-dollar"></i>
              <strong>{{ $loan['autopay_enabled'] ? 'Next AutoPay is scheduled' : 'No scheduled payments yet' }}</strong>
              <span>{{ $loan['autopay_enabled'] ? $money($loan['next_payment_amount']) . ' on ' . $date($loan['next_payment_date']) : 'Scheduled payments will show here once they are set up.' }}</span>
            @endif
          </div>
        </article>
      </section>

      <section class="loan-detail-section">
        <h2>More</h2>
        <div class="loan-more-list">
          <a href="{{ route('prototype.support') }}"><i class="ti ti-calendar-dollar"></i><span>Estimate early payoff</span><i class="ti ti-chevron-right"></i></a>
          <a href="{{ route('prototype.support') }}"><i class="ti ti-help-square-rounded"></i><span>Get help</span><strong>{{ $branch['phone'] ?? '(864) 555-0148' }}</strong></a>
        </div>
      </section>

      @if($showLoanSheet)
        <section class="loan-sheet-backdrop" aria-label="Loan details panel">
          <div class="loan-sheet">
            <div class="sheet-heading">
              <h2>Loan Details</h2>
              <a href="{{ route('prototype.loan', $loan['id']) }}" aria-label="Close loan details"><i class="ti ti-x"></i></a>
            </div>
            <dl class="loan-detail-list">
              <div><dt>Loan Number</dt><dd>#{{ str_pad((string) $loan['id'], 12, '*', STR_PAD_LEFT) }}</dd></div>
              <div><dt>Loan Status</dt><dd>{{ $loan['status'] === 'past_due' ? 'Past due' : 'Active' }}</dd></div>
              <div><dt>Account Balance</dt><dd>{{ $money($loan['balance']) }}</dd></div>
              <div><dt>Loan Open Date</dt><dd>1/15/2024</dd></div>
              <div><dt>Original Principal Amount</dt><dd>{{ $money(($loan['balance'] ?? 0) + 1100) }}</dd></div>
              <div><dt>Term</dt><dd>36 months</dd></div>
              <div><dt>APR</dt><dd>24.9%</dd></div>
              <div><dt>Maturity Date</dt><dd>1/15/2027</dd></div>
              <div><dt>Monthly Payment</dt><dd>{{ $money($loan['next_payment_amount']) }}</dd></div>
              <div><dt>Payoff Amount</dt><dd>{{ $money(($loan['balance'] ?? 0) + 42.18) }}</dd></div>
              <div><dt>Last Payment Date</dt><dd>-</dd></div>
              <div><dt>Last Payment Amount</dt><dd>-</dd></div>
            </dl>
            <div class="payoff-note">
              <i class="ti ti-info-circle"></i>
              <div>
                <strong>Payoff Instructions</strong>
                <p>Payoffs cannot be processed online. Please contact your local branch if you would like to inquire about your payoff amount or pay off your loan.</p>
                <p>Please call us at (**) **-*****</p>
              </div>
            </div>
          </div>
        </section>
      @endif
    @else
      <section class="app-card detail-card {{ $type === 'payment' ? 'payment-detail-shell' : '' }} {{ $type === 'chat' ? 'chat-detail-shell' : '' }}">
        @if(! in_array($type, ['payment', 'notifications', 'chat', 'support'], true))
          <h2 class="detail-page-title">{{ $titles[$type] ?? 'Details' }}</h2>
        @endif
        @switch($type)
        @case('savings')
          <section class="product-experience-page savings-product">
            <span class="account-product-icon"><i class="ti ti-pig-money"></i></span>
            <span class="eyebrow">Product experience</span>
            <h1>Regional Savings</h1>
            <p>This would be the full savings account experience.</p>
          </section>
          @break
        @case('credit-card')
          <section class="product-experience-page card-product">
            <span class="account-product-icon"><i class="ti ti-credit-card"></i></span>
            <span class="eyebrow">Product experience</span>
            <h1>Regional Credit Card</h1>
            <p>This would be the full credit card account experience.</p>
          </section>
          @break
        @case('payment')
          <section
            class="payment-flow"
            data-payment-flow
            data-minimum-due="{{ number_format((float) $amountDue, 2, '.', '') }}"
            data-payment-status="{{ $paymentStatus }}"
            data-scheduled-amount="{{ $scheduledPayment ? $money($scheduledPayment['amount']) : '' }}"
            data-scheduled-date="{{ $scheduledPayment ? $dateLong($scheduledPayment['payment_date']) : '' }}"
            data-home-url="{{ route('prototype.index') }}"
            data-payment-url="{{ route('prototype.payment') }}"
          >
            <div class="payment-hero">
              <span class="eyebrow">Personal loan - {{ substr((string) ($loan['id'] ?? '5831'), -4) }}</span>
              <h2>{{ $money($amountDue) }} due {{ $dateLong($loan['next_payment_date']) }}</h2>
            </div>

            @if($scheduledPayment)
              <article class="pending-payment-card">
                <div class="pending-payment-icon"><i class="ti ti-clock-check"></i></div>
                <div>
                  <span class="eyebrow">Pending payment</span>
                  <h3>{{ $money($scheduledPayment['amount']) }} scheduled</h3>
                  <p>Payment date {{ $dateLong($scheduledPayment['payment_date']) }} from {{ $scheduledPayment['account']['label'] }}.</p>
                </div>
                <dl class="payment-summary-list">
                  <div><dt>Status</dt><dd>{{ $scheduledPayment['status'] }}</dd></div>
                  <div><dt>Confirmation</dt><dd>{{ $scheduledPayment['id'] }}</dd></div>
                  <div><dt>Minimum due</dt><dd>{{ $money($scheduledPayment['minimum_due']) }}</dd></div>
                </dl>
                @if($scheduledPayment['warning'])
                  <div class="payment-warning"><i class="ti ti-alert-circle"></i>{{ $scheduledPayment['warning'] }}</div>
                @endif
                <div class="button-row payment-actions">
                  <a class="btn btn-outline-primary flex-fill" href="{{ route('prototype.loan', $scheduledPayment['loan_id']) }}"><i class="ti ti-receipt"></i>View loan</a>
                  <form method="POST" action="{{ route('prototype.payment.cancel') }}" class="flex-fill" data-cancel-payment>
                    @csrf
                    <button class="btn btn-light w-100" type="submit"><i class="ti ti-x"></i>Cancel payment</button>
                  </form>
                </div>
              </article>
            @else
              <form method="POST" action="{{ route('prototype.payment.schedule') }}" class="payment-form">
                @csrf
                <article class="payment-entry-card">
                  <label class="payment-amount-field" for="payment-amount">
                    <input id="payment-amount" name="amount" type="number" min="0.01" step="0.01" value="{{ number_format((float) $amountDue, 2, '.', '') }}" inputmode="decimal" required>
                    <span>Enter an amount</span>
                  </label>

                  <div class="payment-inline-warning" data-minimum-warning hidden>
                    <i class="ti ti-alert-circle"></i>
                    <span>This will not satisfy the minimum payment due. You may still schedule it, but the account may remain past due.</span>
                  </div>

                  <label class="payment-select-row" for="payment-date">
                    <span>Payment date</span>
                    <input id="payment-date" name="payment_date" type="date" value="{{ $paymentDate }}" required>
                  </label>

                  <fieldset class="payment-account-fieldset">
                    <legend>Pay from</legend>
                    <input type="hidden" name="account_mode" value="saved" data-account-mode>
                    <input type="hidden" name="saved_account" value="Primary Checking - 4203" data-saved-account>
                    <button class="bank-dropdown-toggle" type="button" data-bank-dropdown-toggle aria-expanded="false">
                      <i class="ti ti-building-bank"></i>
                      <span>
                        <strong data-selected-bank-name>Primary Checking &bull; 4203</strong>
                        <small data-selected-bank-meta>Checking account</small>
                      </span>
                      <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="bank-dropdown-menu" data-bank-dropdown-menu hidden>
                      <button type="button" data-account-option data-account-label="Primary Checking - 4203" data-account-name="Primary Checking &bull; 4203" data-account-meta="Checking account">
                        <strong>Primary Checking &bull; 4203</strong>
                        <small>Checking account</small>
                      </button>
                      <button type="button" data-account-option data-account-label="Savings - 1187" data-account-name="Savings &bull; 1187" data-account-meta="Savings account">
                        <strong>Savings &bull; 1187</strong>
                        <small>Savings account</small>
                      </button>
                    </div>
                    <button class="add-bank-toggle" type="button" data-add-bank-toggle><i class="ti ti-plus"></i>Add a new bank account</button>
                  </fieldset>

                  <section class="add-bank-panel" data-add-bank-panel hidden>
                    <label>Account name<input class="form-control" name="new_account_name" type="text" placeholder="Primary checking"></label>
                    <label>Routing number<input class="form-control" name="routing_number" type="text" inputmode="numeric" maxlength="9" placeholder="123456789"></label>
                    <label>Account number<input class="form-control" name="new_account_number" type="text" inputmode="numeric" placeholder="Account number"></label>
                    <label>Confirm account number<input class="form-control" name="confirm_account_number" type="text" inputmode="numeric" placeholder="Confirm account number"></label>
                  </section>
                </article>

                <section class="payment-review-card" data-payment-review hidden>
                  <span class="eyebrow">Review payment</span>
                  <dl class="payment-summary-list">
                    <div><dt>Amount</dt><dd data-review-amount>{{ $money($amountDue) }}</dd></div>
                    <div><dt>Date</dt><dd data-review-date>{{ $dateLong($paymentDate) }}</dd></div>
                    <div><dt>Pay from</dt><dd data-review-account>Primary Checking &bull; 4203</dd></div>
                  </dl>
                  <p data-review-warning hidden>This amount is below the minimum payment due.</p>
                </section>

                <button class="btn btn-primary w-100 payment-review-btn" type="button" data-review-payment>Review</button>
                <button class="btn btn-primary w-100 payment-submit-btn" type="submit" data-submit-payment hidden>Schedule payment</button>
              </form>
            @endif
          </section>
          @break
        @case('application')
          @php
            $application = $scenario['application'] ?? [];
            $step = $application['step'] ?? 'application_started';
            $applicationIcon = match($step) {
              'verify_identity' => 'ti-id',
              'credit_eligibility' => 'ti-shield-check',
              'verify_income' => 'ti-file-dollar',
              'review_options' => 'ti-list-details',
              'funding_destination' => 'ti-building-bank',
              'sign_documents' => 'ti-signature',
              'complete' => 'ti-circle-check',
              default => 'ti-clipboard-list',
            };
          @endphp
          <section class="application-detail-flow">
            <div class="application-phase-row">
              <span>Step {{ $application['phase'] ?? 1 }} of {{ $application['phase_count'] ?? 5 }}</span>
              <strong>{{ $application['phase_label'] ?? 'About you' }}</strong>
            </div>
            <div class="progress application-progress" aria-label="Application progress"><div class="progress-bar" style="width: {{ $application['progress_percent'] ?? 10 }}%"></div></div>

            <div class="application-step-hero">
              <i class="ti {{ $applicationIcon }}"></i>
              <span class="eyebrow">{{ $application['technical_step'] ?? 'Application started' }}</span>
              <h2>{{ $application['headline'] ?? 'Continue your application' }}</h2>
              <p>{{ $application['summary'] ?? 'Continue where you left off.' }}</p>
            </div>

            <article class="app-card application-step-card">
              @switch($step)
                @case('application_started')
                  <div class="application-marketing-card {{ ($application['prequalified'] ?? false) ? 'is-prequalified' : '' }}">
                    <div class="application-marketing-amount"><span>Personal loans up to</span><strong>$25,000</strong></div>
                    <p>{{ ($application['prequalified'] ?? false) ? 'You already have a pre-qualified path. Confirm your information to continue.' : 'A simple application, clear options, and support from your local branch.' }}</p>
                    <div class="application-benefits"><span><i class="ti ti-clock"></i>Fast guided process</span><span><i class="ti ti-shield-check"></i>{{ ($application['prequalified'] ?? false) ? 'Pre-qualified' : 'Soft check to start' }}</span></div>
                  </div>
                  @break
                @case('confirm_information')
                  <div class="application-info-fields">
                    <div class="application-locked-field">
                      <div><span>Full name</span><strong>{{ $scenario['customer']['first_name'] }} {{ $scenario['customer']['last_name'] ?? '' }}</strong></div>
                      <div><span>Mobile phone</span><strong>(864) 555-2194</strong></div>
                      <p><i class="ti ti-lock"></i><span>To update either, call <a href="tel:8645550148">Greenville Branch</a>.</span></p>
                    </div>
                    <label><span>Home address</span><input class="form-control" type="text" name="address" value="1450 Woodruff Rd, Greenville, SC 29607" form="application-advance-form"></label>
                    <label><span>Current employer</span><input class="form-control" type="text" name="employer" value="Carolina Logistics" form="application-advance-form"></label>
                    <label><span>Monthly net income</span><input class="form-control" type="text" inputmode="decimal" name="income" value="$4,850" form="application-advance-form"></label>
                  </div>
                  @if($application['prequalified'] ?? false)
                    <div class="application-trust-row soft-pull"><i class="ti ti-shield-check"></i><div><strong>Checking your rates will not impact your credit score</strong><span>We'll use a soft credit inquiry to show your available loan options.</span></div></div>
                  @endif
                  @break
                @case('credit_eligibility')
                  @if($application['prequalified'] ?? false)
                    <div class="application-trust-row hard-pull"><i class="ti ti-file-search"></i><div><strong>Credit application</strong><span>Continuing authorizes a hard credit inquiry, which may affect your credit score.</span></div></div>
                    <label class="application-consent"><input type="checkbox" checked><span>I authorize Regional Finance to obtain my credit report to complete this application.</span></label>
                  @else
                    <div class="application-trust-row"><i class="ti ti-shield-check"></i><div><strong>Pre-qualify with no score impact</strong><span>This first eligibility check uses a soft inquiry and will not affect your credit score.</span></div></div>
                    <label class="application-consent"><input type="checkbox" checked><span>I authorize Regional Finance to check whether I pre-qualify.</span></label>
                  @endif
                  @break
                @case('review_options')
                  <div class="loan-options-intro"><i class="ti ti-shield-check"></i><span><strong>Your rate check is complete</strong>These options were found with no impact to your credit score.</span></div>
                  <label class="loan-option-choice selected"><input type="radio" name="loan-option" value="3500" checked form="application-advance-form"><span><small>Loan amount</small><strong>$3,500</strong><em>$168 monthly &bull; 24 months</em></span><span class="loan-option-rate"><small>APR</small><strong>24.90%</strong></span><b>Recommended</b></label>
                  <label class="loan-option-choice"><input type="radio" name="loan-option" value="2500" form="application-advance-form"><span><small>Loan amount</small><strong>$2,500</strong><em>$126 monthly &bull; 24 months</em></span><span class="loan-option-rate"><small>APR</small><strong>24.90%</strong></span></label>
                  <div class="application-option-note"><i class="ti ti-info-circle"></i>Selecting an option does not send funds. A hard credit inquiry is required before booking.</div>
                  @break
                @case('verify_income')
                  <div class="application-choice selected"><i class="ti ti-building-bank"></i><div><strong>Connect your bank</strong><span>Securely verify recurring income.</span></div><i class="ti ti-check"></i></div>
                  <div class="application-choice"><i class="ti ti-file-upload"></i><div><strong>Upload a pay stub</strong><span>PDF, JPG, or PNG accepted.</span></div><i class="ti ti-chevron-right"></i></div>
                  @break
                @case('funding_destination')
                  <label class="funding-account-choice selected"><input type="radio" name="funding-account" checked><i class="ti ti-building-bank"></i><span><strong>Primary Checking &bull; 4203</strong><small>Funds arrive in 1-2 business days</small></span><i class="ti ti-circle-check-filled"></i></label>
                  <button class="application-add-account" type="button"><i class="ti ti-plus"></i>Add another account</button>
                  @break
                @case('sign_documents')
                  <div class="application-choice"><i class="ti ti-file-text"></i><div><strong>Loan agreement</strong><span>Ready to review and sign</span></div><i class="ti ti-chevron-right"></i></div>
                  <div class="application-choice"><i class="ti ti-file-certificate"></i><div><strong>Truth in Lending disclosure</strong><span>Ready to review</span></div><i class="ti ti-chevron-right"></i></div>
                  <label class="application-consent"><input type="checkbox" checked><span>I agree to use my electronic signature for these documents.</span></label>
                  @break
                @case('complete')
                  <div class="application-complete"><i class="ti ti-circle-check"></i><strong>Approved for $3,500</strong><span>Your signed documents are complete. Funding is now pending to Checking &bull; 4203.</span></div>
                  <div class="funding-status"><span></span><div><strong>Funding pending</strong><small>Expected within one business day</small></div></div>
                  @break
                @default
                  <div class="application-trust-row"><i class="ti ti-lock"></i><div><strong>Your progress is saved</strong><span>You can leave at any time and continue from this exact step.</span></div></div>
              @endswitch
            </article>

            <form id="application-advance-form" method="POST" action="{{ route('prototype.application.advance', $application['id'] ?? 62001) }}">
              @csrf
              <button class="btn btn-primary w-100" type="submit">{{ $application['cta'] ?? 'Continue' }}</button>
            </form>
            <div class="application-secondary-actions">
              @if($step !== 'application_started' && $step !== 'complete')
                <form method="POST" action="{{ route('prototype.application.previous', $application['id'] ?? 62001) }}">@csrf<button type="submit"><i class="ti ti-arrow-left"></i>Back</button></form>
              @endif
              @if($step !== 'complete')
                <a href="{{ route('prototype.index') }}">Save and finish later</a>
              @endif
            </div>
          </section>
          @break
        @case('offer')
          <section class="offers-page">
            <header class="marketplace-heading"><h2>Explore</h2><p>Products and options picked for you.</p></header>

            @if($application)
              <article class="offer-product-card featured application-offer-card">
                <div class="offer-product-icon"><i class="ti ti-clipboard-list"></i></div>
                <div><span class="eyebrow">Application in progress</span><h3>{{ $application['headline'] }}</h3><p>{{ $application['summary'] }}</p></div>
                <a class="btn btn-primary w-100" href="{{ route('prototype.application', $application['id']) }}">{{ $application['cta'] }}</a>
              </article>
            @elseif(($offer['status'] ?? null) === 'available')
              <article class="offer-product-card featured {{ $offer['type'] }}">
                <div class="offer-product-icon"><i class="ti {{ $offer['type'] === 'prequalified_renewal' ? 'ti-award' : 'ti-sparkles' }}"></i></div>
                <div><span class="eyebrow">{{ $offer['eyebrow'] }}</span><h3>{{ $offer['headline'] }}</h3><p>{{ $offer['body'] }}</p></div>
                <div class="soft-credit-callout compact"><i class="ti ti-shield-check"></i><span>{{ $offer['highlight'] }}</span></div>
                <form class="offer-check-form" method="POST" action="{{ route('prototype.application.start') }}">@csrf<button class="btn btn-primary w-100" type="submit">{{ $offer['cta'] }}</button></form>
              </article>
            @endif

            <section class="marketplace-group">
              <h3>Borrow</h3>
              <div class="marketplace-list">
                <article><i class="ti ti-cash-banknote"></i><div><strong>Personal loans</strong><span>Funds for planned and unexpected expenses.</span></div><form method="POST" action="{{ route('prototype.application.start') }}">@csrf<button type="submit" aria-label="Check personal loan options"><i class="ti ti-chevron-right"></i></button></form></article>
              </div>
            </section>

            <section class="marketplace-group">
              <h3>Protection & benefits</h3>
              <div class="marketplace-list">
                <a href="{{ route('prototype.protection') }}"><i class="ti ti-shield-check"></i><div><strong>Protect your loan</strong><span>Optional help when life changes.</span></div><i class="ti ti-chevron-right"></i></a>
                <a href="{{ route('prototype.protection') }}#home-auto"><i class="ti ti-home-shield"></i><div><strong>Home & Auto Benefits</strong><span>Roadside, deductible, and everyday benefits.</span></div><i class="ti ti-chevron-right"></i></a>
                <a href="{{ route('prototype.protection') }}#auto-plus"><i class="ti ti-car-crash"></i><div><strong>Auto Plus</strong><span>Benefits for unexpected auto expenses.</span></div><i class="ti ti-chevron-right"></i></a>
              </div>
            </section>
          </section>
          @break
        @case('protection')
          <section class="protection-page">
            <header class="protection-heading">
              <div class="protection-icon"><i class="ti ti-shield-check"></i></div>
              <span class="eyebrow">Protection & benefits</span>
              <h2>Extra protection for life's unexpected moments.</h2>
              <p>Optional products and memberships designed to help you stay on track.</p>
            </header>

            <div class="optional-product-note"><i class="ti ti-info-circle"></i><span>These products are optional and are not required for loan approval.</span></div>

            <article class="app-card protection-feature-card">
              <div><i class="ti ti-umbrella"></i><span class="eyebrow">Payment protection</span></div>
              <h3>Help protect your loan payments</h3>
              <p>Coverage options may help when unexpected events affect your ability to pay.</p>
              <div class="benefit-chip-row"><span>Job loss</span><span>Disability</span><span>Life</span><span>Property</span></div>
              <button class="btn btn-primary w-100" type="button">Explore protection</button>
            </article>

            <section class="protection-product-list">
              <article id="home-auto"><i class="ti ti-home-shield"></i><div><h3>Home & Auto</h3><p>Roadside assistance, deductible reimbursement, and locksmith services.</p><button type="button">See all benefits<i class="ti ti-arrow-right"></i></button></div></article>
              <article><i class="ti ti-heart-handshake"></i><div><h3>Silver Safeguard</h3><p>Telemedicine, identity protection, and lifestyle discounts.</p><button type="button">See all benefits<i class="ti ti-arrow-right"></i></button></div></article>
              <article id="auto-plus"><i class="ti ti-car"></i><div><h3>Auto Plus</h3><p>Roadside, repair reimbursement, and auto deductible benefits.</p><button type="button">See all benefits<i class="ti ti-arrow-right"></i></button></div></article>
            </section>
          </section>
          @break
        @case('wellness')
          @php
            $creditAvailable = (bool) ($wellness['credit_monitoring_enabled'] ?? false);
            $bankConnected = (bool) ($wellness['bank_connected'] ?? false);
            $scoreChange = (int) ($wellness['credit_score_change'] ?? 0);
            $budgetCategories = [
              ['label' => 'Housing', 'spent' => 1180, 'budget' => 1250, 'icon' => 'ti-home'],
              ['label' => 'Groceries', 'spent' => ($wellness['budget_warning'] ?? false) ? 485 : 426, 'budget' => 500, 'icon' => 'ti-shopping-cart'],
              ['label' => 'Transportation', 'spent' => 318, 'budget' => 360, 'icon' => 'ti-car'],
            ];
            $cashFlowItems = [
              ['label' => 'Income tracked', 'value' => $money(4120.00)],
              ['label' => 'Bills upcoming', 'value' => $money(986.40)],
              ['label' => 'Projected cushion', 'value' => $money($wellness['cash_flow_cushion'] ?? 288.35)],
            ];
          @endphp
          <section class="wellness-page">
            <div class="wellness-hero">
              <div>
                <span class="eyebrow">Money Hub</span>
                <h2>Financial snapshot</h2>
                <p>{{ ($creditAvailable || $bankConnected) ? 'Your credit, spending, and cash-flow outlook.' : 'Your snapshot will build as information becomes available.' }}</p>
              </div>
              <div class="wellness-score-ring {{ $creditAvailable ? '' : 'is-unavailable' }}">
                @if($creditAvailable)
                  <span>{{ $wellness['credit_score'] }}</span>
                  <small>{{ $scoreChange >= 0 ? '+' : '' }}{{ $scoreChange }} pts</small>
                @else
                  <i class="ti ti-chart-dots"></i>
                  <small>No score yet</small>
                @endif
              </div>
            </div>

            <div class="wellness-status-row">
              <div>
                <i class="ti ti-chart-line"></i>
                <span>Credit monitoring</span>
                <strong>{{ $creditAvailable ? 'Available' : 'Not available' }}</strong>
              </div>
              <div>
                <i class="ti ti-plug-connected"></i>
                <span>Plaid connection</span>
                <strong>{{ $bankConnected ? 'Connected' : 'Not connected' }}</strong>
              </div>
            </div>

            <div class="financial-snapshot-grid">
              <div><span>Credit score</span><strong>{{ $creditAvailable ? $wellness['credit_score'] : '--' }}</strong><small>{{ $creditAvailable ? (($scoreChange > 0 ? '+' : '') . $scoreChange . ' pts') : 'Not available yet' }}</small></div>
              <div><span>Monthly spending</span><strong>{{ $bankConnected ? $money($wellness['monthly_spending'] ?? 0) : '--' }}</strong><small>{{ $bankConnected ? ($wellness['spending_trend'] === 'up' ? 'Higher' : ($wellness['spending_trend'] === 'down' ? 'Improving' : 'Typical')) : 'Connect account' }}</small></div>
              <div><span>Cash-flow outlook</span><strong>{{ $bankConnected ? $money($wellness['cash_flow_cushion'] ?? 0) : '--' }}</strong><small>{{ $wellness['cash_flow_status'] ?? 'Not connected' }}</small></div>
            </div>

            <section class="wellness-insights-section">
              <div class="section-title"><h2>Insights</h2></div>
              @foreach($wellness['insights'] ?? [] as $insight)
                <article class="wellness-insight-card"><i class="ti {{ $insight['icon'] }}"></i><div><h3>{{ $insight['title'] }}</h3><p>{{ $insight['body'] }}</p><button type="button">{{ $insight['cta'] }}<i class="ti ti-arrow-right"></i></button></div></article>
              @endforeach
            </section>

            @if($bankConnected)

              <section class="budget-card">
                <div class="section-title">
                  <h2><i class="ti ti-chart-pie"></i>Budget snapshot</h2>
                </div>
                @foreach($budgetCategories as $category)
                  @php
                    $percent = min(100, round(($category['spent'] / $category['budget']) * 100));
                  @endphp
                  <div class="budget-row">
                    <i class="ti {{ $category['icon'] }}"></i>
                    <div>
                      <span>{{ $category['label'] }}</span>
                      <strong>{{ $money($category['spent']) }} of {{ $money($category['budget']) }}</strong>
                      <div class="budget-meter"><span style="width: {{ $percent }}%"></span></div>
                    </div>
                  </div>
                @endforeach
              </section>

              <section class="cash-flow-card">
                <div class="section-title">
                  <h2><i class="ti ti-arrows-exchange"></i>Cash-flow outlook</h2>
                </div>
                <dl>
                  @foreach($cashFlowItems as $item)
                    <div><dt>{{ $item['label'] }}</dt><dd>{{ $item['value'] }}</dd></div>
                  @endforeach
                </dl>
              </section>
            @else
              <article class="connect-bank-card">
                <i class="ti ti-building-bank"></i>
                <h3>Connect a bank account</h3>
                <p>Use a Plaid connection to unlock spending, budgeting, cash-flow alerts, and upcoming bill insights.</p>
                <button class="btn btn-primary w-100" type="button">Connect with Plaid</button>
              </article>
            @endif
          </section>
          @break
        @case('assets')
          @php
            $vehicles = $scenario['assets']['vehicles'] ?? [];
          @endphp
          <section class="assets-page">
            <form id="add-vehicle-form" method="POST" action="{{ route('prototype.assets.add') }}" hidden>
              @csrf
            </form>
            @if(session('prototype_vehicle_added'))
              <div class="asset-added-message" role="status"><i class="ti ti-circle-check"></i><span><strong>Vehicle added</strong>Your vehicle is now being tracked.</span></div>
            @endif
            @if(count($vehicles) > 0)
            <section class="asset-vehicle-list" aria-label="Tracked vehicles">
              @foreach($vehicles as $vehicle)
                <article class="asset-vehicle-card">
                  <div class="asset-vehicle-heading">
                    <div class="asset-car-icon"><i class="ti ti-car"></i></div>
                    <div>
                      <span>{{ $vehicle['nickname'] ?? 'My vehicle' }}</span>
                      <h3>{{ $vehicle['year'] }} {{ $vehicle['make'] }} {{ $vehicle['model'] }}</h3>
                      <small>{{ $vehicle['trim'] ?? 'Vehicle details' }} • {{ $vehicle['mileage'] ?? 'Mileage needed' }} miles</small>
                    </div>
                    <em>{{ $vehicle['status'] ?? 'Tracked' }}</em>
                  </div>
                  <div class="asset-vehicle-metrics">
                    <div><span>Estimated value</span><strong>{{ $money($vehicle['estimated_value'] ?? 0) }}</strong></div>
                    <div><span>Estimated equity</span><strong>{{ $money($vehicle['estimated_equity'] ?? 0) }}</strong></div>
                  </div>
                  <div class="asset-vehicle-actions">
                    <button class="btn btn-outline-primary w-100" type="button"><i class="ti ti-edit"></i>Manage vehicle</button>
                  </div>
                </article>
              @endforeach
            </section>
            @else
              <article class="asset-empty-state"><i class="ti ti-car"></i><h2>Track a vehicle</h2><p>See your estimated vehicle value and equity.</p><button class="btn btn-primary" type="submit" form="add-vehicle-form">Add vehicle</button></article>
            @endif
            @if(count($vehicles) > 0 && count($vehicles) < 3)
            <article class="asset-add-card">
              <i class="ti ti-circle-plus"></i>
              <div>
                <h3>Add a vehicle</h3>
              </div>
              <button class="btn btn-outline-primary w-100" type="submit" form="add-vehicle-form">Add vehicle</button>
            </article>
            @endif
          </section>
          @break
        @case('profile')
          <section class="profile-page">
            <div class="profile-hero">
              <div class="profile-avatar-large">
                {{ strtoupper(substr($scenario['customer']['first_name'] ?? 'J', 0, 1)) }}{{ strtoupper(substr($scenario['customer']['last_name'] ?? 'D', 0, 1)) }}
              </div>
              <div>
                <h2>{{ $scenario['customer']['first_name'] }} {{ $scenario['customer']['last_name'] ?? 'Davis' }}</h2>
                <p>Regional Finance customer since 2021</p>
              </div>
            </div>

            <nav class="profile-tabs" aria-label="Profile navigation">
              <span class="active">My info</span>
              <a href="{{ route('prototype.settings') }}">Settings</a>
            </nav>

            <section class="profile-info-section profile-about-section">
              <div class="profile-section-heading">
                <h3>About me</h3>
              </div>
              <p class="profile-help-copy">To change your legal name or other verified identity information, contact your branch.</p>
              <div class="profile-read-list">
                <div><span>Legal name</span><strong>{{ $scenario['customer']['first_name'] ?? 'Jordan' }} {{ $scenario['customer']['last_name'] ?? 'Davis' }}</strong></div>
                <div><span>Member since</span><strong>2021</strong></div>
              </div>
              <a class="profile-branch-link" href="tel:{{ preg_replace('/[^0-9]/', '', $branch['phone'] ?? '') }}"><i class="ti ti-phone"></i>Call {{ $branch['name'] ?? 'your branch' }}</a>
            </section>

            <section class="profile-info-section" data-profile-section>
              <div class="profile-section-heading">
                <h3>Contact information</h3>
                <button type="button" data-profile-edit-button aria-expanded="false">Edit</button>
              </div>
              <div class="profile-read-list" data-profile-read-panel>
                <div><span>Home address</span><strong data-profile-value="address">1450 Woodruff Rd, Greenville, SC 29607</strong></div>
                <div><span>Email address</span><strong data-profile-value="email">jordan.davis@example.com</strong></div>
                <div><span>Mobile phone</span><strong data-profile-value="phone">(864) 555-2194</strong></div>
              </div>
              <form class="profile-edit-panel" data-profile-edit-panel hidden>
                <label class="profile-edit-wide">Home address<input data-profile-input="address" type="text" value="1450 Woodruff Rd, Greenville, SC 29607"></label>
                <label>Email address<input data-profile-input="email" type="email" value="jordan.davis@example.com"></label>
                <label>Mobile phone<input data-profile-input="phone" type="tel" value="(864) 555-2194"></label>
                <div class="profile-edit-actions">
                  <button class="btn btn-primary" type="submit">Save</button>
                  <button class="btn btn-outline-primary" type="button" data-profile-cancel>Cancel</button>
                </div>
              </form>
              <p class="profile-save-status" data-profile-save-status hidden><i class="ti ti-circle-check"></i>Changes saved</p>
            </section>

            <section class="profile-info-section" data-profile-section>
              <div class="profile-section-heading">
                <h3>Career</h3>
                <button type="button" data-profile-edit-button aria-expanded="false">Edit</button>
              </div>
              <div class="profile-read-list" data-profile-read-panel>
                <div><span>Employment status</span><strong data-profile-value="employment">Employed full time</strong></div>
                <div><span>Current employer</span><strong data-profile-value="employer">Carolina Logistics</strong></div>
                <div><span>Monthly net income</span><strong data-profile-value="income">$4,850</strong></div>
                <div><span>Pay frequency</span><strong data-profile-value="frequency">Biweekly</strong></div>
              </div>
              <form class="profile-edit-panel" data-profile-edit-panel hidden>
                <label>Employment status<select data-profile-input="employment"><option selected>Employed full time</option><option>Employed part time</option><option>Self employed</option><option>Retired</option></select></label>
                <label>Current employer<input data-profile-input="employer" type="text" value="Carolina Logistics"></label>
                <label>Monthly net income<input data-profile-input="income" type="text" value="$4,850"></label>
                <label>Pay frequency<select data-profile-input="frequency"><option selected>Biweekly</option><option>Weekly</option><option>Monthly</option></select></label>
                <div class="profile-edit-actions">
                  <button class="btn btn-primary" type="submit">Save</button>
                  <button class="btn btn-outline-primary" type="button" data-profile-cancel>Cancel</button>
                </div>
              </form>
              <p class="profile-disclosure"><i class="ti ti-shield-check"></i>Income updates may be reviewed before they are used for lending decisions.</p>
              <p class="profile-save-status" data-profile-save-status hidden><i class="ti ti-circle-check"></i>Changes saved</p>
            </section>

            <section class="profile-info-section" data-profile-section>
              <div class="profile-section-heading">
                <h3>Trusted contact</h3>
                <button type="button" data-profile-edit-button aria-expanded="false">Edit</button>
              </div>
              <div class="profile-read-list" data-profile-read-panel>
                <div><span>Name</span><strong data-profile-value="trusted-name">Morgan Davis</strong></div>
                <div><span>Relationship</span><strong data-profile-value="trusted-relationship">Spouse</strong></div>
                <div><span>Phone</span><strong data-profile-value="trusted-phone">(864) 555-8012</strong></div>
              </div>
              <form class="profile-edit-panel" data-profile-edit-panel hidden>
                <label>Name<input data-profile-input="trusted-name" type="text" value="Morgan Davis"></label>
                <label>Relationship<input data-profile-input="trusted-relationship" type="text" value="Spouse"></label>
                <label>Phone<input data-profile-input="trusted-phone" type="tel" value="(864) 555-8012"></label>
                <div class="profile-edit-actions">
                  <button class="btn btn-primary" type="submit">Save</button>
                  <button class="btn btn-outline-primary" type="button" data-profile-cancel>Cancel</button>
                </div>
              </form>
              <p class="profile-save-status" data-profile-save-status hidden><i class="ti ti-circle-check"></i>Changes saved</p>
            </section>
          </section>
          @break
        @case('documents')
          <section class="document-center">
            <div class="document-hero">
              <div class="document-hero-icon"><i class="ti ti-folder"></i></div>
              <div>
                <span class="eyebrow">Documents</span>
                <h2>Document Center</h2>
                <p>Find loan agreements, payment schedules, statements, disclosures, and archived documents for loans you have now or had in the past.</p>
              </div>
            </div>

            <div class="document-search-row">
              <i class="ti ti-search"></i>
              <span>Search documents</span>
            </div>

            <div class="document-group">
              <div class="document-group-heading">
                <h3>Current loans</h3>
                <span>{{ count($currentDocuments) }}</span>
              </div>
              @foreach($currentDocuments as $documentGroup)
                <article class="document-loan-card">
                  <div class="document-loan-heading">
                    <div>
                      <strong>{{ $documentGroup['loan']['name'] ?? 'Personal loan' }}</strong>
                      <span>Loan #{{ $documentGroup['loan']['id'] ?? '1002841' }}</span>
                    </div>
                    <small>{{ ucfirst(str_replace('_', ' ', $documentGroup['loan']['status'] ?? 'current')) }}</small>
                  </div>
                  <div class="document-list">
                    @foreach($documentGroup['documents'] as $document)
                      <a href="javascript:void(0)" class="document-row">
                        <i class="ti {{ $document['icon'] }}"></i>
                        <div>
                          <strong>{{ $document['name'] }}</strong>
                          <span>{{ $document['type'] }} · {{ $date($document['date']) }}</span>
                        </div>
                        <small>{{ $document['status'] }}</small>
                      </a>
                    @endforeach
                  </div>
                </article>
              @endforeach
            </div>

            <div class="document-group">
              <div class="document-group-heading">
                <h3>Past loans</h3>
                <span>{{ count($pastLoanDocuments) }}</span>
              </div>
              @foreach($pastLoanDocuments as $documentGroup)
                <article class="document-loan-card archived">
                  <div class="document-loan-heading">
                    <div>
                      <strong>{{ $documentGroup['loan']['name'] }}</strong>
                      <span>Loan #{{ $documentGroup['loan']['id'] }} · Closed {{ $date($documentGroup['loan']['closed_date']) }}</span>
                    </div>
                    <small>Archived</small>
                  </div>
                  <div class="document-list">
                    @foreach($documentGroup['documents'] as $document)
                      <a href="javascript:void(0)" class="document-row">
                        <i class="ti {{ $document['icon'] }}"></i>
                        <div>
                          <strong>{{ $document['name'] }}</strong>
                          <span>{{ $document['type'] }} · {{ $date($document['date']) }}</span>
                        </div>
                        <small>{{ $document['status'] }}</small>
                      </a>
                    @endforeach
                  </div>
                </article>
              @endforeach
            </div>
          </section>
          @break
        @case('chat')
          <section class="mock-chat" data-mock-chat>
            <div class="mock-chat-header">
              <div class="chat-assistant-avatar"><i class="ti ti-message-chatbot"></i></div>
              <div>
                <span class="eyebrow">Regional assistant</span>
                <h2>How can we help?</h2>
              </div>
            </div>

            <div class="chat-thread" aria-live="polite" data-chat-thread>
              <article class="chat-bubble assistant">
                <span>Regional Assistant</span>
                <p>Hi Jordan. I can help with payments, loan details, documents, offers, or branch questions.</p>
              </article>
              <article class="chat-bubble assistant">
                <span>Prototype note</span>
                <p>This demo assistant is a placeholder. In the app, this experience would be AI-powered and connected to Regional Finance support flows.</p>
              </article>
            </div>

            <div class="chat-suggestions" aria-label="Suggested questions">
              <button type="button" data-chat-prompt="Can you help me understand my next payment?">Next payment</button>
              <button type="button" data-chat-prompt="Can you explain my loan options?">Loan options</button>
              <button type="button" data-chat-prompt="What documents do I need?">Documents</button>
            </div>

            <form class="chat-composer" data-chat-form>
              <label class="visually-hidden" for="mock-chat-message">Message</label>
              <input id="mock-chat-message" data-chat-input type="text" placeholder="Ask a question" autocomplete="off">
              <button type="submit" aria-label="Send message"><i class="ti ti-send"></i></button>
            </form>
          </section>
          @break
        @case('notifications')
          @php
            $unreadCount = collect($notifications)->where('read', false)->count();
          @endphp
          <section class="notifications-hub">
            <div class="notifications-toolbar">
              <div>
                <span class="eyebrow">{{ $unreadCount }} unread</span>
                <h2>Notifications</h2>
              </div>
              <div class="notification-tools">
                <form method="POST" action="{{ route('prototype.notifications.readAll') }}">
                  @csrf
                  <button class="icon-btn" type="submit" aria-label="Mark all notifications as read"><i class="ti ti-list-check"></i></button>
                </form>
                <a class="icon-btn" href="{{ route('prototype.settings') }}" aria-label="Notification settings"><i class="ti ti-settings"></i></a>
              </div>
            </div>

            <div class="notification-list">
              @foreach($notifications as $notification)
                <article class="notification-item {{ $notification['read'] ? 'read' : 'unread' }}">
                  <a href="{{ $notification['url'] }}" class="notification-content">
                    <i class="ti {{ $notification['icon'] }}"></i>
                    <div>
                      <div class="notification-meta">
                        <span>{{ $notification['type'] }}</span>
                        <small>{{ $notification['time'] }}</small>
                      </div>
                      <h3>{{ $notification['title'] }}</h3>
                      <p>{{ $notification['body'] }}</p>
                    </div>
                  </a>
                  @if(! $notification['read'])
                    <form method="POST" action="{{ route('prototype.notifications.read', $notification['id']) }}">
                      @csrf
                      <button type="submit" aria-label="Mark {{ $notification['title'] }} as read"><i class="ti ti-check"></i></button>
                    </form>
                  @else
                    <span class="notification-read-state"><i class="ti ti-check"></i></span>
                  @endif
                </article>
              @endforeach
            </div>
          </section>
          @break
        @case('settings')
          <section class="settings-page">
            <div class="settings-profile-card">
              <div class="profile-avatar-large sm">
                {{ strtoupper(substr($scenario['customer']['first_name'] ?? 'J', 0, 1)) }}{{ strtoupper(substr($scenario['customer']['last_name'] ?? 'D', 0, 1)) }}
              </div>
              <div>
                <h2>{{ $scenario['customer']['first_name'] ?? 'Jordan' }} {{ $scenario['customer']['last_name'] ?? 'Davis' }}</h2>
                <a href="{{ route('prototype.profile') }}">Edit profile</a>
              </div>
            </div>

            <div class="settings-list">
              <label><span><i class="ti ti-bell"></i>Payment reminders</span><input type="checkbox" checked></label>
              <label><span><i class="ti ti-file-text"></i>Document alerts</span><input type="checkbox" checked></label>
              <label><span><i class="ti ti-sparkles"></i>Offer updates</span><input type="checkbox"></label>
              <label><span><i class="ti ti-lock"></i>Face ID</span><input type="checkbox" checked></label>
            </div>

            <div class="simple-list-card">
              <a href="{{ route('prototype.support') }}"><i class="ti ti-headphones"></i><span>Support</span><strong>Contact</strong></a>
              <button type="button"><i class="ti ti-shield-lock"></i><span>Privacy and security</span><strong>View</strong></button>
            </div>
          </section>
          @break
        @case('support')
          <section class="branch-support-page">
            <div class="branch-support-hero">
              <span class="eyebrow">Your branch</span>
              <h2>{{ $branch['name'] ?? 'Regional Finance branch' }}</h2>
              <p>Get help with payments, documents, applications, payoff questions, and account servicing.</p>
            </div>

            <div
              class="branch-map branch-map-large"
              data-branch-map
              data-lat="{{ $branch['lat'] ?? 34.8334 }}"
              data-lng="{{ $branch['lng'] ?? -82.3075 }}"
              data-zoom="15"
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
              <div class="branch-map-location">
                <strong>{{ $branch['name'] ?? 'Regional Finance branch' }}</strong>
                <span>{{ $branch['address'] ?? 'Find your nearest branch' }}</span>
              </div>
            </div>

            <div class="branch-detail-list branch-support-details">
              <div>
                <i class="ti ti-map-2"></i>
                <span>Address</span>
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
                <i class="ti ti-mail"></i>
                <span>Email</span>
                <strong>greenville@regionalfinance.com</strong>
              </div>
              <div>
                <i class="ti ti-user-star"></i>
                <span>Branch manager</span>
                <strong>{{ $branch['manager'] ?? 'Branch team' }}</strong>
              </div>
            </div>

            <div class="button-row">
              <a href="tel:{{ preg_replace('/[^0-9]/', '', $branch['phone'] ?? '') }}" class="btn btn-primary flex-fill"><i class="ti ti-phone"></i>Call</a>
              <a href="mailto:greenville@regionalfinance.com" class="btn btn-outline-primary flex-fill"><i class="ti ti-mail"></i>Email</a>
            </div>
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch['address'] ?? 'Regional Finance') }}" target="_blank" rel="noopener" class="btn btn-outline-primary w-100"><i class="ti ti-route"></i>Get directions</a>
            <a href="https://branches.regionalfinance.com/fl/jacksonville/6000-lake-grey-blvd" target="_blank" rel="noopener" class="btn btn-outline-primary w-100 branch-page-link"><i class="ti ti-external-link"></i>View official branch page</a>
          </section>
          @break
        @default
          <p>Account servicing detail for loan {{ $id }} with payment history, documents, and payoff information.</p>
          <button class="btn btn-primary w-100" type="button">Make a payment</button>
        @endswitch
      </section>

    @endif
    <x-prototype-bottom-nav :scenario="$scenario" :current="$type" />
  </main>
</div>
<script type="application/json" data-prototype-state>@json($appState)</script>
@endsection

@section('page-script')
@if($type === 'support')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endif
<script src="{{ asset('assets/js/prototype-mobile.js') }}?v=20260827profile-refine"></script>
@endsection
