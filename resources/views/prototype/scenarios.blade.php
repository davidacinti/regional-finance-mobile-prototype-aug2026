@php
$navbarView = 'layouts/sections/navbar/navbar-empty';
$footerView = 'layouts/sections/footer/footer-empty';
$isMenu = false;
$isNavbar = false;
$isFooter = false;
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Prototype Scenarios')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/prototype-mobile.css') }}">
@endsection

@section('content')
<main class="scenario-page">
  <header class="scenario-hero">
    <div>
      <span class="brand-mark">Regional</span>
      <h1>Mobile app scenarios</h1>
      <p>Select a mock customer state, then the mobile dashboard at <strong>/</strong> will render from that scenario.</p>
    </div>
    <a href="{{ route('prototype.index') }}" class="btn btn-outline-primary">Back to prototype</a>
  </header>

  @foreach($groups as $groupName => $scenarios)
    <section class="scenario-group">
      <h2>{{ $groupName }}</h2>
      <div class="scenario-grid">
        @foreach($scenarios as $id => $scenario)
          <article class="scenario-card {{ $activeScenarioId === $id ? 'active' : '' }}">
            <div class="card-heading">
              <span>{{ $scenario['name'] }}</span>
              @if($activeScenarioId === $id)
                <span class="status-pill success">Loaded</span>
              @endif
            </div>
            <p>{{ $scenario['description'] }}</p>
            <div class="module-list">
              @foreach($scenario['modules'] as $module)
                <span>{{ $module }}</span>
              @endforeach
            </div>
            <form method="POST" action="{{ route('prototype.scenarios.select', $id) }}">
              @csrf
              <button class="btn btn-primary w-100" type="submit">Load scenario</button>
            </form>
          </article>
        @endforeach
      </div>
    </section>
  @endforeach
</main>
@endsection
