<section class="container-fluid py-3">
  <div class="row g-3">
    <!-- Cards -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card rs-card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="card-subtitle text-muted small mb-1">مشتریان</div>
            <div class="h4 m-0 fw-bold">1,248</div>
          </div>
          <div class="rs-card-icon rs-bg-primary-soft">
            <i class="fa-solid fa-user-group"></i>
          </div>
        </div>
        <div class="card-footer small text-muted">+12% نسبت به ماه قبل</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card rs-card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="card-subtitle text-muted small mb-1">فروش ماه</div>
            <div class="h4 m-0 fw-bold">‎‌‎₮ 84,500,000</div>
          </div>
          <div class="rs-card-icon rs-bg-success-soft">
            <i class="fa-solid fa-sack-dollar"></i>
          </div>
        </div>
        <div class="card-footer small text-muted">+6.4% رشد</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card rs-card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="card-subtitle text-muted small mb-1">سرنخ‌های جدید</div>
            <div class="h4 m-0 fw-bold">312</div>
          </div>
          <div class="rs-card-icon rs-bg-warning-soft">
            <i class="fa-solid fa-bullseye"></i>
          </div>
        </div>
        <div class="card-footer small text-muted">-2.1% نسبت به هفته قبل</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card rs-card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="card-subtitle text-muted small mb-1">وظایف باز</div>
            <div class="h4 m-0 fw-bold">27</div>
          </div>
          <div class="rs-card-icon rs-bg-info-soft">
            <i class="fa-solid fa-list-check"></i>
          </div>
        </div>
        <div class="card-footer small text-muted">4 مورد امروز سررسید می‌شود</div>
      </div>
    </div>
  </div>

  <!-- Charts + Table -->
  <div class="row g-3 mt-1">
    <div class="col-12 col-xxl-8">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold">نمودار فروش ۳۰ روز اخیر</span>
          <div class="btn-group btn-group-sm" role="group" aria-label="period">
            <button class="btn btn-outline-secondary active">30 روز</button>
            <button class="btn btn-outline-secondary">90 روز</button>
            <button class="btn btn-outline-secondary">سال</button>
          </div>
        </div>
        <div class="card-body">
          <canvas id="salesLineChart" height="110"></canvas>
        </div>
      </div>
    </div>
    <div class="col-12 col-xxl-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <span class="fw-bold">ترکیب فروش</span>
        </div>
        <div class="card-body">
          <canvas id="salesDoughnut" height="220"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold">آخرین سفارش‌ها</span>
          <a class="btn btn-sm btn-primary" href="#">همه سفارش‌ها</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle m-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>مشتری</th>
                <th>محصول</th>
                <th>مبلغ</th>
                <th>وضعیت</th>
                <th>تاریخ</th>
                <th>عملیات</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1001</td>
                <td>شرکت آلفا</td>
                <td>پکیج CRM پیشرفته</td>
                <td>‎‌‎₮ 12,900,000</td>
                <td><span class="badge text-bg-success">پرداخت‌شده</span></td>
                <td>1404/06/09</td>
                <td><button class="btn btn-sm btn-outline-primary">نمایش</button></td>
              </tr>
              <tr>
                <td>1002</td>
                <td>گروه نگین</td>
                <td>تمدید اشتراک</td>
                <td>‎‌‎₮ 2,400,000</td>
                <td><span class="badge text-bg-warning">در انتظار</span></td>
                <td>1404/06/08</td>
                <td><button class="btn btn-sm btn-outline-primary">نمایش</button></td>
              </tr>
              <tr>
                <td>1003</td>
                <td>فناوران سپهر</td>
                <td>ماژول اتوماسیون</td>
                <td>‎‌‎₮ 6,800,000</td>
                <td><span class="badge text-bg-danger">برگشت</span></td>
                <td>1404/06/08</td>
                <td><button class="btn btn-sm btn-outline-primary">نمایش</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
