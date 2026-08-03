document.addEventListener('DOMContentLoaded', function () {
  // Show the chosen filename next to Bootstrap custom file inputs.
  document.querySelectorAll('.custom-file-input').forEach(function (input) {
    input.addEventListener('change', function () {
      var label = input.nextElementSibling;
      if (label && input.files.length) {
        label.textContent = input.files[0].name;
      }
    });
  });

  // Confirm before any destructive (delete) form submission.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
