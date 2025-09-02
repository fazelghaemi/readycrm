  <!-- Core JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Charts (optional) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <!-- App scripts (only if exists in your project) -->
  <?php if (file_exists(__DIR__ . '/../public/assets/js/app.js')): ?>
    <script src="public/assets/js/app.js"></script>
  <?php endif; ?>

  <!-- Ready Studio small helpers -->
  <script>
    // Example: sidebar toggler (if you have a button with #toggleSidebar and an .rs-sidebar)
    (function(){
      const btn = document.getElementById('toggleSidebar');
      const sidebar = document.querySelector('.rs-sidebar');
      if(btn && sidebar){
        btn.addEventListener('click', function(e){
          e.preventDefault();
          sidebar.classList.toggle('show');
        });
      }
    })();
  </script>
</body>
</html>
