<?php /* admin/includes/header.php */
$page_title = $page_title ?? 'Dashboard';
$page_sub   = $page_sub   ?? 'Admin Panel · Perpustakaan Digital';
?>
<header class="topbar no-print">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open');document.querySelector('.sidebar-overlay').classList.toggle('show')">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
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
    <a href="logout.php" class="btn btn-ghost btn-sm no-print" style="color:var(--danger)">
      <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</header>
