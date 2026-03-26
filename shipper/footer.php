<footer class="footer">
        <div class="container-fluid">
          <div class="copyright float-right">
            &copy;
            <script>document.write(new Date().getFullYear())</script>
            Shipper Panel
          </div>
        </div>
      </footer>
    </div>
  </div>
  <!--   Core JS Files   -->
  <script src="../admin/assets/js/core/jquery.min.js"></script>
  <script src="../admin/assets/js/core/popper.min.js"></script>
  <script src="../admin/assets/js/core/bootstrap-material-design.min.js"></script>
  <script src="../admin/assets/js/plugins/perfect-scrollbar.jquery.min.js"></script>
  <script src="../admin/assets/js/plugins/bootstrap-notify.js"></script>
  <script src="../admin/assets/js/material-dashboard.js?v=2.1.0"></script>
  <script>
    $(document).ready(function() {
      $().ready(function() {
        $sidebar = $('.sidebar');
        $sidebar_img_container = $sidebar.find('.sidebar-background');
        window_width = $(window).width();
        $('.sidebar .sidebar-wrapper, .main-panel').perfectScrollbar();
      });
    });
  </script>
</body>
</html>
