<?php
// Public Footer - include dynamic elements from settings
require_once __DIR__ . '/config.php';
$settings = isset($GLOBALS['public_settings']) ? $GLOBALS['public_settings'] : [];
?>
<footer class="text-gray-800 py-8 footer-sticky" style="background:rgba(255,255,255,1); box-shadow:0 2px 8px rgba(0,0,0,0.05); border-radius: 1rem 1rem 0 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; width: 100%;">
<style>
.footer-sticky {
  position: relative;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
@media (min-height: 300px) {
  html, body {
    height: 100%;
    min-height: 100%;
    display: flex;
    flex-direction: column;
  }
  body {
    flex: 1 0 auto;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  .footer-sticky {
    margin-top: auto;
    width: 100%;
  }
}
</style>
    <div class="standard-container px-4 sm:px-6 lg:px-8">
        <p style="text-align: center;">
            <?php 
                $hakcipta = trim($settings['hakcipta'] ?? '');
                if ($hakcipta) {
                    echo $hakcipta;
                } else {
                    echo '&copy; ' . date('Y') . ' Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.';
                }
            ?>
        </p>
    </div>
</footer>
