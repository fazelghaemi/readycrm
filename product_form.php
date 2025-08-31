<?php
$product_id = (int)($_GET['id'] ?? 0);
$is_edit = $product_id > 0;
$page_title = $is_edit ? 'ویرایش محصول' : 'محصول جدید';

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// بررسی دسترسی
if (!hasPermission($is_edit ? 'edit_product' : 'add_product')) {
    setMessage('شما دسترسی لازم برای این عملیات را ندارید', 'error');
    header('Location: products.php');
    exit();
}

// دریافت محصول برای ویرایش
$product = null;
if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            setMessage('محصول یافت نشد', 'error');
            header('Location: products.php');
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("خطا در دریافت اطلاعات محصول: " . $e->getMessage());
        setMessage('خطا در بارگذاری اطلاعات محصول', 'error');
        header('Location: products.php');
        exit();
    }
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        setMessage('درخواست نامعتبر. لطفاً مجدداً تلاش کنید.', 'error');
    } else {
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cost_price = (float)($_POST['cost_price'] ?? 0);
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $min_stock_level = (int)($_POST['min_stock_level'] ?? 0);
        $unit = trim($_POST['unit'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $weight = (float)($_POST['weight'] ?? 0);
        $dimensions = trim($_POST['dimensions'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $tags = trim($_POST['tags'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        $errors = [];
        
        // اعتبارسنجی
        if (empty($name)) {
            $errors[] = 'نام محصول الزامی است';
        }
        
        if (empty($sku)) {
            $errors[] = 'کد محصول الزامی است';
        } else {
            // بررسی تکراری نبودن کد محصول
            $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $stmt->execute([$sku, $product_id]);
            if ($stmt->fetch()) {
                $errors[] = 'کد محصول تکراری است';
            }
        }
        
        if ($price < 0) {
            $errors[] = 'قیمت فروش نمی‌تواند منفی باشد';
        }
        
        if ($cost_price < 0) {
            $errors[] = 'قیمت خرید نمی‌تواند منفی باشد';
        }
        
        if ($stock_quantity < 0) {
            $errors[] = 'موجودی نمی‌تواند منفی باشد';
        }
        
        if ($barcode && !$is_edit) {
            // بررسی تکراری نبودن بارکد
            $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ? AND barcode != ''");
            $stmt->execute([$barcode]);
            if ($stmt->fetch()) {
                $errors[] = 'بارکد تکراری است';
            }
        }
        
        if (empty($errors)) {
            try {
                if ($is_edit) {
                    // بروزرسانی محصول
                    $stmt = $pdo->prepare("
                        UPDATE products SET 
                            name = ?, sku = ?, description = ?, category = ?, price = ?, cost_price = ?,
                            stock_quantity = ?, min_stock_level = ?, unit = ?, barcode = ?, weight = ?, 
                            dimensions = ?, status = ?, tags = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name, $sku, $description, $category, $price, $cost_price,
                        $stock_quantity, $min_stock_level, $unit, $barcode, $weight,
                        $dimensions, $status, $tags, $notes, $product_id
                    ]);
                    
                    $action = 'update';
                    $message = 'محصول با موفقیت بروزرسانی شد';
                    
                } else {
                    // ایجاد محصول جدید
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            name, sku, description, category, price, cost_price, stock_quantity,
                            min_stock_level, unit, barcode, weight, dimensions, status, tags, notes,
                            created_by, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $name, $sku, $description, $category, $price, $cost_price, $stock_quantity,
                        $min_stock_level, $unit, $barcode, $weight, $dimensions, $status, $tags, $notes,
                        $_SESSION['user_id']
                    ]);
                    
                    $product_id = $pdo->lastInsertId();
                    $action = 'create';
                    $message = 'محصول با موفقیت ثبت شد';
                }
                
                // ثبت فعالیت
                logActivity($_SESSION['user_id'], $action . '_product', 'products', $product_id, [
                    'name' => $name,
                    'sku' => $sku,
                    'price' => $price
                ]);
                
                setMessage($message, 'success');
                header('Location: product_view.php?id=' . $product_id);
                exit();
                
            } catch (PDOException $e) {
                error_log("خطا در ذخیره محصول: " . $e->getMessage());
                $errors[] = 'خطا در ذخیره اطلاعات';
            }
        }
        
        if (!empty($errors)) {
            setMessage(implode('<br>', $errors), 'error');
        }
    }
}

// دریافت دسته‌بندی‌های موجود
try {
    $categories = $pdo->query("
        SELECT DISTINCT category 
        FROM products 
        WHERE category IS NOT NULL AND category != '' 
        ORDER BY category
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

$csrf_token = generateCSRFToken();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?php echo $page_title; ?></h4>
        <p class="text-muted mb-0">
            <?php if ($is_edit): ?>
                ویرایش محصول <?php echo htmlspecialchars($product['name']); ?>
            <?php else: ?>
                افزودن محصول جدید به انبار
            <?php endif; ?>
        </p>
    </div>
    
    <div>
        <a href="products.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right me-2"></i>
            بازگشت
        </a>
    </div>
</div>

<?php displayMessage(); ?>

<form method="POST" id="productForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="row">
        <!-- اطلاعات اصلی -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        اطلاعات محصول
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 col-md-12 mb-3">
                            <label class="form-label required">نام محصول</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                                   placeholder="نام کامل محصول..."
                                   required>
                        </div>
                        
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="form-label required">کد محصول (SKU)</label>
                            <input type="text" class="form-control" name="sku" 
                                   value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" 
                                   placeholder="مثال: PRD-001"
                                   required>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">توضیحات محصول</label>
                            <textarea class="form-control" name="description" rows="4" 
                                      placeholder="توضیحات کامل در مورد محصول..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-lg-6 col-md-12 mb-3">
                            <label class="form-label">دسته‌بندی</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="category" 
                                       value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>" 
                                       placeholder="دسته‌بندی محصول..."
                                       list="categoryList">
                                <datalist id="categoryList">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <small class="text-muted">دسته‌بندی جدید تایپ کنید یا از لیست انتخاب کنید</small>
                        </div>
                        
                        <div class="col-lg-6 col-md-12 mb-3">
                            <label class="form-label">واحد شمارش</label>
                            <select class="form-select" name="unit">
                                <option value="">انتخاب واحد...</option>
                                <option value="عدد" <?php echo ($product['unit'] ?? '') === 'عدد' ? 'selected' : ''; ?>>عدد</option>
                                <option value="کیلوگرم" <?php echo ($product['unit'] ?? '') === 'کیلوگرم' ? 'selected' : ''; ?>>کیلوگرم</option>
                                <option value="گرم" <?php echo ($product['unit'] ?? '') === 'گرم' ? 'selected' : ''; ?>>گرم</option>
                                <option value="لیتر" <?php echo ($product['unit'] ?? '') === 'لیتر' ? 'selected' : ''; ?>>لیتر</option>
                                <option value="متر" <?php echo ($product['unit'] ?? '') === 'متر' ? 'selected' : ''; ?>>متر</option>
                                <option value="بسته" <?php echo ($product['unit'] ?? '') === 'بسته' ? 'selected' : ''; ?>>بسته</option>
                                <option value="جعبه" <?php echo ($product['unit'] ?? '') === 'جعبه' ? 'selected' : ''; ?>>جعبه</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-6 col-md-12 mb-3">
                            <label class="form-label">بارکد</label>
                            <input type="text" class="form-control" name="barcode" 
                                   value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>" 
                                   placeholder="بارکد محصول (اختیاری)">
                        </div>
                        
                        <div class="col-lg-6 col-md-12 mb-3">
                            <label class="form-label">وضعیت</label>
                            <select class="form-select" name="status" required>
                                <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>فعال</option>
                                <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>غیرفعال</option>
                                <option value="discontinued" <?php echo ($product['status'] ?? '') === 'discontinued' ? 'selected' : ''; ?>>متوقف شده</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">برچسب‌ها</label>
                            <input type="text" class="form-control" name="tags" 
                                   value="<?php echo htmlspecialchars($product['tags'] ?? ''); ?>" 
                                   placeholder="برچسب‌ها را با کاما از هم جدا کنید...">
                            <small class="text-muted">مثال: الکترونیک، موبایل، سامسونگ</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- مشخصات فیزیکی -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cube me-2 text-primary"></i>
                        مشخصات فیزیکی
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 col-md-12 mb-3">
                            <label class="form-label">وزن (گرم)</label>
                            <input type="number" class="form-control" name="weight" 
                                   value="<?php echo $product['weight'] ?? ''; ?>" 
                                   min="0" step="0.01"
                                   placeholder="وزن محصول به گرم">
                        </div>
                        
                        <div class="col-lg-6 col-md-12 mb-3">
                            <label class="form-label">ابعاد</label>
                            <input type="text" class="form-control" name="dimensions" 
                                   value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>" 
                                   placeholder="مثال: 10x20x5 سانتی‌متر">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- یادداشت‌ها -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note me-2 text-primary"></i>
                        یادداشت‌های داخلی
                    </h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="notes" rows="4" 
                              placeholder="یادداشت‌های داخلی که فقط کارکنان مشاهده می‌کنند..."><?php echo htmlspecialchars($product['notes'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- اطلاعات مالی و انبار -->
        <div class="col-lg-4">
            <!-- قیمت‌گذاری -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-dollar-sign me-2 text-primary"></i>
                        قیمت‌گذاری
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">قیمت فروش</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="price" 
                                   value="<?php echo $product['price'] ?? ''; ?>" 
                                   min="0" step="0.01" required
                                   onchange="calculateProfit()">
                            <span class="input-group-text">تومان</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">قیمت خرید/تولید</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="cost_price" 
                                   value="<?php echo $product['cost_price'] ?? ''; ?>" 
                                   min="0" step="0.01"
                                   onchange="calculateProfit()">
                            <span class="input-group-text">تومان</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="profitInfo" style="display: none;">
                        <small>
                            <strong>سود هر واحد:</strong> <span id="profitAmount">0</span> تومان<br>
                            <strong>درصد سود:</strong> <span id="profitPercent">0</span>%
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- مدیریت انبار -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-warehouse me-2 text-primary"></i>
                        مدیریت انبار
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">موجودی فعلی</label>
                        <input type="number" class="form-control" name="stock_quantity" 
                               value="<?php echo $product['stock_quantity'] ?? '0'; ?>" 
                               min="0" step="1"
                               onchange="checkStockLevel()">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">حداقل موجودی</label>
                        <input type="number" class="form-control" name="min_stock_level" 
                               value="<?php echo $product['min_stock_level'] ?? '5'; ?>" 
                               min="0" step="1"
                               onchange="checkStockLevel()">
                        <small class="text-muted">هشدار زمانی که موجودی کمتر از این مقدار شود</small>
                    </div>
                    
                    <div class="alert alert-warning" id="stockWarning" style="display: none;">
                        <small>
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            موجودی کمتر از حداقل مجاز است!
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- خلاصه محصول -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        خلاصه محصول
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h6 text-primary" id="totalValue">0</div>
                            <small class="text-muted">ارزش کل موجودی</small>
                        </div>
                        <div class="col-6">
                            <div class="h6 text-success" id="potentialProfit">0</div>
                            <small class="text-muted">سود احتمالی</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- دکمه‌های عملیات -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>
                            <?php echo $is_edit ? 'بروزرسانی محصول' : 'ثبت محصول'; ?>
                        </button>
                        
                        <?php if ($is_edit): ?>
                            <a href="product_view.php?id=<?php echo $product_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>
                                مشاهده محصول
                            </a>
                        <?php endif; ?>
                        
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>
                            لیست محصولات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function calculateProfit() {
    const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
    const costPrice = parseFloat(document.querySelector('input[name="cost_price"]').value) || 0;
    
    if (price > 0 && costPrice > 0) {
        const profit = price - costPrice;
        const profitPercent = ((profit / costPrice) * 100).toFixed(1);
        
        document.getElementById('profitAmount').textContent = formatMoney(profit);
        document.getElementById('profitPercent').textContent = profitPercent;
        document.getElementById('profitInfo').style.display = 'block';
    } else {
        document.getElementById('profitInfo').style.display = 'none';
    }
    
    calculateTotalValue();
}

function checkStockLevel() {
    const stock = parseInt(document.querySelector('input[name="stock_quantity"]').value) || 0;
    const minLevel = parseInt(document.querySelector('input[name="min_stock_level"]').value) || 0;
    
    if (stock > 0 && minLevel > 0 && stock <= minLevel) {
        document.getElementById('stockWarning').style.display = 'block';
    } else {
        document.getElementById('stockWarning').style.display = 'none';
    }
    
    calculateTotalValue();
}

function calculateTotalValue() {
    const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
    const costPrice = parseFloat(document.querySelector('input[name="cost_price"]').value) || 0;
    const stock = parseInt(document.querySelector('input[name="stock_quantity"]').value) || 0;
    
    const totalValue = costPrice * stock;
    const potentialProfit = (price - costPrice) * stock;
    
    document.getElementById('totalValue').textContent = formatMoney(totalValue);
    document.getElementById('potentialProfit').textContent = formatMoney(Math.max(0, potentialProfit));
}

function formatMoney(amount) {
    return new Intl.NumberFormat('fa-IR').format(Math.round(amount)) + ' تومان';
}

// محاسبه اولیه
document.addEventListener('DOMContentLoaded', function() {
    calculateProfit();
    checkStockLevel();
    calculateTotalValue();
});

// اعتبارسنجی فرم
document.getElementById('productForm').addEventListener('submit', function(e) {
    const name = document.querySelector('input[name="name"]').value.trim();
    const sku = document.querySelector('input[name="sku"]').value.trim();
    const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
    
    if (!name) {
        e.preventDefault();
        alert('نام محصول الزامی است');
        return false;
    }
    
    if (!sku) {
        e.preventDefault();
        alert('کد محصول الزامی است');
        return false;
    }
    
    if (price <= 0) {
        e.preventDefault();
        alert('قیمت فروش باید بیشتر از صفر باشد');
        return false;
    }
});
</script>

<style>
.required::after {
    content: ' *';
    color: var(--danger-color);
}

.card-header h5 {
    font-weight: 600;
}

.input-group-text {
    background: var(--bg-light);
    border-color: var(--border-color);
    color: var(--text-medium);
}

.alert-info {
    background: var(--info-light) !important;
    border-color: var(--info-color) !important;
    color: var(--info-color) !important;
}

.alert-warning {
    background: var(--warning-light) !important;
    border-color: var(--warning-color) !important;
    color: var(--warning-color) !important;
}

.h6 {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

datalist {
    display: none;
}
</style>

<?php include 'includes/footer.php'; ?>
