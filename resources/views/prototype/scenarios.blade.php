@php
$navbarView = 'layouts/sections/navbar/navbar-empty';
$footerView = 'layouts/sections/footer/footer-empty';
$isMenu = false;
$isNavbar = false;
$isFooter = false;
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Scenario Builder')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}?v=20260825asset-add">
@endsection

@section('content')
<main class="scenario-page state-builder-page">
  <header class="scenario-hero state-builder-hero">
    <div>
      <span class="brand-mark">Regional Finance prototype</span>
      <h1>Scenario Builder</h1>
      <p>Adjust customer state once. Every screen updates from the same source.</p>
    </div>
    <a href="{{ route('prototype.index') }}" class="btn btn-primary"><i class="ti ti-device-mobile"></i>Open app</a>
  </header>

  @if($appState['payments']['pending'] ?? null)
    @php($pendingPayment = $appState['payments']['pending'])
    <section class="builder-pending-payment" role="status">
      <i class="ti ti-clock-check"></i>
      <div><span>Pending payment</span><strong>${{ number_format((float) $pendingPayment['amount'], 2) }} scheduled for {{ \Carbon\Carbon::parse($pendingPayment['payment_date'])->format('M j, Y') }}</strong><small>This payment remains in the prototype until it is cancelled or the scenario is reset.</small></div>
      <a href="{{ route('prototype.payment') }}">View<i class="ti ti-arrow-right"></i></a>
    </section>
  @endif

  <section class="builder-section quick-presets">
    <div class="builder-section-heading">
      <div><i class="ti ti-bolt"></i><h2>Quick presets</h2></div>
      <span class="state-saved-indicator" data-state-save-status aria-live="polite"><i class="ti ti-cloud-check"></i><span>Saved</span></span>
    </div>
    <div class="preset-strip">
      @foreach($presets as $presetId => $preset)
        <form method="POST" action="{{ route('prototype.presets.apply', $presetId) }}" data-preset-form data-preset-id="{{ $presetId }}">
          @csrf
          <button class="preset-button {{ ($appState['meta']['preset'] ?? '') === $presetId ? 'active' : '' }}" type="submit">
            <i class="ti {{ $preset['icon'] }}"></i>
            <span><strong>{{ $preset['label'] }}</strong><small>{{ $preset['description'] }}</small></span>
          </button>
        </form>
      @endforeach
    </div>
  </section>

  <form method="POST" action="{{ route('prototype.state.update') }}" class="state-builder-form" data-state-builder data-success-url="{{ route('prototype.index') }}">
    @csrf

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>1</span><h2>Customer & loan status</h2></div><i class="ti ti-user-circle"></i></div>
      <div class="builder-control">
        <label>Customer type</label>
        <div class="builder-segments three">
          @foreach($options['customer_types'] as $value => $label)
            <label><input type="radio" name="customer[type]" value="{{ $value }}" @checked($appState['customer']['type'] === $value)><span>{{ $label }}</span></label>
          @endforeach
        </div>
      </div>
      <div class="builder-control inline-control">
        <div><label>Active loans</label><small>Shown for active borrowers</small></div>
        <div class="builder-stepper" data-stepper>
          <button type="button" data-stepper-minus aria-label="Remove loan"><i class="ti ti-minus"></i></button>
          <input type="number" name="loans[count]" value="{{ $appState['loans']['count'] }}" min="0" max="2" readonly>
          <button type="button" data-stepper-plus aria-label="Add loan"><i class="ti ti-plus"></i></button>
        </div>
      </div>
    </section>

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>2</span><h2>Offers</h2></div><i class="ti ti-sparkles"></i></div>
      <div class="builder-control">
        <label for="offer-type">Primary lending offer</label>
        <select id="offer-type" name="offer[type]" class="form-select">
          @foreach($options['offer_types'] as $value => $label)
            <option value="{{ $value }}" @selected($appState['offer']['type'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
        <small>Application progress and serious delinquency automatically override offers.</small>
      </div>
    </section>

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>3</span><h2>Payment & delinquency</h2></div><i class="ti ti-calendar-dollar"></i></div>
      <div class="builder-control">
        <label for="payment-status">Payment status</label>
        <select id="payment-status" name="loans[payment_status]" class="form-select">
          @foreach($options['payment_statuses'] as $value => $label)
            <option value="{{ $value }}" @selected($appState['loans']['payment_status'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </section>

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>4</span><h2>Financial wellness</h2></div><i class="ti ti-heart-rate-monitor"></i></div>
      <div class="builder-control">
        <label>Credit score change</label>
        <div class="builder-segments three">
          @foreach(['decrease' => 'Decrease', 'none' => 'No change', 'increase' => 'Increase'] as $value => $label)
            <label><input type="radio" name="wellness[credit_score_change]" value="{{ $value }}" @checked($appState['wellness']['credit_score_change'] === $value)><span>{{ $label }}</span></label>
          @endforeach
        </div>
      </div>
      <div class="builder-control inline-control">
        <div><label for="credit-score">Credit score</label><small>300-850</small></div>
        <input id="credit-score" class="builder-number-input" type="number" name="wellness[credit_score]" value="{{ $appState['wellness']['credit_score'] }}" min="300" max="850">
      </div>
      <div class="builder-toggle-grid">
        @foreach(['high_utilization' => ['High utilization', 'Show a credit usage insight'], 'budget_warning' => ['Budget warning', 'Show a category warning'], 'bank_connected' => ['Bank connected', 'Enable spending and cash flow']] as $key => [$label, $description])
          <label class="builder-switch"><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span><input type="hidden" name="wellness[{{ $key }}]" value="0"><input type="checkbox" name="wellness[{{ $key }}]" value="1" @checked($appState['wellness'][$key])></label>
        @endforeach
      </div>
      <div class="builder-split-controls">
        <label>Cash-flow outlook<select name="wellness[cash_flow]" class="form-select">@foreach(['low' => 'Low', 'normal' => 'Normal', 'strong' => 'Strong'] as $value => $label)<option value="{{ $value }}" @selected($appState['wellness']['cash_flow'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Spending trend<select name="wellness[spending_trend]" class="form-select">@foreach(['down' => 'Down', 'normal' => 'Normal', 'up' => 'Up'] as $value => $label)<option value="{{ $value }}" @selected($appState['wellness']['spending_trend'] === $value)>{{ $label }}</option>@endforeach</select></label>
      </div>
    </section>

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>5</span><h2>Originations</h2></div><i class="ti ti-clipboard-list"></i></div>
      <label class="builder-switch primary-switch"><span><strong>Application in progress</strong><small>Replaces acquisition messages with resume actions</small></span><input type="hidden" name="origination[active]" value="0"><input type="checkbox" name="origination[active]" value="1" @checked($appState['origination']['active']) data-origination-toggle></label>
      <div class="builder-control" data-origination-step @if(!$appState['origination']['active']) hidden @endif>
        <label for="origination-step">Current step</label>
        <select id="origination-step" name="origination[step]" class="form-select">
          @foreach($options['origination_steps'] as $value => $label)
            <option value="{{ $value }}" @selected(($appState['origination']['step'] ?? '') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </section>

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>6</span><h2>Vehicles</h2></div><i class="ti ti-car"></i></div>
      <div class="builder-control inline-control">
        <div><label>Tracked vehicles</label><small>Supports 0-3 demo vehicles</small></div>
        <div class="builder-stepper" data-stepper>
          <button type="button" data-stepper-minus aria-label="Remove vehicle"><i class="ti ti-minus"></i></button>
          <input type="number" name="vehicles[count]" value="{{ $appState['vehicles']['count'] }}" min="0" max="3" readonly>
          <button type="button" data-stepper-plus aria-label="Add vehicle"><i class="ti ti-plus"></i></button>
        </div>
      </div>
    </section>

    <section class="builder-section">
      <div class="builder-section-heading"><div><span>7</span><h2>Protection & benefits</h2></div><i class="ti ti-shield-check"></i></div>
      <label class="builder-switch primary-switch"><span><strong>Protection merchandising</strong><small>Optional and never required for loan approval</small></span><input type="hidden" name="protection[enabled]" value="0"><input type="checkbox" name="protection[enabled]" value="1" @checked($appState['protection']['enabled'])></label>
      <div class="builder-control">
        <label for="protection-context">Featured context</label>
        <select id="protection-context" name="protection[context]" class="form-select">
          @foreach(['loan' => 'Payment protection', 'home_auto' => 'Home & Auto', 'auto' => 'Auto Plus'] as $value => $label)
            <option value="{{ $value }}" @selected($appState['protection']['context'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </section>

    <div class="builder-actions">
      <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i>Save state</button>
    </div>
  </form>

  <form method="POST" action="{{ route('prototype.reset') }}" class="builder-reset-form" data-reset-prototype>
    @csrf
    <button class="btn btn-outline-danger" type="submit"><i class="ti ti-restore"></i>Reset prototype</button>
  </form>
</main>
<script type="application/json" data-prototype-state>@json($appState)</script>
@endsection

@section('page-script')
<script src="{{ asset('assets/js/prototype-mobile.js') }}?v=20260825scenario-save"></script>
@endsection
