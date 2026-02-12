<?php
/**
 * ReadyCRM - Footer (Minimal + Optimized Chatbot + Inline SVG Icons)
 * File: /private/footer.php
 *
 * Goals:
 * - Minimal height footer (no big cards / descriptions).
 * - Inline SVG icons (accurate, no FontAwesome dependency in footer/chat UI).
 * - Improved chatbot UI/UX (copy, expand, persistence, better RTL, lighter CSS).
 * - No duplicate </body></html>, no early return that breaks layout.
 */

if (!function_exists('rcm_escape')) {
    function rcm_escape($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Inline SVG icon set (Lucide-inspired, RTL-friendly).
 * Usage: echo rcm_svg('grid', 18, 'class-name');
 */
if (!function_exists('rcm_svg')) {
    function rcm_svg(string $name, int $size = 18, string $class = '', string $extraAttrs = ''): string {
        $s = max(12, min(28, (int)$size));
        $cls = $class ? ' class="'.rcm_escape($class).'"' : '';
        $attrs = trim($extraAttrs);
        if ($attrs !== '') $attrs = ' '.$attrs;

        $base = 'width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

        $paths = [
            // UI / App
            'grid' => '<path d="M3 3h8v8H3z"/><path d="M13 3h8v8h-8z"/><path d="M3 13h8v8H3z"/><path d="M13 13h8v8h-8z"/>',
            'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M12 2v3"/><path d="M22 12h-3"/><path d="M12 22v-3"/><path d="M2 12h3"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
            'checklist' => '<path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><path d="M4 6l1 1 2-2"/><path d="M4 12l1 1 2-2"/><path d="M4 18l1 1 2-2"/>',
            'gear' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.03.03a2 2 0 0 1-1.42 3.42h-.07a1.7 1.7 0 0 0-1.66 1.2 2 2 0 0 1-3.86 0 1.7 1.7 0 0 0-1.66-1.2h-.07a2 2 0 0 1-1.42-3.42l.03-.03A1.7 1.7 0 0 0 4.6 15a2 2 0 0 1 0-6 1.7 1.7 0 0 0-.34-1.87l-.03-.03A2 2 0 0 1 5.65 3.7h.07A1.7 1.7 0 0 0 7.38 2.5a2 2 0 0 1 3.86 0 1.7 1.7 0 0 0 1.66 1.2h.07a2 2 0 0 1 1.42 3.42l-.03.03A1.7 1.7 0 0 0 19.4 9a2 2 0 0 1 0 6z"/>',

            // Meta / Legal
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/>',
            'lifebuoy' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="M4.9 4.9l4.2 4.2"/><path d="M14.9 14.9l4.2 4.2"/><path d="M19.1 4.9l-4.2 4.2"/><path d="M9.1 14.9l-4.2 4.2"/>',
            'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
            'dot' => '<circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/>',

            // Chat / actions
            'chat' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 5V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h8"/><path d="M8 14h5"/>',
            'x' => '<path d="M18 6 6 18"/><path d="M6 6l12 12"/>',
            'send' => '<path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2z"/>',
            'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
            'expand' => '<path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3 14 10"/><path d="M3 21l7-7"/>',
            'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'spark' => '<path d="M12 2l1.8 5.6H20l-4.6 3.3 1.8 5.6L12 13.7 6.8 16.5 8.6 11 4 7.6h6.2z"/>',

            // Social (simple, compact, fill icons)
            'telegram' => '<path fill="currentColor" stroke="none" d="M21.9 4.6c.2-1-.7-1.8-1.7-1.4L2.9 9.9c-1 .4-1 1.8.1 2.1l4.5 1.4 1.7 5.5c.3 1 1.6 1.2 2.2.4l2.6-3.2 5.1 3.8c.8.6 2 .1 2.2-.9l2.3-14.4zM8.3 12.8l9.9-6.2c.2-.1.4.2.2.4l-8.2 7.6-.3 3.4-1.3-4.2-3.5-1.1c-.3-.1-.3-.4 0-.5l15.1-5.8-11.9 7.4z"/>',
            'instagram' => '<rect x="7" y="7" width="10" height="10" rx="3" /><path d="M16.5 7.5h.01"/><circle cx="12" cy="12" r="2.5"/><rect x="3" y="3" width="18" height="18" rx="5" />',
            'linkedin' => '<path d="M6 9H3v12h3z"/><path d="M4.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/><path d="M21 21h-3v-6.2c0-1.5-.5-2.5-1.8-2.5-1 0-1.6.7-1.9 1.3-.1.3-.1.8-.1 1.3V21h-3s.04-10.5 0-12h3v1.7c.4-.6 1.2-1.5 3-1.5 2.2 0 3.8 1.4 3.8 4.5V21z"/>',
            'whatsapp' => '<path fill="currentColor" stroke="none" d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.9-1.4A10 10 0 1 0 12 2zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-2.9.8.8-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.7-5.7c-.3-.1-1.6-.8-1.9-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-1 .9-.2.1-.4.1-.7-.1-.3-.1-1.2-.4-2.3-1.4-.8-.7-1.4-1.6-1.6-1.9-.2-.3 0-.5.1-.6l.5-.6c.1-.1.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.7-1.7-1-2.4-.3-.7-.6-.6-.7-.6h-.6c-.2 0-.5.1-.7.3-.2.2-1 1-1 2.5s1.1 3 1.2 3.2c.1.2 2.1 3.2 5.2 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.6-.7 1.9-1.3.2-.6.2-1.1.1-1.3-.1-.1-.3-.2-.6-.3z"/>',
        ];

        $content = $paths[$name] ?? $paths['dot'];
        return '<svg '.$base.$cls.$attrs.' aria-hidden="true" focusable="false">'.$content.'</svg>';
    }
}

/* ------------------------------------------------------------------ */
/* Data (settings + user)                                              */
/* ------------------------------------------------------------------ */
$rcm_is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? null;

$can_manager = in_array($user_role, ['admin', 'manager'], true);
$can_admin   = ($user_role === 'admin');

$company_name = 'سیستم CRM';
$company_phone = '';
$company_email = '';
$company_address = '';

$app_version = defined('APP_VERSION') ? (string)APP_VERSION : '1.0.0';
$today_fa = date('Y/m/d');
if (function_exists('jdate')) {
    try { $today_fa = jdate('Y/m/d', time()); } catch (Throwable $e) {}
}

$server_online = true;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $keys = ['company_name','company_phone','company_email','company_address','chatbot_enabled'];
        $in  = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($in)");
        $stmt->execute($keys);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) $map[$r['setting_key']] = $r['setting_value'];

        if (!empty($map['company_name']))    $company_name = $map['company_name'];
        if (!empty($map['company_phone']))   $company_phone = $map['company_phone'];
        if (!empty($map['company_email']))   $company_email = $map['company_email'];
        if (!empty($map['company_address'])) $company_address = $map['company_address'];

        // health check
        $pdo->query("SELECT 1");
        $server_online = true;
    } catch (Throwable $e) {
        $server_online = false;
    }
} else {
    $server_online = false;
}

