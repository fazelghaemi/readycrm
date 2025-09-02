document.addEventListener('DOMContentLoaded', function(){
  if (typeof Chart === 'undefined') return;
  const $line = document.getElementById('salesLineChart');
  const $doughnut = document.getElementById('salesDoughnut');
  // Global defaults (no specific colors to keep it theme-neutral)
  Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('--rs-font') || 'Poppins';

  if($line){
    new Chart($line, {
      type: 'line',
      data: {
        labels: ['7','6','5','4','3','2','1'],
        datasets: [{
          label: 'فروش روزانه',
          data: [12,18,9,24,20,28,26],
          tension: .35,
          fill: false
        }]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        scales:{ y:{ beginAtZero:true } }
      }
    });
  }

  if($doughnut){
    new Chart($doughnut, {
      type: 'doughnut',
      data: {
        labels: ['محصول A','محصول B','سرویس C'],
        datasets: [{ data: [45, 30, 25] }]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position: 'bottom' } },
        cutout:'60%'
      }
    });
  }
});
