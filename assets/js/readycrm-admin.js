(function(){
  const sidebar = document.querySelector('.rs-sidebar');
  const mainContent = document.querySelector('.main-content');
  const btn = document.getElementById('toggleSidebar');

  function showSidebar(show){
    if(window.innerWidth < 992){
      sidebar.classList.toggle('show', !!show);
    }else{
      // desktop: collapse/expand by padding
      const collapsed = mainContent.classList.toggle('expanded');
      sidebar.classList.toggle('collapsed', collapsed);
    }
  }

  btn && btn.addEventListener('click', function(e){
    e.preventDefault();
    showSidebar(true);
  });

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e){
    if(window.innerWidth >= 992) return;
    if(!sidebar.contains(e.target) && !btn.contains(e.target)){
      sidebar.classList.remove('show');
    }
  });
})();
