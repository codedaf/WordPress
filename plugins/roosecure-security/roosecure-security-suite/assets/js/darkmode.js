
document.addEventListener('DOMContentLoaded', function() {
  const body = document.body;
  const toggle = document.getElementById('darkModeToggle');
  const dark = localStorage.getItem('roosecure_darkmode') === 'true';
  if (dark) body.classList.add('dark-mode');

  toggle.addEventListener('click', function() {
    body.classList.toggle('dark-mode');
    localStorage.setItem('roosecure_darkmode', body.classList.contains('dark-mode'));
  });
});
