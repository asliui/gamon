  </div>
  <script>
    (function () {
      const topbar = document.getElementById('topbar');
      const toggle = document.getElementById('navToggle');
      if (!topbar || !toggle) return;
      toggle.addEventListener('click', function () {
        const open = topbar.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    })();
  </script>
</body>
</html>

