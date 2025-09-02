<section class="container-fluid py-3">
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">فرم نمونه</div>
        <div class="card-body">
          <form class="row g-3">
            <div class="col-12">
              <label class="form-label">نام سازمان</label>
              <input type="text" class="form-control" placeholder="مثلاً Ready Studio" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">ایمیل</label>
              <input type="email" class="form-control" placeholder="name@example.com" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">موبایل</label>
              <input type="tel" class="form-control" placeholder="09xxxxxxxxx" />
            </div>
            <div class="col-12">
              <label class="form-label">پیام</label>
              <textarea class="form-control" rows="4" placeholder="توضیحات..."></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <button type="reset" class="btn btn-outline-secondary">پاک‌سازی</button>
              <button type="submit" class="btn btn-primary">ثبت</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">چک‌باکس و انتخاب‌ها</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">وضعیت</label>
              <select class="form-select">
                <option>فعال</option>
                <option>غیرفعال</option>
                <option>در انتظار</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">نقش کاربری</label>
              <select class="form-select">
                <option>ادمین</option>
                <option>کاربر</option>
                <option>مشاور</option>
              </select>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="news" checked>
                <label class="form-check-label" for="news">ارسال خبرنامه</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="terms">
                <label class="form-check-label" for="terms">قوانین و شرایط را می‌پذیرم</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
