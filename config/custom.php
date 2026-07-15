<?php

// Custom Config
// -------------------------------------------------------------------------------------
// IMPORTANT: Clear browser local storage after changing these values.

return [
  'custom' => [
    'myLayout' => 'vertical', // Options: vertical, horizontal

    'myTheme' => 'theme-default', // Options: theme-default, theme-bordered, theme-semi-dark

    'myStyle' => 'light', // Options: light, dark, system

    'myRTLSupport' => true,

    'myRTLMode' => false,

    'hasCustomizer' => true,

    'displayCustomizer' => true,

    'contentLayout' => 'compact', // Options: compact, wide

    'navbarType' => 'fixed', // Options supported by Helpers.php: fixed, static, hidden

    'footerFixed' => false,

    'menuFixed' => true,

    'menuCollapsed' => false,

    'headerType' => 'fixed', // Options: fixed, static

    'showDropdownOnHover' => true,

    'customizerControls' => [
      'rtl',
      'style',
      'headerType',
      'contentLayout',
      'layoutCollapsed',
      'layoutNavbarOptions',
      'themes',
    ],
  ],
];