-- ایجاد دیتابیس
CREATE DATABASE IF NOT EXISTS crm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm_system;

-- جدول کاربران
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    mobile VARCHAR(15),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    role ENUM('admin', 'manager', 'sales', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    department VARCHAR(100),
    position VARCHAR(100),
    hire_date DATE,
    salary DECIMAL(10,2),
    address TEXT,
    notes TEXT,
    last_login DATETIME,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول مشتریان
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(100),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    website VARCHAR(100),
    industry VARCHAR(50),
    customer_type ENUM('individual', 'company') DEFAULT 'individual',
    status ENUM('active', 'inactive', 'prospect') DEFAULT 'prospect',
    source VARCHAR(50),
    assigned_to INT,
    tags TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول لیدها
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    company VARCHAR(100),
    position VARCHAR(50),
    source VARCHAR(50),
    status ENUM('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    value DECIMAL(15,2) DEFAULT 0,
    probability INT DEFAULT 0,
    expected_close_date DATE,
    assigned_to INT,
    description TEXT,
    notes TEXT,
    tags TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول فعالیت‌ها و وظایف
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('call', 'email', 'meeting', 'follow_up', 'other') DEFAULT 'other',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    due_date DATETIME,
    completed_at DATETIME NULL,
    assigned_to INT,
    related_type ENUM('customer', 'lead', 'user') NULL,
    related_id INT NULL,
    reminder_datetime DATETIME NULL,
    is_reminder_sent BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول فعالیت‌های مشتریان
CREATE TABLE customer_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    activity_type ENUM('call', 'email', 'meeting', 'note', 'purchase', 'support') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT,
    activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    duration INT DEFAULT 0, -- مدت زمان به دقیقه
    outcome VARCHAR(100),
    next_action VARCHAR(200),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول محصولات/خدمات
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price DECIMAL(15,2) DEFAULT 0,
    cost_price DECIMAL(15,2) DEFAULT 0,
    sku VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    unit VARCHAR(50) DEFAULT 'عدد',
    barcode VARCHAR(100),
    weight DECIMAL(10,3) DEFAULT 0,
    dimensions VARCHAR(100),
    tags TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول فروش/سفارشات
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    lead_id INT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    shipping_amount DECIMAL(15,2) DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'transfer', 'check', 'installment'),
    sale_date DATETIME NOT NULL,
    delivery_date DATE NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول آیتم‌های فروش
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- جدول پرداخت‌ها
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(20) UNIQUE NOT NULL,
    sale_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'transfer', 'cheque', 'other') NOT NULL,
    payment_date DATE NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    status ENUM('pending', 'confirmed', 'failed', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول تنظیمات سیستم
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول لاگ‌ها
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON NULL,
    new_values JSON NULL,
    details TEXT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول فایل‌ها
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    related_type ENUM('customer', 'lead', 'task', 'sale', 'user') NOT NULL,
    related_id INT NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ایجاد ایندکس‌ها برای بهینه‌سازی
CREATE INDEX idx_customers_email ON customers(email);
CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_customers_assigned_to ON customers(assigned_to);
CREATE INDEX idx_customers_status ON customers(status);

CREATE INDEX idx_leads_email ON leads(email);
CREATE INDEX idx_leads_phone ON leads(phone);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_assigned_to ON leads(assigned_to);

CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);

CREATE INDEX idx_sales_customer_id ON sales(customer_id);
CREATE INDEX idx_sales_status ON sales(status);
CREATE INDEX idx_sales_sale_date ON sales(sale_date);

CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

