window.config = window.config || {};
const isDark = document.documentElement.classList.contains('dark-style');

window.config.colors = {
  ...(window.config.colors || {}),
  primary: '#3D86D1',
  secondary: '#10284A',
  dark: '#10284A',
  white: '#fff',
  cardColor: isDark ? '#10284A' : '#fff',
  bodyBg: isDark ? '#071A31' : '#F6F8FA',
  bodyColor: isDark ? '#DDE3E8' : '#10284A',
  headingColor: isDark ? '#FFFFFF' : '#10284A',
  textMuted: isDark ? '#A9B7C6' : '#6d6b77',
  borderColor: isDark ? '#294765' : '#DDE3E8'
};

window.config.colors_label = {
  ...(window.config.colors_label || {}),
  primary: '#3D86D129',
  secondary: '#10284A29'
};
