/**
 * Config
 * -------------------------------------------------------------------------------------
 * ! IMPORTANT: Make sure you clear the browser local storage In order to see the config changes in the template.
 * ! To clear local storage: (https://www.leadshook.com/help/how-to-clear-local-storage-in-google-chrome-browser/).
 */

'use strict';

// JS global variables
window.config = {
  colors: {
    primary: '#3D86D1',
    secondary: '#10284A',
    success: '#28c76f',
    info: '#00bad1',
    warning: '#ff9f43',
    danger: '#FF4C51',
    dark: '#10284A',
    black: '#000',
    white: '#fff',
    cardColor: '#fff',
    bodyBg: '#F6F8FA',
    bodyColor: '#10284A',
    headingColor: '#10284A',
    textMuted: '#6d6b77',
    borderColor: '#DDE3E8'
  },
  colors_label: {
    primary: '#3D86D129',
    secondary: '#10284A29',
    success: '#28c76f29',
    info: '#00cfe829',
    warning: '#ff9f4329',
    danger: '#ea545529',
    dark: '#4b4b4b29'
  },
  colors_dark: {
    cardColor: '#10284A',
    bodyBg: '#0b1c34',
    bodyColor: '#b2b1cb',
    headingColor: '#ffffff',
    textMuted: '#8285a0',
    borderColor: '#24415f'
  },
  enableMenuLocalStorage: true // Enable menu state with local storage support
};

window.assetsPath = document.documentElement.getAttribute('data-assets-path');
window.baseUrl = document.documentElement.getAttribute('data-base-url') + '/';
window.templateName = document.documentElement.getAttribute('data-template');
window.rtlSupport = true; // set true for rtl support (rtl + ltr), false for ltr only.
