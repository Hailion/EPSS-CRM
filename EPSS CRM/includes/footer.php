<?php
// includes/footer.php
// Remove database connection code from here since it's already in db/db.php
$current_year = date('Y');
?>

<!-- Main content will be here -->
</div>
<div class="page-wrapper">

  <footer class="footer">
    <div class="footer-content">
      <p>&copy; <?php echo $current_year; ?> EPSS CRM. <?php echo t('all_rights_reserved'); ?></p>
    </div>
  </footer>
</div>

<style>
/* Reset default margins and ensure full height */
html,
body {
  height: 100%;
  margin: 0;
  padding: 0;
}

* {
  margin: 0;
  padding: 0;
}

/* Page wrapper to handle flex layout */
.page-wrapper {
  /* min-height: 50vh; */
  display: flex;
  flex-direction: column;

}

/* Main content area */
.main-content {
  flex: 1 0 auto;
  width: 100%;
}

/* Footer styles */
.footer {
  background: var(--primary-gradient);
  color: white;
  padding: 1rem;
  text-align: center;
  width: 100%;
  flex-shrink: 0;
  /* Prevent footer from shrinking */
}

.footer-content {
  max-width: 1400px;
  margin: 0 auto;
  font-size: 0.9rem;
}
</style>

</body>

</html>