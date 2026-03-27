/* Perpustakaan Digital — Script v2.0 */
document.addEventListener('DOMContentLoaded', () => {
  // Mobile sidebar toggle
  const sidebar = document.querySelector('.sidebar');
  const toggle  = document.querySelector('.sidebar-toggle');
  const overlay = document.querySelector('.sidebar-overlay');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show');
    });
    if (overlay) overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }
  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => { el.style.transition='opacity .4s'; el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 4500);
  });
  // Table live search
  const liveSearch = document.querySelector('[data-search-table]');
  if (liveSearch) {
    const table = document.getElementById(liveSearch.dataset.searchTable);
    liveSearch.addEventListener('input', () => {
      const q = liveSearch.value.toLowerCase();
      if (table) table.querySelectorAll('tbody tr').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q)?'':'none'; });
    });
  }
});
function showModal(id)  { const m=document.getElementById(id); if(m) m.style.display='flex'; }
function closeModal(id) { const m=document.getElementById(id); if(m) m.style.display='none'; }
function showReset(id, nama) {
  const el=document.getElementById('resetId'); const tl=document.getElementById('resetTitle');
  if(el) el.value=id; if(tl) tl.textContent='Reset Password: '+nama;
  showModal('resetModal');
}
