<section class="container-fluid py-3">
  <div class="row g-3">
    <div class="col-12 col-md-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">نمودار خطی</div>
        <div class="card-body"><canvas id="demoLine" height="120"></canvas></div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">نمودار میله‌ای</div>
        <div class="card-body"><canvas id="demoBar" height="120"></canvas></div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">نمودار دونات</div>
        <div class="card-body"><canvas id="demoDoughnut" height="120"></canvas></div>
      </div>
    </div>
  </div>
</section>
<script>
  // Initialize sample charts for this page (uses global Chart from CDN)
  document.addEventListener('DOMContentLoaded', function(){
    const mk = (id, type, labels, data) => new Chart(document.getElementById(id), {
      type, data: { labels, datasets: [{ label: 'نمونه', data }] },
      options: { responsive:true, maintainAspectRatio:false }
    });
    mk('demoLine', 'line', ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور'], [10, 14, 12, 18, 9, 21]);
    mk('demoBar', 'bar',  ['A','B','C','D','E','F'], [5, 9, 7, 11, 6, 13]);
    mk('demoDoughnut', 'doughnut', ['A','B','C'], [35, 25, 40]);
  });
</script>
