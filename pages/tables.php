<section class="container-fluid py-3">
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-bold">جدول داده نمونه</span>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary">خروجی CSV</button>
        <button class="btn btn-sm btn-primary">افزودن سطر</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle m-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>نام</th>
            <th>ایمیل</th>
            <th>نقش</th>
            <th>وضعیت</th>
            <th>عملیات</th>
          </tr>
        </thead>
        <tbody>
          
          <tr>
            <td>{{i}}</td>
            <td>کاربر {{i}}</td>
            <td>user{{i}}@example.com</td>
            <td>ادمین</td>
            <td><span class="badge text-bg-success">فعال</span></td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary">ویرایش</button>
                <button class="btn btn-outline-danger">حذف</button>
              </div>
            </td>
          </tr>
          
        </tbody>
      </table>
    </div>
  </div>
</section>
