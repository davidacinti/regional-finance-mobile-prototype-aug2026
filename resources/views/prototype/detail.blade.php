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
  'wellness' => 'Financial health',
  'assets' => 'Vehicle details',
  'profile' => 'Profile',
  'documents' => 'Document Center',
  'chat' => 'AI chat',
  'notifications' => 'Notifications',
  'support' => 'Support',
  'payment' => 'Make a payment',
];
$loans = $scenario['loans'] ?? [];
$branch = $scenario['branch'] ?? [];
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
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}?v=20260714t">
@endsection

@section('content')
<div class="prototype-shell" data-prototype-theme="light">
  <main class="phone-frame detail-frame" aria-label="{{ $titles[$type] ?? 'Prototype detail' }}">
    <header class="detail-topbar">
      <nav class="top-navbar" aria-label="App actions">
        <a class="top-nav-btn" href="{{ route('prototype.index') }}" aria-label="Back to home"><i class="ti ti-arrow-left"></i></a>
        <a class="top-logo-link" href="{{ route('prototype.index') }}" aria-label="Regional Finance home">
          <img class="regional-logo" src="{{ asset('assets/img/branding/regionals-logo.svg') }}" alt="Regional Finance">
        </a>
        <div class="top-nav-actions">
          <a class="top-nav-btn" href="{{ route('prototype.chat') }}" aria-label="Open AI chat"><i class="ti ti-message-chatbot"></i></a>
          <a class="top-nav-btn notification-action" href="{{ route('prototype.notifications') }}" aria-label="View notifications"><i class="ti ti-bell"></i><span></span></a>
          <button class="top-nav-btn theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false"><i class="ti ti-moon"></i></button>
        </div>
      </nav>
    </header>

    @if($type === 'loan')
      <section class="loan-detail-hero">
        <div>
          <span>Original principal amount</span>
          <strong>{{ $money(($loan['balance'] ?? 0) + 1100) }}</strong>
        </div>
        <div>
          <span>Account balance</span>
          <strong>{{ $money($loan['balance']) }}</strong>
        </div>
        <a href="{{ route('prototype.loan', ['loan' => $loan['id'], 'sheet' => 'details']) }}" class="btn btn-outline-light btn-sm">Loan details</a>
      </section>

      <section class="app-card loan-servicing-card">
        <div class="servicing-heading">
          <div>
            <span>Amount due</span>
            <strong>{{ $money(($loan['past_due_amount'] ?? 0) > 0 ? $loan['past_due_amount'] : $loan['next_payment_amount']) }}</strong>
          </div>
          <span class="autopay-state"><i class="ti ti-refresh"></i>{{ $loan['autopay_enabled'] ? 'AutoPay enabled' : 'AutoPay off' }}</span>
        </div>
        <p class="due-line"><i class="ti ti-circle-filled"></i>Due on {{ $date($loan['next_payment_date']) }}</p>
        <a class="detail-link" href="{{ route('prototype.payment') }}">See payment details</a>
        <div class="servicing-facts">
          <div><span>Payoff amount</span><strong>{{ $money(($loan['balance'] ?? 0) + 42.18) }}</strong></div>
          <div><span>Last payment date</span><strong>-</strong></div>
          <div><span>Last payment amount</span><strong>-</strong></div>
        </div>
        <a href="{{ route('prototype.payment') }}" class="btn btn-primary w-100">Manage AutoPay</a>
        <a href="{{ route('prototype.payment') }}" class="btn btn-outline-primary w-100 mt-3">Make a Payment</a>
      </section>

      <section class="loan-detail-section">
        <h2>Upcoming Payments</h2>
        <article class="payment-row">
          <i class="ti ti-refresh"></i>
          <div>
            <strong>AutoPay (Monthly)</strong>
            <span>Scheduled {{ $date($loan['next_payment_date']) }}</span>
          </div>
          <strong>{{ $money($loan['next_payment_amount']) }}</strong>
          <button type="button" aria-label="Manage AutoPay options"><i class="ti ti-dots-vertical"></i></button>
        </article>
      </section>

      <section class="loan-detail-section">
        <div class="section-title">
          <h2>Recent Activity</h2>
          <a href="{{ route('prototype.payment') }}">View all Payment Activity</a>
        </div>
        <article class="empty-activity app-card">
          <i class="ti ti-receipt-off"></i>
          <strong>No recent payments yet</strong>
          <span>Payment activity will appear here after a transaction posts.</span>
        </article>
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
          @php($application = $scenario['application'] ?? [])
          <section class="application-detail-flow">
            <span class="eyebrow">{{ ($application['status'] ?? '') === 'approved' ? 'Approved' : 'Application status' }}</span>
            <h2>{{ $application['headline'] ?? $application['current_step'] ?? 'Continue your application' }}</h2>
            <p>{{ $application['summary'] ?? 'Continue the in-progress application, review requested items, and complete the next required step.' }}</p>
            <div class="progress" aria-label="Application progress">
              <div class="progress-bar" style="width: {{ $application['progress_percent'] ?? 50 }}%"></div>
            </div>
            <dl class="payment-summary-list">
              <div><dt>Current step</dt><dd>{{ $application['current_step'] ?? 'Application' }}</dd></div>
              <div><dt>Next action</dt><dd>{{ $application['next_action'] ?? 'Continue application' }}</dd></div>
              <div><dt>Progress</dt><dd>{{ $application['progress_percent'] ?? 50 }}%</dd></div>
            </dl>
            @if(isset($application['due_by']))
              <div class="payment-warning"><i class="ti ti-alert-circle"></i>Complete this by {{ $dateLong($application['due_by']) }} to keep your application moving.</div>
            @elseif(isset($application['expires_at']))
              <div class="payment-warning"><i class="ti ti-clock"></i>Complete by {{ $dateLong($application['expires_at']) }}.</div>
            @endif
            <button class="btn btn-primary w-100 mt-3" type="button">{{ $application['cta'] ?? 'Continue application' }}</button>
          </section>
          @break
        @case('offer')
          <p>Review available loan options, plain-language disclosures, expiration dates, and credit-impact messaging.</p>
          <button class="btn btn-primary w-100" type="button">Review mock offer</button>
          @break
        @case('wellness')
          <p>Expanded credit score, spending, cash-flow, and education content would live here.</p>
          <button class="btn btn-outline-primary w-100" type="button">View insights</button>
          @break
        @case('assets')
          <p>Connected vehicle details, estimated value history, and equity education would live here.</p>
          <button class="btn btn-outline-primary w-100" type="button">Refresh estimate</button>
          @break
        @case('profile')
          <p>Customer profile, contact preferences, alerts, and app settings would live here.</p>
          <button class="btn btn-outline-primary w-100" type="button">Edit account</button>
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
          @php($unreadCount = collect($notifications)->where('read', false)->count())
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
          </section>
          @break
        @default
          <p>Account servicing detail for loan {{ $id }} with payment history, documents, and payoff information.</p>
          <button class="btn btn-primary w-100" type="button">Make a payment</button>
        @endswitch
      </section>

      @if(! in_array($type, ['payment', 'chat'], true))
        <a href="{{ route('prototype.index') }}" class="btn btn-light w-100">Return home</a>
      @endif
    @endif
  </main>
</div>
@endsection

@section('page-script')
@if($type === 'support')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endif
<script src="{{ asset('assets/js/prototype-mobile.js') }}?v=20260714t"></script>
@endsection
