            </div><!-- End content-wrapper -->
        </main><!-- End main-content -->
    </div><!-- End app-wrapper -->

    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo-section">
                    <img src="../assets/favicon.png" alt="Logo" class="footer-logo-img">
                    <div class="footer-brand-info">
                        <h3 class="footer-brand-name">سیستم CRM</h3>
                        <p class="footer-brand-tagline">مدیریت حرفه‌ای ارتباط با مشتریان</p>
                    </div>
                </div>
                <p class="footer-description">
                    یک سیستم جامع برای مدیریت مشتریان، لیدها، فروش و وظایف با رابط کاربری مدرن و کاربرپسند
                </p>
                <div class="footer-stats">
                    <div class="footer-stat-item">
                        <i class="fas fa-users"></i>
                        <div class="footer-stat-info">
                            <span class="footer-stat-value">
                                <?php 
                                try {
                                    echo number_format($pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn());
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </span>
                            <span class="footer-stat-label">مشتری</span>
                        </div>
                    </div>
                    <div class="footer-stat-item">
                        <i class="fas fa-shopping-cart"></i>
                        <div class="footer-stat-info">
                            <span class="footer-stat-value">
                                <?php 
                                try {
                                    echo number_format($pdo->query("SELECT COUNT(*) FROM sales WHERE status != 'cancelled'")->fetchColumn());
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </span>
                            <span class="footer-stat-label">فروش</span>
                        </div>
                    </div>
                    <div class="footer-stat-item">
                        <i class="fas fa-tasks"></i>
                        <div class="footer-stat-info">
                            <span class="footer-stat-value">
                                <?php 
                                try {
                                    echo number_format($pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn());
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </span>
                            <span class="footer-stat-label">وظیفه انجام شده</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-center">
                <h4 class="footer-section-title">دسترسی سریع</h4>
                <div class="footer-links">
                    <div class="footer-link-column">
                        <a href="dashboard.php" class="footer-link">
                            <i class="fas fa-th-large"></i>
                            <span>داشبورد</span>
                        </a>
                        <a href="leads.php" class="footer-link">
                            <i class="fas fa-bullseye"></i>
                            <span>لیدها</span>
                        </a>
                        <a href="customers.php" class="footer-link">
                            <i class="fas fa-users"></i>
                            <span>مشتریان</span>
                        </a>
                        <a href="sales.php" class="footer-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>فروش</span>
                        </a>
                    </div>
                    <div class="footer-link-column">
                        <a href="tasks.php" class="footer-link">
                            <i class="fas fa-tasks"></i>
                            <span>وظایف</span>
                        </a>
                        <a href="products.php" class="footer-link">
                            <i class="fas fa-box"></i>
                            <span>محصولات</span>
                        </a>
                        <a href="reports.php" class="footer-link">
                            <i class="fas fa-chart-line"></i>
                            <span>گزارشات</span>
                        </a>
                        <a href="settings.php" class="footer-link">
                            <i class="fas fa-cog"></i>
                            <span>تنظیمات</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-right">
                <h4 class="footer-section-title">اطلاعات سیستم</h4>
                <div class="footer-info-grid">
                    <div class="footer-info-item">
                        <i class="fas fa-code-branch"></i>
                        <div class="footer-info-content">
                            <span class="footer-info-label">نسخه</span>
                            <span class="footer-info-value"><?php echo APP_VERSION ?? '1.0.0'; ?></span>
                        </div>
                    </div>
                    <div class="footer-info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="footer-info-content">
                            <span class="footer-info-label">تاریخ امروز</span>
                            <span class="footer-info-value">
                                <?php 
                                echo jdate('Y/m/d', time());
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="footer-info-item">
                        <i class="fas fa-clock"></i>
                        <div class="footer-info-content">
                            <span class="footer-info-label">ساعت</span>
                            <span class="footer-info-value" id="currentTime">
                                <?php echo date('H:i:s'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="footer-info-item">
                        <i class="fas fa-server"></i>
                        <div class="footer-info-content">
                            <span class="footer-info-label">وضعیت سرور</span>
                            <span class="footer-info-value">
                                <span class="status-indicator status-online"></span>
                                آنلاین
                            </span>
                        </div>
                    </div>
                </div>

                <div class="footer-developer">
                    <div class="footer-developer-header">
                        <i class="fas fa-code"></i>
                        <span>توسعه یافته توسط</span>
                    </div>
                    <a href="https://readystudio.ir/" target="_blank" rel="noopener noreferrer" class="footer-developer-link">
                        <div class="footer-developer-logo">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="footer-developer-info">
                            <span class="footer-developer-name">ردی استودیو</span>
                            <span class="footer-developer-url">readystudio.ir</span>
                        </div>
                        <i class="fas fa-external-link-alt footer-developer-icon"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="footer-copyright">
                    <i class="fas fa-copyright"></i>
                    <span>
                        <?php echo date('Y'); ?> 
                        تمامی حقوق محفوظ است
                    </span>
                </div>
                <div class="footer-bottom-links">
                    <a href="http://crm.readystudio.ir/terms" class="footer-bottom-link">
                        <i class="fas fa-file-contract"></i>
                        قوانین و مقررات
                    </a>
                    <a href="http://crm.readystudio.ir/policy" class="footer-bottom-link">
                        <i class="fas fa-shield-alt"></i>
                        حریم خصوصی
                    </a>
                    <a href="http://crm.readystudio.ir/support" class="footer-bottom-link">
                        <i class="fas fa-life-ring"></i>
                        پشتیبانی
                    </a>
                </div>
                <div class="footer-social">
                    <a href="#" class="footer-social-link" title="تلگرام">
                        <i class="fab fa-telegram"></i>
                    </a>
                    <a href="#" class="footer-social-link" title="اینستاگرام">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="footer-social-link" title="لینکدین">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="footer-social-link" title="واتساپ">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="scroll-to-top" title="بازگشت به بالا">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* ==================== FOOTER STYLES ==================== */
        .app-footer {
            background: linear-gradient(135deg, var(--brand-black) 0%, #1a1a1a 100%);
            color: white;
            margin-top: auto;
            margin-right: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: var(--transition-smooth);
        }

        @media (max-width: 992px) {
            .app-footer {
                margin-right: 0;
                width: 100%;
            }
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1.5fr;
            gap: 3rem;
            padding: 3rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 1200px) {
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
            }

            .footer-left {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem 1.5rem;
            }

            .footer-left,
            .footer-center,
            .footer-right {
                grid-column: 1;
            }
        }

        /* Footer Left Section */
        .footer-logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .footer-logo-img {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            background: var(--gradient-primary);
            padding: 10px;
            box-shadow: var(--shadow-brand);
        }

        .footer-brand-info {
            display: flex;
            flex-direction: column;
        }

        .footer-brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-brand-tagline {
            font-size: 0.875rem;
            color: var(--gray-400);
            margin: 0;
        }

        .footer-description {
            color: var(--gray-400);
            line-height: 1.8;
            margin-bottom: 2rem;
            font-size: 0.9375rem;
        }

        .footer-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 576px) {
            .footer-stats {
                grid-template-columns: 1fr;
            }
        }

        .footer-stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-base);
        }

        .footer-stat-item:hover {
            background: rgba(0, 176, 164, 0.1);
            border-color: var(--brand-primary);
            transform: translateY(-2px);
        }

        .footer-stat-item i {
            font-size: 1.5rem;
            color: var(--brand-primary);
        }

        .footer-stat-info {
            display: flex;
            flex-direction: column;
        }

        .footer-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }

        .footer-stat-label {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin-top: 0.25rem;
        }

        /* Footer Center Section */
        .footer-section-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .footer-section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 40px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }

        .footer-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 576px) {
            .footer-links {
                grid-template-columns: 1fr;
            }
        }

        .footer-link-column {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--gray-400);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition-base);
            font-size: 0.9375rem;
        }

        .footer-link:hover {
            color: var(--brand-primary-light);
            background: rgba(255, 255, 255, 0.05);
            padding-right: 1rem;
        }

        .footer-link i {
            font-size: 0.875rem;
            width: 20px;
            text-align: center;
        }

        /* Footer Right Section */
        .footer-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 576px) {
            .footer-info-grid {
                grid-template-columns: 1fr;
            }
        }

        .footer-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-info-item i {
            font-size: 1.25rem;
            color: var(--brand-primary);
        }

        .footer-info-content {
            display: flex;
            flex-direction: column;
        }

        .footer-info-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            line-height: 1;
        }

        .footer-info-value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: white;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: var(--radius-full);
            display: inline-block;
        }

        .status-online {
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Footer Developer Section */
        .footer-developer {
            padding: 1.5rem;
            background: rgba(0, 176, 164, 0.1);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(0, 176, 164, 0.2);
        }

        .footer-developer-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }

        .footer-developer-header i {
            color: var(--brand-primary);
        }

        .footer-developer-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: var(--transition-base);
            border: 2px solid transparent;
        }

        .footer-developer-link:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--brand-primary);
            transform: translateY(-2px);
        }

        .footer-developer-logo {
            width: 48px;
            height: 48px;
            background: var(--gradient-primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: var(--shadow-brand);
        }

        .footer-developer-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .footer-developer-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .footer-developer-url {
            font-size: 0.875rem;
            color: var(--brand-primary);
            margin-top: 0.25rem;
        }

        .footer-developer-icon {
            color: var(--gray-500);
            font-size: 1rem;
            transition: var(--transition-base);
        }

        .footer-developer-link:hover .footer-developer-icon {
            color: var(--brand-primary);
            transform: translate(-3px, -3px);
        }

        /* Footer Bottom */
        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
        }

        .footer-bottom-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .footer-bottom-content {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
        }

        .footer-copyright {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-400);
            font-size: 0.875rem;
        }

        .footer-copyright i {
            color: var(--brand-primary);
        }

        .footer-bottom-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        @media (max-width: 576px) {
            .footer-bottom-links {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        .footer-bottom-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition-base);
        }

        .footer-bottom-link:hover {
            color: var(--brand-primary-light);
        }

        .footer-bottom-link i {
            font-size: 0.75rem;
        }

        .footer-social {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .footer-social-link {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-sm);
            color: var(--gray-400);
            text-decoration: none;
            transition: var(--transition-base);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-social-link:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateY(-3px);
            border-color: var(--brand-primary);
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            width: 48px;
            height: 48px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-full);
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
            z-index: 998;
            box-shadow: var(--shadow-brand);
        }

        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0, 176, 164, 0.4);
        }

        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 1rem;
                left: 1rem;
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>

    <script>
        // Mobile Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (sidebar && toggle && window.innerWidth <= 992) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Scroll to Top Button
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });

        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Update Current Time
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        // Update time every second
        setInterval(updateTime, 1000);

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Active navigation link highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(function(link) {
                const linkPage = link.getAttribute('href');
                if (linkPage === currentPage) {
                    link.classList.add('active');
                }
            });
        });

        // Form validation enhancement
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
