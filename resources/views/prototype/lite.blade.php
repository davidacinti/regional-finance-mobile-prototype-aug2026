@php
$navbarView = 'layouts/sections/navbar/navbar-empty';
$footerView = 'layouts/sections/footer/footer-empty';
$isMenu = false;
$isNavbar = false;
$isFooter = false;
$customizerHidden = 'customizer-hide';
$application = $scenario['application'];
$stage = $application['step'];
$branch = $scenario['branch'];
$amount = '$' . number_format((float) $application['prequalified_amount'], 0);
$authenticated = (bool) $application['authenticated'];
$taskRoutes = [
  'income' => route('prototype.lite.income'),
  'vehicle' => route('prototype.lite.vehicle'),
  'closing' => route('prototype.lite.closing'),
];
$nextStep = $application['next_step'];
$appointment = $application['appointment'];
$isTaskScreen = in_array($screen, ['income', 'vehicle', 'closing', 'password'], true);
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Regional Finance Application')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}?v=20260902origination-lite">
@endsection

@section('content')
<div class="prototype-shell" data-prototype-theme="light">
  <main class="phone-frame lite-frame {{ $stage === 'lendingtree_offer' ? 'lendingtree-frame' : '' }}" aria-label="Regional Finance origination lite experience">
    @if($stage === 'lendingtree_offer')
      <header class="lendingtree-topbar">
        <span class="lendingtree-brand"><i class="ti ti-leaf"></i>LendingTree</span>
        <span><i class="ti ti-lock"></i>Secure</span>
      </header>
      <section class="lendingtree-marketplace">
        <span class="lite-kicker">Matched for you</span>
        <h1>Your prequalified loan offers</h1>
        <p>Compare estimated options from lenders in your network.</p>

        <article class="lender-offer regional-lender-offer">
          <div class="lender-offer-heading">
            <img src="{{ asset('assets/img/branding/regionals-logo.svg') }}" alt="Regional Finance">
            <span>Recommended</span>
          </div>
          <strong class="lender-amount">{{ $amount }} loan</strong>
          <div class="lender-metrics">
            <div><span>Est. monthly payment</span><strong>$318</strong></div>
            <div><span>APR</span><strong>22.99%</strong></div>
            <div><span>Term</span><strong>36 months</strong></div>
          </div>
          <form method="POST" action="{{ route('prototype.lite.select-offer') }}">
            @csrf
            <button class="btn btn-primary w-100" type="submit">View Regional offer</button>
          </form>
        </article>

        <article class="lender-offer comparison-offer">
          <div><strong>BrightLend</strong><span>$7,000 loan</span></div>
          <div><strong>$302/mo</strong><span>27.40% APR</span></div>
          <button type="button" aria-label="View BrightLend offer"><i class="ti ti-chevron-right"></i></button>
        </article>
        <article class="lender-offer comparison-offer">
          <div><strong>RiverBank</strong><span>$6,500 loan</span></div>
          <div><strong>$289/mo</strong><span>29.15% APR</span></div>
          <button type="button" aria-label="View RiverBank offer"><i class="ti ti-chevron-right"></i></button>
        </article>
        <p class="lendingtree-disclosure">Rates and payments are estimates. Final terms are provided by the selected lender.</p>
      </section>
    @else
      <header class="lite-regional-header">
        @if($isTaskScreen)
          <a class="top-nav-btn" href="{{ route('prototype.index') }}" aria-label="Return to application dashboard" data-back-button><i class="ti ti-arrow-left"></i></a>
        @else
          <span class="lite-secure-mark"><i class="ti ti-lock"></i></span>
        @endif
        <a class="top-logo-link" href="{{ route('prototype.index') }}" aria-label="Regional Finance application home">
          <img class="regional-logo" src="{{ asset('assets/img/branding/regionals-logo.svg') }}" alt="Regional Finance">
        </a>
        <span class="lite-mode-label">Application</span>
      </header>

      @if($stage === 'regional_offer')
        <section class="lite-gate-screen regional-offer-screen">
          <div class="lite-gate-icon"><i class="ti ti-file-search"></i></div>
          <span class="lite-kicker">Credit review</span>
          <h1>Complete your application</h1>
          <p>You selected the {{ $amount }} loan offer. To continue, Regional Finance needs to review your credit.</p>
          <form method="POST" action="{{ route('prototype.lite.continue-offer') }}">
            @csrf
            <label class="lite-credit-consent">
              <input type="checkbox" required checked>
              <span><strong>Authorize credit review</strong>I authorize Regional Finance to obtain my credit report. This is a hard credit inquiry and may impact my credit score.</span>
            </label>
            <button class="btn btn-primary w-100" type="submit">Authorize and continue</button>
          </form>
        </section>
      @elseif($stage === 'otp_phone')
        <section class="lite-gate-screen otp-screen">
          <div class="lite-gate-icon"><i class="ti ti-device-mobile-message"></i></div>
          <span class="lite-kicker">Secure access</span>
          <h1>Verify your phone</h1>
          <p>We'll text a verification code to the number from your LendingTree request.</p>
          <div class="masked-phone">(***) ***-1234</div>
          <form method="POST" action="{{ route('prototype.lite.send-code') }}">
            @csrf
            <button class="btn btn-primary w-100" type="submit">Send code</button>
          </form>
          <small>Message and data rates may apply.</small>
        </section>
      @elseif($stage === 'otp_code')
        <section class="lite-gate-screen otp-screen">
          <div class="lite-gate-icon"><i class="ti ti-lock-check"></i></div>
          <span class="lite-kicker">Code sent</span>
          <h1>Enter verification code</h1>
          <p>Enter the six-digit code sent to (***) ***-1234.</p>
          <form method="POST" action="{{ route('prototype.lite.verify-code') }}" data-otp-form>
            @csrf
            <div class="otp-code-inputs" aria-label="Six digit verification code">
              @for($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" aria-label="Digit {{ $i + 1 }}" data-otp-input value="{{ $i + 1 }}">
              @endfor
            </div>
            <button class="btn btn-primary w-100" type="submit">Verify and continue</button>
          </form>
          <button class="lite-text-button" type="button">Resend code</button>
        </section>
      @elseif($screen === 'income')
        <section class="lite-task-page">
          <span class="lite-kicker">Application task</span>
          <h1>Verify your income</h1>
          <p>Upload a recent paystub or another income document requested by Regional Finance.</p>
          <form method="POST" action="{{ route('prototype.lite.income.submit') }}" enctype="multipart/form-data" data-lite-upload-form>
            @csrf
            <input type="hidden" name="income_document" value="Michael-Reed-paystub.pdf" data-upload-filename>
            <div class="lite-upload-options">
              <label><i class="ti ti-camera"></i><span>Take photo</span><input type="file" accept="image/*" capture="environment" hidden data-lite-file-input></label>
              <label><i class="ti ti-photo"></i><span>Choose from phone</span><input type="file" accept="image/*" hidden data-lite-file-input></label>
              <label><i class="ti ti-file-upload"></i><span>Upload file</span><input type="file" accept="image/*,.pdf" hidden data-lite-file-input></label>
            </div>
            <div class="lite-file-preview" data-lite-file-preview>
              <i class="ti ti-file-check"></i>
              <div><strong data-lite-filename>Michael-Reed-paystub.pdf</strong><span><i class="ti ti-circle-check"></i>Ready to submit</span></div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Submit income document</button>
          </form>
        </section>
      @elseif($screen === 'vehicle')
        <section class="lite-task-page">
          <span class="lite-kicker">Application task</span>
          <h1>Upload photos of your vehicle</h1>
          <p>We need a few photos to verify your vehicle before closing.</p>
          <form method="POST" action="{{ route('prototype.lite.vehicle.submit') }}" enctype="multipart/form-data" data-vehicle-upload-form>
            @csrf
            <div class="vehicle-photo-list">
              @foreach(['Front' => 'ti-car', 'Rear' => 'ti-car', 'Driver side' => 'ti-steering-wheel', 'Passenger side' => 'ti-car', 'VIN / dashboard' => 'ti-dashboard'] as $label => $icon)
                <label class="vehicle-photo-row" data-vehicle-photo>
                  <span class="vehicle-photo-preview"><i class="ti {{ $icon }}"></i></span>
                  <span><strong>{{ $label }}</strong><small data-photo-status>Photo needed</small></span>
                  <span class="vehicle-photo-action"><i class="ti ti-camera-plus"></i>Add</span>
                  <input type="file" accept="image/*" capture="environment" hidden data-vehicle-photo-input>
                </label>
              @endforeach
            </div>
            <button class="btn btn-primary w-100" type="submit">Submit photos</button>
          </form>
        </section>
      @elseif($screen === 'closing')
        <section class="lite-task-page">
          @if($stage === 'closing_scheduled' && $appointment)
            <div class="appointment-confirmation">
              <div class="lite-gate-icon"><i class="ti ti-calendar-check"></i></div>
              <span class="lite-kicker">You're scheduled</span>
              <h1>{{ \Carbon\Carbon::parse($appointment['date'])->format('l, F j') }}</h1>
              <strong>{{ $appointment['time'] }}</strong>
              <p>{{ $branch['name'] }}<br>{{ $branch['address'] }}</p>
              <a class="btn btn-primary w-100" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch['address']) }}"><i class="ti ti-route"></i>Get directions</a>
              <button class="btn btn-outline-primary w-100" type="button"><i class="ti ti-calendar-plus"></i>Add to calendar</button>
            </div>
          @else
            <span class="lite-kicker">Final step</span>
            <h1>Schedule your closing</h1>
            <p>Choose a time to complete your closing at {{ $branch['name'] }}.</p>
            <form method="POST" action="{{ route('prototype.lite.closing.submit') }}" class="closing-scheduler">
              @csrf
              <fieldset>
                <legend>Choose a day</legend>
                <div class="closing-day-options">
                  @foreach(['2026-09-09' => ['Wed', '9'], '2026-09-10' => ['Thu', '10'], '2026-09-11' => ['Fri', '11']] as $value => [$day, $date])
                    <label><input type="radio" name="appointment_date" value="{{ $value }}" @checked($value === '2026-09-10')><span><small>{{ $day }}</small><strong>{{ $date }}</strong></span></label>
                  @endforeach
                </div>
              </fieldset>
              <fieldset>
                <legend>Available times</legend>
                <div class="closing-time-options">
                  @foreach(['9:00 AM', '10:30 AM', '1:00 PM', '3:30 PM'] as $time)
                    <label><input type="radio" name="appointment_time" value="{{ $time }}" @checked($time === '10:30 AM')><span>{{ $time }}</span></label>
                  @endforeach
                </div>
              </fieldset>
              <div class="closing-branch-summary"><i class="ti ti-map-pin"></i><div><strong>{{ $branch['name'] }}</strong><span>{{ $branch['address'] }}</span></div></div>
              <button class="btn btn-primary w-100" type="submit">Confirm appointment</button>
            </form>
          @endif
        </section>
      @elseif($screen === 'password')
        <section class="lite-task-page">
          <span class="lite-kicker">Account access</span>
          <h1>Save your account</h1>
          <p>Create a password so you can return to your application without requesting a new code.</p>
          <form method="POST" action="{{ route('prototype.lite.password.submit') }}" class="lite-password-form">
            @csrf
            <label>Email address<input type="email" value="michael.reed@example.com"></label>
            <label>Create password<input type="password" value="Regional2026!"></label>
            <label>Confirm password<input type="password" value="Regional2026!"></label>
            <div class="password-requirements"><i class="ti ti-shield-check"></i>Use at least 8 characters, including a number and symbol.</div>
            <button class="btn btn-primary w-100" type="submit">Set up password</button>
          </form>
        </section>
      @else
        <section class="lite-dashboard">
          <header class="lite-welcome">
            <span class="lite-kicker">Welcome, {{ $scenario['customer']['first_name'] }}</span>
            <h1>{{ $screen === 'application' ? 'Application status' : 'Your loan application' }}</h1>
          </header>

          <section class="lite-application-summary">
            <div>
              <span>Selected loan amount</span>
              <strong>{{ $amount }}</strong>
            </div>
            <span class="lite-status-pill"><i class="ti {{ $stage === 'closing_scheduled' ? 'ti-calendar-check' : ($stage === 'complete' ? 'ti-circle-check' : 'ti-progress-check') }}"></i>{{ $application['headline'] }}</span>
            @if(!in_array($stage, ['closing_scheduled', 'complete'], true))<p>{{ $application['summary'] }}</p>@endif
          </section>

          @if($nextStep)
            <section class="lite-next-step">
              <div class="lite-next-step-icon"><i class="ti {{ $nextStep['icon'] }}"></i></div>
              <div><span class="lite-kicker">{{ $nextStep['eyebrow'] }}</span><h2>{{ $nextStep['headline'] }}</h2><p>{{ $nextStep['body'] }}</p></div>
              <a class="btn btn-primary w-100" href="{{ $taskRoutes[$nextStep['key']] }}">{{ $nextStep['cta'] }}<i class="ti ti-arrow-right"></i></a>
            </section>
          @elseif($stage === 'closing_scheduled' && $appointment)
            <section class="lite-appointment-card">
              <i class="ti ti-calendar-check"></i>
              <div><span class="lite-kicker">Closing scheduled</span><h2>{{ \Carbon\Carbon::parse($appointment['date'])->format('l, F j') }} at {{ $appointment['time'] }}</h2><p>{{ $branch['name'] }}<br>{{ $branch['address'] }}</p></div>
              <a href="{{ route('prototype.lite.closing') }}">View appointment<i class="ti ti-arrow-right"></i></a>
            </section>
          @else
            <section class="lite-ready-card"><i class="ti ti-circle-check"></i><div><span class="lite-kicker">Application complete</span><h2>You're ready to close</h2><p>Your application tasks are complete. {{ $branch['name'] }} will help with the final details.</p></div></section>
          @endif

          <section class="lite-progress-card">
            <div class="lite-section-heading"><h2>Application progress</h2><span>{{ $application['progress_percent'] }}%</span></div>
            <div class="progress application-progress"><div class="progress-bar" style="width: {{ $application['progress_percent'] }}%"></div></div>
            <div class="lite-progress-list">
              @foreach($application['tasks'] as $task)
                @php($isComplete = in_array($task['status'], ['Complete', 'Scheduled'], true))
                <div class="{{ $isComplete ? 'complete' : ($task['status'] === 'Action required' ? 'active' : '') }}">
                  <i class="ti {{ $isComplete ? 'ti-circle-check-filled' : ($task['status'] === 'Action required' ? 'ti-circle-arrow-right-filled' : 'ti-circle') }}"></i>
                  <span><strong>{{ $task['label'] }}</strong><small>{{ $task['status'] }}</small></span>
                </div>
              @endforeach
            </div>
          </section>

          @if(!$application['password_created'])
            <section class="lite-password-prompt">
              <i class="ti ti-lock-plus"></i>
              <div><span class="lite-kicker">Save your account</span><h2>Create a password</h2><p>Return to your application without requesting a new code.</p></div>
              <a href="{{ route('prototype.lite.password') }}">Set up password<i class="ti ti-arrow-right"></i></a>
            </section>
          @endif
        </section>
      @endif

      @if($authenticated)
        <x-prototype-bottom-nav :scenario="$scenario" :current="$screen === 'home' ? 'home' : 'application'" />
      @endif
    @endif

    <aside class="scenario-floater" aria-label="Prototype scenario controls">
      <button type="button" aria-label="Show current prototype scenario"><i class="ti ti-flask"></i></button>
      <div class="scenario-floater-panel"><span class="eyebrow">Scenario lab</span><strong>{{ $scenario['name'] }}</strong><a href="{{ route('prototype.scenarios') }}"><i class="ti ti-adjustments-horizontal"></i>Open builder</a></div>
    </aside>
  </main>
</div>
<script type="application/json" data-prototype-state>@json($appState)</script>
@endsection

@section('page-script')
<script src="{{ asset('assets/js/prototype-mobile.js') }}?v=20260902origination-lite"></script>
@endsection