-- درج داده‌های اولیه
INSERT INTO users (username, email, password, first_name, last_name, role, mobile, phone, department, position, hire_date, salary, address, notes, status, created_at) VALUES
('admin', 'admin@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'علی', 'احمدی', 'admin', '09121234567', '02188776655', 'مدیریت', 'مدیر کل', '2020-01-15', 25000000.00, 'تهران، ولیعصر، پلاک 123', 'مدیر کل سیستم', 'active', NOW()),
('manager', 'manager@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'سارا', 'محمدی', 'manager', '09129876543', '02188776656', 'فروش', 'مدیر فروش', '2021-03-10', 18000000.00, 'تهران، انقلاب، پلاک 456', 'مدیر بخش فروش', 'active', NOW()),
('sales1', 'sales@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'رضا', 'کریمی', 'sales', '09112345678', '02188776657', 'فروش', 'کارشناس فروش', '2022-06-20', 12000000.00, 'تهران، کریمخان، پلاک 789', 'کارشناس فروش ارشد', 'active', NOW()),
('user1', 'user@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مریم', 'حسینی', 'user', '09198765432', '02188776658', 'پشتیبانی', 'کارشناس پشتیبانی', '2023-01-05', 10000000.00, 'تهران، شریعتی، پلاک 321', 'کارشناس پشتیبانی مشتریان', 'active', NOW());

-- مشتریان تستی
INSERT INTO customers (customer_code, first_name, last_name, company_name, customer_type, email, phone, mobile, address, city, postal_code, status, created_by, created_at) VALUES
('CUS001', 'احمد', 'رضایی', NULL, 'individual', 'ahmad@email.com', '02112345678', '09123456789', 'خیابان ولیعصر، پلاک 123', 'تهران', '1234567890', 'active', 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),
('CUS002', 'فاطمه', 'علوی', NULL, 'individual', 'fateme@email.com', '02187654321', '09198765432', 'خیابان انقلاب، پلاک 456', 'تهران', '0987654321', 'active', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
('CUS003', 'شرکت', 'فناوری پارس', 'شرکت فناوری پارس', 'company', 'info@parstech.ir', '02155667788', '09155667788', 'خیابان شریعتی، برج میلاد', 'تهران', '1122334455', 'active', 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
('CUS004', 'حسن', 'کریمی', NULL, 'individual', 'hassan@email.com', '02133445566', '09133445566', 'خیابان کریمخان، پلاک 789', 'تهران', '5566778899', 'active', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
('CUS005', 'شرکت', 'بازرگانی آریا', 'شرکت بازرگانی آریا', 'company', 'contact@arya.com', '02144556677', '09144556677', 'میدان آزادی، ساختمان تجاری', 'تهران', '6677889900', 'active', 1, DATE_SUB(NOW(), INTERVAL 15 DAY));

-- لیدهای تستی  
INSERT INTO leads (title, first_name, last_name, company, email, phone, source, status, priority, description, assigned_to, created_by, created_at) VALUES
('مدیر فروش', 'مهدی', 'نوری', 'شرکت تکنولوژی نوین', 'mehdi@novin.com', '09121112233', 'website', 'new', 'high', 'علاقه‌مند به خرید سیستم CRM', 3, 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
('کارشناس IT', 'زهرا', 'صادقی', 'شرکت داده پردازی', 'zahra@dataproc.ir', '09134445566', 'phone', 'contacted', 'medium', 'نیاز به راهکار مدیریت مشتری', 3, 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
('مدیر عامل', 'کامران', 'احمدی', 'گروه صنعتی البرز', 'kamran@alborz.com', '09167778899', 'email', 'qualified', 'high', 'درخواست دمو محصول', 3, 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
('مدیر بازاریابی', 'لیلا', 'محمدی', 'شرکت بازرگانی پارس', 'leila@pars.com', '09155443322', 'social', 'proposal', 'medium', 'جلسه برای ارائه قیمت', 3, 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
('مدیر فنی', 'امین', 'کریمی', 'شرکت نرم‌افزاری رایان', 'amin@rayan.ir', '09188776655', 'referral', 'won', 'low', 'قرارداد منعقد شده', 3, 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- محصولات تستی
INSERT INTO products (name, sku, description, category, price, cost_price, stock_quantity, min_stock_level, unit, barcode, weight, dimensions, status, tags, notes, created_by, created_at) VALUES
('سیستم CRM حرفه‌ای', 'CRM-PRO-001', 'سیستم مدیریت ارتباط با مشتری برای شرکت‌های بزرگ', 'نرم‌افزار', 5000000.00, 2500000.00, 10, 2, 'لایسنس', '1234567890123', 0.000, 'دیجیتال', 'active', 'CRM,مدیریت,مشتری', 'محصول پرفروش', 1, NOW()),
('ماژول گزارش‌گیری', 'RPT-MOD-002', 'ماژول گزارش‌گیری پیشرفته برای CRM', 'ماژول', 1500000.00, 750000.00, 25, 5, 'لایسنس', '2345678901234', 0.000, 'دیجیتال', 'active', 'گزارش,تحلیل', 'ماژول اضافی', 1, NOW()),
('خدمات پیاده‌سازی', 'SRV-IMP-003', 'خدمات پیاده‌سازی و راه‌اندازی سیستم', 'خدمات', 3000000.00, 1200000.00, 100, 10, 'ساعت', '3456789012345', 0.000, 'خدماتی', 'active', 'پیاده‌سازی,راه‌اندازی', 'خدمات تخصصی', 1, NOW()),
('پشتیبانی سالانه', 'SUP-YRL-004', 'پشتیبانی و نگهداری سالانه سیستم', 'پشتیبانی', 800000.00, 300000.00, 50, 5, 'قرارداد', '4567890123456', 0.000, 'خدماتی', 'active', 'پشتیبانی,نگهداری', 'قرارداد سالانه', 1, NOW()),
('آموزش کاربران', 'TRN-USR-005', 'دوره آموزشی کاربران سیستم CRM', 'آموزش', 1200000.00, 400000.00, 20, 3, 'دوره', '5678901234567', 0.000, 'آموزشی', 'active', 'آموزش,کاربران', 'آموزش عملی', 1, NOW());

-- وظایف تستی
INSERT INTO tasks (title, description, assigned_to, priority, status, due_date, related_type, related_id, completed_at, created_by, created_at) VALUES
('تماس با مشتری جدید', 'تماس اولیه با مشتری برای شناخت نیازها', 3, 'high', 'pending', DATE_ADD(NOW(), INTERVAL 2 DAY), 'customer', 1, NULL, 1, NOW()),
('ارسال پیشنهاد قیمت', 'تهیه و ارسال پیشنهاد قیمت برای پروژه CRM', 3, 'medium', 'in_progress', DATE_ADD(NOW(), INTERVAL 5 DAY), 'lead', 2, NULL, 1, NOW()),
('دمو محصول', 'برگزاری جلسه دمو برای نمایش امکانات', 2, 'high', 'pending', DATE_ADD(NOW(), INTERVAL 3 DAY), 'lead', 3, NULL, 1, NOW()),
('پیگیری قرارداد', 'پیگیری وضعیت امضای قرارداد', 3, 'medium', 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), 'customer', 3, DATE_SUB(NOW(), INTERVAL 1 DAY), 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('بررسی نیازمندی‌ها', 'تحلیل دقیق نیازمندی‌های فنی مشتری', 4, 'low', 'in_progress', DATE_ADD(NOW(), INTERVAL 7 DAY), 'lead', 4, NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- فروش‌های تستی
INSERT INTO sales (sale_number, customer_id, lead_id, sale_date, subtotal, total_amount, tax_amount, discount_amount, shipping_amount, final_amount, status, payment_status, payment_method, notes, created_by, created_at) VALUES
('S240001', 1, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), 5800000.00, 5800000.00, 450000.00, 200000.00, 0.00, 6050000.00, 'confirmed', 'paid', 'transfer', 'پرداخت کامل انجام شده', 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('S240002', 3, 3, DATE_SUB(NOW(), INTERVAL 5 DAY), 8000000.00, 8000000.00, 720000.00, 500000.00, 0.00, 8220000.00, 'confirmed', 'paid', 'transfer', 'فروش بزرگ', 3, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('S240003', 2, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), 1500000.00, 1500000.00, 135000.00, 0.00, 0.00, 1635000.00, 'delivered', 'paid', 'cash', 'تحویل کامل', 3, DATE_SUB(NOW(), INTERVAL 8 DAY));

-- آیتم‌های فروش تستی
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 1, 5000000.00, 5000000.00),
(1, 4, 1, 800000.00, 800000.00),
(2, 1, 1, 5000000.00, 5000000.00),
(2, 3, 1, 3000000.00, 3000000.00),
(3, 2, 1, 1500000.00, 1500000.00);

-- پرداخت‌های تستی
INSERT INTO payments (payment_number, sale_id, amount, payment_method, payment_date, reference_number, notes, status, created_by, created_at) VALUES
('PAY001', 1, 6050000.00, 'transfer', CURDATE(), 'TXN123456789', 'پرداخت کامل فاکتور', 'confirmed', 3, NOW()),
('PAY002', 2, 8220000.00, 'transfer', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'CARD987654321', 'پرداخت کامل', 'confirmed', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('PAY003', 3, 1635000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'پرداخت نقدی', 'confirmed', 3, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- لاگ‌های فعالیت تستی
INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) VALUES
(1, 'create_customer', 'customers', 1, NULL, '{"name":"احمد رضایی","email":"ahmad@email.com"}', '127.0.0.1', 'Mozilla/5.0', NOW()),
(3, 'create_sale', 'sales', 1, NULL, '{"sale_number":"S240001","amount":6050000}', '127.0.0.1', 'Mozilla/5.0', NOW()),
(1, 'update_lead', 'leads', 3, '{"status":"qualified"}', '{"status":"won"}', '127.0.0.1', 'Mozilla/5.0', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 'create_task', 'tasks', 3, NULL, '{"title":"دمو محصول","priority":"high"}', '127.0.0.1', 'Mozilla/5.0', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'update_task', 'tasks', 4, '{"status":"in_progress"}', '{"status":"completed"}', '127.0.0.1', 'Mozilla/5.0', DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- تنظیمات سیستم
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'شرکت سیستم مدیریت ارتباط پارس', 'string', 'نام شرکت'),
('company_phone', '021-88776655', 'string', 'تلفن شرکت'),
('company_email', 'info@parscrm.ir', 'string', 'ایمیل شرکت'),
('company_address', 'تهران، میدان ولیعصر، برج میلاد، طبقه 15', 'string', 'آدرس شرکت'),
('tax_rate', '9', 'integer', 'نرخ مالیات (درصد)'),
('currency', 'تومان', 'string', 'واحد پول'),
('records_per_page', '20', 'integer', 'تعداد رکورد در هر صفحه');