/* Chatbot enabled? (default true if setting absent) */
$chatbot_enabled = false;
if ($rcm_is_logged_in) {
    $chatbot_enabled = true;
    if (isset($map) && array_key_exists('chatbot_enabled', $map)) {
        $chatbot_enabled = ((string)$map['chatbot_enabled'] === '1');
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'chatbot_enabled' LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row && isset($row['setting_value'])) $chatbot_enabled = ((string)$row['setting_value'] === '1');
        } catch (Throwable $e) {}
    }
}

/* Footer quick links (minimal) */
$footer_links = [
    ['href'=>'dashboard.php', 'label'=>'داشبورد', 'icon'=>'grid', 'show'=>true],
    ['href'=>'leads.php',     'label'=>'لیدها',   'icon'=>'target', 'show'=>true],
    ['href'=>'customers.php', 'label'=>'مشتریان', 'icon'=>'users', 'show'=>true],
    ['href'=>'sales.php',     'label'=>'فروش',    'icon'=>'cart', 'show'=>true],
    ['href'=>'tasks.php',     'label'=>'وظایف',   'icon'=>'checklist', 'show'=>true],
    ['href'=>'reports.php',   'label'=>'گزارشات', 'icon'=>'grid', 'show'=>$can_manager],
    ['href'=>'settings.php',  'label'=>'تنظیمات', 'icon'=>'gear', 'show'=>$can_admin],
];

$legal_links = [
    ['href'=>'http://crm.readystudio.ir/terms',  'label'=>'قوانین',      'icon'=>'file'],
    ['href'=>'http://crm.readystudio.ir/policy', 'label'=>'حریم خصوصی', 'icon'=>'shield'],
    ['href'=>'http://crm.readystudio.ir/support','label'=>'پشتیبانی',   'icon'=>'lifebuoy'],
];

$social_links = [
    ['href'=>'https://t.me/studioready',                          'title'=>'تلگرام',    'icon'=>'telegram'],
    ['href'=>'https://www.instagram.com/readystudio.ir',          'title'=>'اینستاگرام','icon'=>'instagram'],
    ['href'=>'https://www.linkedin.com/in/ready-studio-a79611231/','title'=>'لینکدین',   'icon'=>'linkedin'],
    ['href'=>'https://wa.me/+989392116387',                       'title'=>'واتساپ',    'icon'=>'whatsapp'],
];

