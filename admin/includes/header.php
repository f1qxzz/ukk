<?php /* admin/includes/header.php - Topbar */
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'Admin Panel · Perpustakaan Digital';
?>
<header class="topbar no-print">
  <div class="topbar-left">
    <div>
      <div class="page-title"><?= htmlspecialchars($page_title) ?></div>
      <div class="page-breadcrumb"><?= htmlspecialchars($page_sub) ?></div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-date">
      <?php date_default_timezone_set('Asia/Jakarta'); echo date('d M Y'); ?>
    </div>
    <div class="topbar-user">
      <div class="topbar-avatar admin"><?= strtoupper(substr(getPenggunaName(),0,1)) ?></div>
      <span class="topbar-username"><?= htmlspecialchars(getPenggunaName()) ?></span>
    </div>
  </div>
</header>
