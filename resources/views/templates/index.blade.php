@extends('layouts/contentNavbarLayout')

@section('title', 'Template Library')

@section('content')
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
      <div>
        <h4 class="mb-1">Template Library</h4>
        <p class="text-muted mb-0">{{ count($templates) }} Vuexy reference pages available under /templates.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <input id="templateSearch" type="search" class="form-control" placeholder="Search templates" aria-label="Search templates">
        <select id="templateCategory" class="form-select" aria-label="Filter by category">
          <option value="">All categories</option>
          @foreach ($categories as $category)
            <option value="{{ $category }}">{{ $category }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Category</th>
          <th>Route</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="templateRows">
        @foreach ($templates as $template)
          <tr data-name="{{ strtolower($template['name']) }}" data-category="{{ $template['category'] }}">
            <td>{{ $template['name'] }}</td>
            <td>{{ $template['category'] }}</td>
            <td><code>{{ $template['route'] }}</code></td>
            <td><a href="{{ $template['url'] }}" class="btn btn-sm btn-primary">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
  const templateSearch = document.getElementById('templateSearch');
  const templateCategory = document.getElementById('templateCategory');
  const templateRows = Array.from(document.querySelectorAll('#templateRows tr'));

  function filterTemplates() {
    const search = templateSearch.value.trim().toLowerCase();
    const category = templateCategory.value;

    templateRows.forEach(row => {
      const matchesSearch = !search || row.textContent.toLowerCase().includes(search);
      const matchesCategory = !category || row.dataset.category === category;
      row.hidden = !(matchesSearch && matchesCategory);
    });
  }

  templateSearch.addEventListener('input', filterTemplates);
  templateCategory.addEventListener('change', filterTemplates);
</script>
@endsection