/* Optional per-page script hooks */
$page_scripts   = (isset($page_scripts) && is_array($page_scripts)) ? $page_scripts : [];
$page_inline_js = (isset($page_inline_js) && is_string($page_inline_js)) ? $page_inline_js : '';

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
?>

            </div><!-- End content-wrapper -->
        </main><!-- End main-content -->
    </div><!-- End app-wrapper -->

    <!-- ==================== MINIMAL FOOTER ==================== -->
    <style>
        /* Minimal footer: short height, no big cards */
        .app-footer{
            background: linear-gradient(135deg, var(--brand-black) 0%, #101214 100%);
            color: rgba(255,255,255,.85);
            margin-right: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: var(--transition-smooth);
            border-top: 1px solid rgba(255,255,255,.08);
        }
        @media (max-width: 992px){
            .app-footer{ margin-right:0; width:100%; }
        }

        .footer-mini{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 1rem;
            padding: 14px 18px;
            flex-wrap: wrap;
        }

        .footer-brand{
            display:flex;
            align-items:center;
            gap: 10px;
            min-width: 220px;
        }
        .footer-logo{
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: var(--gradient-primary);
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow: var(--shadow-brand);
            overflow:hidden;
        }
        .footer-logo img{ width: 22px; height: 22px; object-fit: contain; }
        .footer-brand-title{
            display:flex;
            flex-direction:column;
            line-height:1.2;
        }
        .footer-brand-title strong{
            font-weight: 900;
            font-size: .95rem;
            color:#fff;
        }
        .footer-brand-title span{
            font-size: .78rem;
            color: rgba(255,255,255,.55);
        }

        .footer-nav{
            display:flex;
            align-items:center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content:center;
        }
        .footer-link{
            display:flex;
            align-items:center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 12px;
            text-decoration:none;
            color: rgba(255,255,255,.72);
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            transition: var(--transition-base);
            font-size: .86rem;
            white-space: nowrap;
        }
        .footer-link:hover{
            color:#fff;
            background: rgba(20,184,166,.12);
            border-color: rgba(20,184,166,.35);
            transform: translateY(-1px);
        }
        .footer-link svg{ opacity:.9 }

        .footer-meta{
            display:flex;
            align-items:center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content:flex-end;
            min-width: 260px;
        }

        .footer-chip{
            display:flex;
            align-items:center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            color: rgba(255,255,255,.75);
            font-size: .82rem;
        }

        .footer-dot{
            width: 8px; height:8px; border-radius: 999px;
            background: var(--success);
            box-shadow: 0 0 10px rgba(0,200,83,.35);
        }
        .footer-dot.offline{
            background: var(--danger);
            box-shadow: 0 0 10px rgba(244,67,54,.35);
        }

        .footer-social{
            display:flex;
            align-items:center;
            gap: 8px;
        }
        .footer-social a{
            width: 36px; height:36px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius: 12px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            color: rgba(255,255,255,.75);
            transition: var(--transition-base);
            text-decoration:none;
        }
        .footer-social a:hover{
            background: var(--gradient-primary);
            color: #fff;
            border-color: rgba(20,184,166,.55);
            transform: translateY(-1px);
        }

        .footer-bottomline{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 10px;
            padding: 10px 18px 14px;
            color: rgba(255,255,255,.50);
            font-size: .78rem;
            border-top: 1px solid rgba(255,255,255,.06);
            flex-wrap: wrap;
        }
        .footer-bottomline a{
            color: rgba(255,255,255,.58);
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap: 6px;
            padding: 4px 6px;
            border-radius: 10px;
            transition: var(--transition-base);
        }
        .footer-bottomline a:hover{
            color:#fff;
            background: rgba(255,255,255,.06);
        }

        /* keep footer compact on very small */
        @media (max-width: 576px){
            .footer-brand{ min-width: unset; width:100%; }
            .footer-meta{ min-width: unset; width:100%; justify-content:space-between; }
            .footer-bottomline{ justify-content:center; text-align:center; }
        }
    </style>

    <footer class="app-footer" role="contentinfo">
        <div class="footer-mini">
            <!-- Brand -->
            <div class="footer-brand">
                <div class="footer-logo" title="<?php echo rcm_escape($company_name); ?>">
                    <img src="../assets/favicon.png" alt="Logo">
                </div>
                <div class="footer-brand-title">
                    <strong><?php echo rcm_escape($company_name); ?></strong>
                    <span>نسخه <?php echo rcm_escape($app_version); ?> • <?php echo rcm_escape($today_fa); ?></span>
                </div>
            </div>

            <!-- Quick links (minimal) -->
            <nav class="footer-nav" aria-label="دسترسی سریع">
                <?php foreach ($footer_links as $l): if (!$l['show']) continue; ?>
                    <a class="footer-link" href="<?php echo rcm_escape($l['href']); ?>" title="<?php echo rcm_escape($l['label']); ?>">
                        <?php echo rcm_svg($l['icon'], 18); ?>
                        <span><?php echo rcm_escape($l['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Meta -->
            <div class="footer-meta">
                <div class="footer-chip" title="وضعیت سرویس">
                    <span class="footer-dot <?php echo $server_online ? '' : 'offline'; ?>"></span>
                    <span><?php echo $server_online ? 'آنلاین' : 'آفلاین'; ?></span>
                </div>

                <div class="footer-social" aria-label="شبکه‌های اجتماعی">
                    <?php foreach ($social_links as $s): ?>
                        <a href="<?php echo rcm_escape($s['href']); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo rcm_escape($s['title']); ?>">
                            <?php
                                // instagram/linkedin are stroke icons; telegram/whatsapp are fill icons; both ok in currentColor
                                echo rcm_svg($s['icon'], 18, '', ($s['icon']==='telegram' || $s['icon']==='whatsapp') ? '' : '');
                            ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottomline">
            <div>
                <?php echo rcm_escape(date('Y')); ?> © تمامی حقوق محفوظ است
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:center;">
                <?php foreach ($legal_links as $l): ?>
                    <a href="<?php echo rcm_escape($l['href']); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo rcm_escape($l['label']); ?>">
                        <?php echo rcm_svg($l['icon'], 16); ?>
                        <span><?php echo rcm_escape($l['label']); ?></span>
                    </a>
                <?php endforeach; ?>

                <a href="https://readystudio.ir/" target="_blank" rel="noopener noreferrer" title="توسعه‌دهنده">
                    <?php echo rcm_svg('external', 16); ?>
                    <span>ردی استودیو</span>
                </a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle (required for tooltips/alerts) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ==================== GLOBAL APP JS (light, safe) ==================== -->
    <script>
        (function(){
            'use strict';

            // Auto-dismiss alerts (except .alert-permanent)
            document.addEventListener('DOMContentLoaded', function(){
                if (!window.bootstrap) return;
                document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert){
                    setTimeout(function(){
                        try {
                            bootstrap.Alert.getOrCreateInstance(alert).close();
                        } catch(e){}
                    }, 5000);
                });
            });

            // Active nav link highlight (sidebar)
            document.addEventListener('DOMContentLoaded', function(){
                const current = (window.location.pathname.split('/').pop() || '').toLowerCase();
                document.querySelectorAll('.nav-link').forEach(function(link){
                    const href = (link.getAttribute('href') || '').toLowerCase();
                    if (href === current) link.classList.add('active');
                });
            });

            // Bootstrap form validation enhancement
            (function(){
                const forms = document.querySelectorAll('.needs-validation');
                Array.from(forms).forEach(function(form){
                    form.addEventListener('submit', function(event){
                        if (!form.checkValidity()){
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();

        })();
    </script>

    <!-- ==================== OPTIONAL PER-PAGE SCRIPTS ==================== -->
    <?php foreach ($page_scripts as $src): if (!is_string($src) || trim($src)==='') continue; ?>
        <script src="<?php echo rcm_escape($src); ?>"></script>
    <?php endforeach; ?>

    <?php if (trim($page_inline_js) !== ''): ?>
        <script><?php echo $page_inline_js; ?></script>
    <?php endif; ?>

    <!-- ==================== AI SETTINGS JS (ONLY settings.php) ==================== -->
    <?php
        // اگر این فایل برای بخش تنظیمات AI داری، فقط در صفحه تنظیمات لود شود
        $ai_js_path = __DIR__ . '/settings_ai_functions.js';
        if ($current_page === 'settings.php' && is_file($ai_js_path)) {
            echo "<script>\n";
            readfile($ai_js_path);
            echo "\n</script>\n";
        }
    ?>

    <!-- ==================== AI CHATBOT COPILOT (Optimized) ==================== -->
    <?php if ($chatbot_enabled): ?>
        <style>
            :root{
                --rcm-cb-teal: var(--brand-primary);
                --rcm-cb-teal2: #0d9488;
                --rcm-cb-bg: #f7fbfb;
                --rcm-cb-card: #ffffff;
                --rcm-cb-border: #e6eef0;
                --rcm-cb-text: #0f172a;
                --rcm-cb-muted: #64748b;
                --rcm-cb-shadow: 0 10px 30px rgba(0,0,0,.14), 0 2px 8px rgba(0,0,0,.08);
                --rcm-cb-radius: 18px;
                --rcm-cb-ease: cubic-bezier(.4,0,.2,1);
            }

            /* FAB */
            #rcm-cb-fab{
                position: fixed;
                bottom: 18px;
                left: 18px;
                width: 56px; height: 56px;
                border-radius: 999px;
                border: 0;
                cursor: pointer;
                z-index: 9998;
                color:#fff;
                background: linear-gradient(135deg, var(--rcm-cb-teal) 0%, var(--rcm-cb-teal2) 100%);
                box-shadow: 0 10px 24px rgba(20,184,166,.35);
                display:flex;align-items:center;justify-content:center;
                transition: transform .2s var(--rcm-cb-ease), box-shadow .2s var(--rcm-cb-ease);
                outline: none;
            }
            #rcm-cb-fab:hover{ transform: translateY(-2px) scale(1.04); box-shadow: 0 14px 30px rgba(20,184,166,.45); }
            #rcm-cb-fab .i{ position:absolute; transition: opacity .2s, transform .2s; }
            #rcm-cb-fab.open .i-chat{ opacity:0; transform: scale(.7) rotate(-10deg); }
            #rcm-cb-fab.open .i-x{ opacity:1; transform: scale(1); }
            #rcm-cb-fab:not(.open) .i-chat{ opacity:1; transform: scale(1); }
            #rcm-cb-fab:not(.open) .i-x{ opacity:0; transform: scale(.7) rotate(10deg); }

            #rcm-cb-badge{
                position:absolute;
                top: 2px; right: 2px;
                width: 16px; height: 16px;
                border-radius: 999px;
                background: #ef4444;
                color:#fff;
                font-size: 10px;
                font-weight: 900;
                display:flex;align-items:center;justify-content:center;
                border:2px solid #fff;
                opacity:0;
                transform: scale(.5);
                transition: all .2s var(--rcm-cb-ease);
            }
            #rcm-cb-badge.show{ opacity:1; transform: scale(1); }

            /* Window */
            #rcm-cb-window{
                position: fixed;
                bottom: 86px;
                left: 18px;
                width: 360px;
                max-width: calc(100vw - 36px);
                height: 520px;
                max-height: calc(100vh - 110px);
                border-radius: var(--rcm-cb-radius);
                background: var(--rcm-cb-card);
                border: 1px solid var(--rcm-cb-border);
                box-shadow: var(--rcm-cb-shadow);
                overflow: hidden;
                z-index: 9999;
                opacity:0;
                transform: translateY(10px) scale(.98);
                pointer-events:none;
                transition: all .22s var(--rcm-cb-ease);
                display:flex;
                flex-direction:column;
            }
            #rcm-cb-window.show{
                opacity:1;
                transform: translateY(0) scale(1);
                pointer-events:auto;
            }
            #rcm-cb-window.expand{
                width: min(520px, calc(100vw - 36px));
                height: min(740px, calc(100vh - 110px));
            }

            /* Header */
            .rcm-cb-header{
                background: linear-gradient(135deg, var(--rcm-cb-teal) 0%, var(--rcm-cb-teal2) 100%);
                color:#fff;
                padding: 12px 12px;
                display:flex;
                align-items:center;
                gap: 10px;
            }
            .rcm-cb-avatar{
                width: 36px; height:36px;
                border-radius: 14px;
                background: rgba(255,255,255,.18);
                display:flex;align-items:center;justify-content:center;
                flex-shrink:0;
            }
            .rcm-cb-title{
                display:flex; flex-direction:column;
                line-height:1.2;
                flex:1;
                font-family: 'YekanBakh', sans-serif;
            }
            .rcm-cb-title strong{ font-size: .95rem; font-weight: 900; }
            .rcm-cb-title span{ font-size: .75rem; opacity:.85; display:flex;align-items:center;gap:6px; }
            .rcm-cb-dot{
                width: 7px; height:7px;
                border-radius:999px;
                background:#4ade80;
                box-shadow: 0 0 10px rgba(74,222,128,.45);
            }

            .rcm-cb-actions{
                display:flex;
                align-items:center;
                gap: 6px;
            }
            .rcm-cb-iconbtn{
                width: 34px; height:34px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,.22);
                background: rgba(255,255,255,.14);
                color:#fff;
                display:flex;align-items:center;justify-content:center;
                cursor:pointer;
                transition: transform .15s var(--rcm-cb-ease), background .15s var(--rcm-cb-ease);
            }
            .rcm-cb-iconbtn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.22); }

            /* Messages */
            #rcm-cb-messages{
                flex:1;
                background: var(--rcm-cb-bg);
                padding: 12px;
                overflow:auto;
                display:flex;
                flex-direction:column;
                gap: 10px;
                scroll-behavior:smooth;
            }
            #rcm-cb-messages::-webkit-scrollbar{ width: 4px; }
            #rcm-cb-messages::-webkit-scrollbar-thumb{ background: #d7e6e8; border-radius: 999px; }

            .rcm-cb-msg{ display:flex; gap: 8px; align-items:flex-end; max-width:100%; }
            .rcm-cb-msg.user{ flex-direction: row-reverse; }
            .rcm-cb-bubble{
                max-width: 78%;
                padding: 10px 12px;
                border-radius: 16px;
                font-family: 'YekanBakh', sans-serif;
                font-size: 13.5px;
                line-height: 1.7;
                direction: rtl;
                word-break: break-word;
                position: relative;
            }
            .rcm-cb-msg.bot .rcm-cb-bubble{
                background:#fff;
                border: 1px solid var(--rcm-cb-border);
                color: var(--rcm-cb-text);
                border-bottom-right-radius: 6px;
            }
            .rcm-cb-msg.user .rcm-cb-bubble{
                background: linear-gradient(135deg, var(--rcm-cb-teal) 0%, var(--rcm-cb-teal2) 100%);
                color:#fff;
                border-bottom-left-radius: 6px;
            }
            .rcm-cb-meta{
                font-size: 10px;
                color: rgba(100,116,139,.75);
                margin-top: 6px;
                display:flex;
                align-items:center;
                gap: 8px;
            }
            .rcm-cb-msg.user .rcm-cb-meta{ color: rgba(255,255,255,.75); justify-content:flex-end; }
            .rcm-cb-mini-btn{
                border: 0;
                background: transparent;
                color: inherit;
                opacity:.85;
                padding: 2px 4px;
                border-radius: 8px;
                cursor:pointer;
            }
            .rcm-cb-mini-btn:hover{ background: rgba(0,0,0,.06); opacity:1; }
            .rcm-cb-msg.user .rcm-cb-mini-btn:hover{ background: rgba(255,255,255,.12); }

            /* Typing */
            .rcm-cb-typing{
                display:inline-flex;
                align-items:center;
                gap: 4px;
                padding: 8px 10px;
                background:#fff;
                border: 1px solid var(--rcm-cb-border);
                border-radius: 14px;
                border-bottom-right-radius: 6px;
            }
            .rcm-cb-typing span{
                width: 6px; height: 6px;
                border-radius: 999px;
                background: var(--rcm-cb-teal);
                animation: rcmCbB .9s infinite;
                opacity:.5;
            }
            .rcm-cb-typing span:nth-child(2){ animation-delay: .15s; }
            .rcm-cb-typing span:nth-child(3){ animation-delay: .30s; }
            @keyframes rcmCbB{
                0%,60%,100%{ transform: translateY(0); opacity:.45 }
                30%{ transform: translateY(-4px); opacity:1 }
            }

            /* Quick actions (compact, collapsible) */
            #rcm-cb-qa{
                display:flex;
                gap: 6px;
                flex-wrap: wrap;
                padding: 10px 12px;
                background: #fff;
                border-top: 1px solid var(--rcm-cb-border);
            }
            .rcm-cb-chip{
                border: 1px solid rgba(20,184,166,.25);
                background: rgba(20,184,166,.08);
                color: #0f766e;
                font-family:'YekanBakh', sans-serif;
                font-weight: 800;
                font-size: 12px;
                padding: 6px 10px;
                border-radius: 999px;
                cursor:pointer;
                transition: all .15s var(--rcm-cb-ease);
                white-space: nowrap;
            }
            .rcm-cb-chip:hover{ background: rgba(20,184,166,.16); transform: translateY(-1px); }

            /* Input */
            .rcm-cb-input{
                display:flex;
                gap: 8px;
                padding: 10px 12px;
                background:#fff;
                border-top: 1px solid var(--rcm-cb-border);
            }
            #rcm-cb-text{
                flex:1;
                border-radius: 14px;
                border: 1.5px solid var(--rcm-cb-border);
                background: #f9fbfb;
                padding: 10px 12px;
                font-family:'YekanBakh', sans-serif;
                font-size: 13.5px;
                line-height: 1.5;
                outline:none;
                resize:none;
                max-height: 110px;
                min-height: 42px;
                direction: rtl;
                transition: border-color .15s var(--rcm-cb-ease), box-shadow .15s var(--rcm-cb-ease), background .15s var(--rcm-cb-ease);
            }
            #rcm-cb-text:focus{
                border-color: rgba(20,184,166,.8);
                box-shadow: 0 0 0 3px rgba(20,184,166,.18);
                background:#fff;
            }
            #rcm-cb-send{
                width: 44px; height:44px;
                border-radius: 14px;
                border: 0;
                cursor:pointer;
                color:#fff;
                background: linear-gradient(135deg, var(--rcm-cb-teal) 0%, var(--rcm-cb-teal2) 100%);
                display:flex; align-items:center; justify-content:center;
                transition: transform .15s var(--rcm-cb-ease), opacity .15s var(--rcm-cb-ease);
            }
            #rcm-cb-send:hover{ transform: translateY(-1px) scale(1.02); }
            #rcm-cb-send:disabled{ opacity:.45; cursor:not-allowed; transform:none; }

            /* Toast */
            #rcm-cb-toast{
                position:absolute;
                bottom: 78px;
                left: 12px;
                right: 12px;
                margin:auto;
                background: rgba(15,23,42,.92);
                color:#fff;
                padding: 10px 12px;
                border-radius: 14px;
                font-size: 12px;
                font-family:'YekanBakh', sans-serif;
                opacity:0;
                transform: translateY(6px);
                transition: all .2s var(--rcm-cb-ease);
                pointer-events:none;
            }
            #rcm-cb-toast.show{ opacity:1; transform: translateY(0); }

            /* Mobile */
            @media (max-width: 480px){
                #rcm-cb-window{
                    left: 10px;
                    right: 10px;
                    width: auto;
                    bottom: 78px;
                    height: calc(100vh - 100px);
                    max-height: none;
                }
                #rcm-cb-fab{
                    left: 12px;
                    bottom: 12px;
                    width: 54px; height: 54px;
                }
            }

            @media (prefers-reduced-motion: reduce){
                *{ transition:none!important; animation:none!important; scroll-behavior:auto!important; }
            }
        </style>

        <button id="rcm-cb-fab" aria-label="چت‌بات" title="دستیار AI">
            <span class="i i-chat"><?php echo rcm_svg('chat', 22); ?></span>
            <span class="i i-x"><?php echo rcm_svg('x', 22); ?></span>
            <span style="position:absolute;top:6px;left:6px;opacity:.85;"><?php echo rcm_svg('spark', 14); ?></span>
            <span id="rcm-cb-badge">1</span>
        </button>

        <section id="rcm-cb-window" dir="rtl" role="dialog" aria-label="دستیار هوش مصنوعی CRM">
            <div class="rcm-cb-header">
                <div class="rcm-cb-avatar"><?php echo rcm_svg('spark', 18); ?></div>
                <div class="rcm-cb-title">
                    <strong>کوپیلات CRM</strong>
                    <span><span class="rcm-cb-dot"></span><span id="rcm-cb-status">آماده پاسخگویی</span></span>
                </div>
                <div class="rcm-cb-actions">
                    <button class="rcm-cb-iconbtn" id="rcm-cb-new" title="مکالمه جدید"><?php echo rcm_svg('plus', 18); ?></button>
                    <button class="rcm-cb-iconbtn" id="rcm-cb-expand" title="بزرگ/کوچک"><?php echo rcm_svg('expand', 18); ?></button>
                </div>
            </div>

            <div id="rcm-cb-messages">
                <div class="rcm-cb-msg bot">
                    <div class="rcm-cb-bubble">
                        سلام 👋 من دستیار CRM هستم. می‌تونی از چیپ‌های زیر استفاده کنی یا سوالتو بنویسی.
                        <div class="rcm-cb-meta">
                            <span><?php echo rcm_escape(date('H:i')); ?></span>
                            <button class="rcm-cb-mini-btn" data-copy="سلام 👋 من دستیار CRM هستم."> <?php echo rcm_svg('copy', 14); ?> </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="rcm-cb-qa">
                <button class="rcm-cb-chip" data-msg="آمار کلی CRM رو بهم بگو">📊 آمار کلی</button>
                <button class="rcm-cb-chip" data-msg="لیدهای فوری امروز رو لیست کن">🔥 لیدهای فوری</button>
                <button class="rcm-cb-chip" data-msg="وظایف امروز من چیه؟">✅ وظایف امروز</button>
                <button class="rcm-cb-chip" data-msg="فروش این ماه چقدره؟">💰 فروش ماه</button>
            </div>

            <div class="rcm-cb-input">
                <textarea id="rcm-cb-text" rows="1" maxlength="1200" placeholder="پیام بنویس… (Enter=ارسال، Shift+Enter=خط جدید)"></textarea>
                <button id="rcm-cb-send" disabled title="ارسال"><?php echo rcm_svg('send', 18); ?></button>
            </div>

            <div id="rcm-cb-toast">کپی شد ✅</div>
        </section>

        <script>
            (function(){
                'use strict';

                const fab   = document.getElementById('rcm-cb-fab');
                const win   = document.getElementById('rcm-cb-window');
                const badge = document.getElementById('rcm-cb-badge');
                const msgs  = document.getElementById('rcm-cb-messages');
                const qa    = document.getElementById('rcm-cb-qa');
                const text  = document.getElementById('rcm-cb-text');
                const send  = document.getElementById('rcm-cb-send');
                const status= document.getElementById('rcm-cb-status');
                const btnNew= document.getElementById('rcm-cb-new');
                const btnEx = document.getElementById('rcm-cb-expand');
                const toast = document.getElementById('rcm-cb-toast');

                const state = {
                    open: false,
                    loading: false,
                    sessionId: localStorage.getItem('rcm_cb_session') || null,
                    expanded: localStorage.getItem('rcm_cb_expand') === '1'
                };

                // API endpoint resolver: /public/*.php uses same dir endpoint
                const CHAT_API = (function(){
                    const p = window.location.pathname || '';
                    if (p.includes('/public/')) return 'chatbot_api.php';
                    return '../public/chatbot_api.php';
                })();

                function showToast(message){
                    if (!toast) return;
                    toast.textContent = message || 'انجام شد ✅';
                    toast.classList.add('show');
                    setTimeout(()=>toast.classList.remove('show'), 1400);
                }

                function scrollBottom(){
                    requestAnimationFrame(()=>{ msgs.scrollTop = msgs.scrollHeight; });
                }

                function escHtml(str){
                    return String(str)
                        .replace(/&/g,'&amp;')
                        .replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;')
                        .replace(/\n/g,'<br>');
                }

                function formatText(str){
                    // escape first, then minimal markdown-ish
                    return escHtml(str)
                        .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
                        .replace(/\*(.+?)\*/g,'<em>$1</em>')
                        .replace(/`(.+?)`/g,'<code>$1</code>');
                }

                function nowTime(){
                    try { return new Date().toLocaleTimeString('fa-IR',{hour:'2-digit',minute:'2-digit'}); }
                    catch(e){ return ''; }
                }

                function appendMsg(type, rawText){
                    const isUser = type === 'user';
                    const t = nowTime();
                    const safeText = isUser ? escHtml(rawText) : formatText(rawText);

                    const wrap = document.createElement('div');
                    wrap.className = 'rcm-cb-msg ' + (isUser ? 'user' : 'bot');

                    wrap.innerHTML = `
                        <div class="rcm-cb-bubble">
                            ${safeText}
                            <div class="rcm-cb-meta">
                                <span>${t}</span>
                                <button class="rcm-cb-mini-btn" data-copy="${rcmAttr(rawText)}" title="کپی">
                                    ${copyIconSvg()}
                                </button>
                            </div>
                        </div>
                    `;
                    msgs.appendChild(wrap);
                    scrollBottom();
                }

                function showTyping(){
                    const wrap = document.createElement('div');
                    wrap.className = 'rcm-cb-msg bot';
                    wrap.id = 'rcm-cb-typing';
                    wrap.innerHTML = `<div class="rcm-cb-typing"><span></span><span></span><span></span></div>`;
                    msgs.appendChild(wrap);
                    scrollBottom();
                }

                function hideTyping(){
                    const t = document.getElementById('rcm-cb-typing');
                    if (t) t.remove();
                }

                function toggle(open){
                    state.open = (typeof open === 'boolean') ? open : !state.open;
                    fab.classList.toggle('open', state.open);
                    win.classList.toggle('show', state.open);

                    if (state.open){
                        badge.classList.remove('show');
                        localStorage.setItem('rcm_cb_open','1');
                        setTimeout(()=>text.focus(), 80);
                    } else {
                        localStorage.setItem('rcm_cb_open','0');
                    }
                }

                function setExpanded(flag){
                    state.expanded = !!flag;
                    win.classList.toggle('expand', state.expanded);
                    localStorage.setItem('rcm_cb_expand', state.expanded ? '1' : '0');
                }

                function rcmAttr(str){
                    // safe attribute
                    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                }

                function copyIconSvg(){
                    // inline svg for copy (small) - keep as string to avoid DOM parsing overhead
                    return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>`;
                }

                async function apiCall(action, data){
                    const form = new FormData();
                    form.append('action', action);
                    if (state.sessionId) form.append('session_id', state.sessionId);
                    if (data){
                        Object.keys(data).forEach(k => form.append(k, data[k]));
                    }

                    const ctrl = new AbortController();
                    const to = setTimeout(()=>ctrl.abort(), 30000);

                    const res = await fetch(CHAT_API, {
                        method: 'POST',
                        body: form,
                        signal: ctrl.signal
                    });

                    clearTimeout(to);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return await res.json();
                }

                async function ensureSession(){
                    if (state.sessionId) return;
                    try{
                        const r = await apiCall('new_session');
                        if (r && r.session_id){
                            state.sessionId = r.session_id;
                            localStorage.setItem('rcm_cb_session', state.sessionId);
                        }
                    }catch(e){
                        // silent
                    }
                }

                async function sendMessage(textValue){
                    const msg = (textValue ?? text.value).trim();
                    if (!msg || state.loading) return;

                    state.loading = true;
                    send.disabled = true;
                    text.value = '';
                    text.style.height = 'auto';
                    if (status) status.textContent = 'در حال پردازش…';

                    appendMsg('user', msg);
                    showTyping();

                    try{
                        await ensureSession();
                        const r = await apiCall('send_message', { message: msg });

                        hideTyping();

                        if (r && r.success && r.response){
                            appendMsg('bot', r.response);
                            // update quick actions if provided
                            if (r.quick_actions && Array.isArray(r.quick_actions) && r.quick_actions.length){
                                qa.innerHTML = '';
                                r.quick_actions.slice(0, 6).forEach(item=>{
                                    const btn = document.createElement('button');
                                    btn.className = 'rcm-cb-chip';
                                    btn.dataset.msg = item.message || item;
                                    btn.textContent = item.label || item;
                                    btn.addEventListener('click', ()=>sendMessage(btn.dataset.msg));
                                    qa.appendChild(btn);
                                });
                            }

                            // badge if closed
                            if (!state.open){
                                badge.textContent = '1';
                                badge.classList.add('show');
                            }
                        } else {
                            appendMsg('bot', '⚠️ ' + ((r && r.error) ? r.error : 'خطایی رخ داد. دوباره تلاش کنید.'));
                        }

                        if (r && r.session_id){
                            state.sessionId = r.session_id;
                            localStorage.setItem('rcm_cb_session', state.sessionId);
                        }
                    } catch(e){
                        hideTyping();
                        appendMsg('bot', '⚠️ ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
                    } finally {
                        state.loading = false;
                        send.disabled = !text.value.trim();
                        if (status) status.textContent = 'آماده پاسخگویی';
                    }
                }

                // Textarea autosize + enable send
                text.addEventListener('input', function(){
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 110) + 'px';
                    send.disabled = !this.value.trim() || state.loading;
                });

                text.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' && !e.shiftKey){
                        e.preventDefault();
                        sendMessage();
                    }
                });

                send.addEventListener('click', ()=>sendMessage());

                // Chips
                qa.addEventListener('click', function(e){
                    const btn = e.target.closest('.rcm-cb-chip');
                    if (!btn) return;
                    sendMessage(btn.dataset.msg || btn.textContent);
                });

                // Copy handler (event delegation)
                msgs.addEventListener('click', async function(e){
                    const btn = e.target.closest('[data-copy]');
                    if (!btn) return;

                    const val = btn.getAttribute('data-copy') || '';
                    try{
                        await navigator.clipboard.writeText(val);
                        showToast('کپی شد ✅');
                    } catch(err){
                        showToast('کپی نشد ❌');
                    }
                });

                // New conversation
                btnNew.addEventListener('click', async function(){
                    if (!confirm('مکالمه جدید شروع شود؟')) return;
                    state.sessionId = null;
                    localStorage.removeItem('rcm_cb_session');
                    msgs.innerHTML = '';
                    appendMsg('bot', 'مکالمه جدید شروع شد. سوالتو بپرس 🙂');
                    await ensureSession();
                });

                // Expand
                btnEx.addEventListener('click', function(){
                    setExpanded(!state.expanded);
                });

                // Toggle
                fab.addEventListener('click', ()=>toggle());
                document.addEventListener('keydown', (e)=>{
                    if (e.key === 'Escape' && state.open) toggle(false);
                });

                // Restore state
                setExpanded(state.expanded);
                const openAtLoad = localStorage.getItem('rcm_cb_open') === '1';
                if (openAtLoad) toggle(true);

                // Init session in background
                ensureSession();

                // Tiny initial badge
                setTimeout(()=>{ if (!state.open) badge.classList.add('show'); }, 2200);

            })();
        </script>
    <?php endif; ?>

</body>
</html>
