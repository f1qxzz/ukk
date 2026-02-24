// Perpustakaan Digital — script.js

function confirmDelete(msg){
  return confirm(msg || 'Apakah Anda yakin ingin menghapus data ini?');
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.alert').forEach(a=>{
    setTimeout(()=>{
      a.style.transition='opacity .5s';
      a.style.opacity='0';
      setTimeout(()=>a.remove(),500);
    },5000);
  });

  // Rating stars
  document.querySelectorAll('.star-input').forEach(star=>{
    star.addEventListener('click',function(){
      const val=this.dataset.value;
      document.getElementById('rating_value').value=val;
      document.querySelectorAll('.star-input').forEach(s=>{
        s.style.color=s.dataset.value<=val?'#f59e0b':'#ccc';
      });
    });
  });
});

function formatRp(n){ return 'Rp '+n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
