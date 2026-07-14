<?php

namespace App\Http\Controllers\Templates;

use App\Http\Controllers\Controller;
use App\Support\TemplateLibrary;

class TemplateIndexController extends Controller
{
  public function __invoke()
  {
    return view('templates.index', [
      'templates' => TemplateLibrary::pages(),
      'categories' => TemplateLibrary::categories(),
    ]);
  }
}
