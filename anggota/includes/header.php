<?php /* anggota/includes/header.php */
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'Portal Anggota · Perpustakaan Digital';
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
      <div class="topbar-avatar anggota"><?= strtoupper(substr(getAnggotaName(),0,1)) ?></div>
      <span class="topbar-username"><?= htmlspecialchars(getAnggotaName()) ?></span>
    </div>
  </div>
</header>
