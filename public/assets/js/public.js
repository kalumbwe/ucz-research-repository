document.addEventListener('DOMContentLoaded', function () {
  // Auto-submit the browse-page filter selects when changed, so
  // narrowing by department/category/year feels instant.
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () {
      el.closest('form').submit();
    });
  });
});
