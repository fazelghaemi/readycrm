<?php
/**
 * ReadyCRM CodeHub helper (FULL FILE)
 * - بدون وابستگی به فریم‌ورک، سازگار با ساختار فعلی CRM
 * - فقط ادمین اجازه دسترسی دارد
 * - تشخیص ادمین: hasRole('admin') → hasPermission('manage_codehub'|'admin'|'view_codehub') → currentUserRole() → $_SESSION → getCurrentUser() → fallback IDs
 */

if (!defined('CODEHUB_LOADED')) { define('CODEHUB_LOADED', true); }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ـــــ Config اختیاری: اگر چند کاربر خاص همیشه ادمین CodeHub هستند اینجا ID بدهید (کاما جدا)
// مثال: define('CODEHUB_ADMIN_IDS', '1,2,5');
if (!defined('CODEHUB_ADMIN_IDS')) {
    define('CODEHUB_ADMIN_IDS', '1'); // پیشفرض: کاربر 1
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

/** CSRF helpers */
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/** PDO Connection resolver */
function codehub_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // 1) If project has Database class
    if (class_exists('Database')) {
        $db = new Database();
        if (method_exists($db, 'getConnection')) {
            $pdo = $db->getConnection();
        } elseif (property_exists($db, 'pdo')) {
            $pdo = $db->pdo;
        }
    }

    // 2) Global $pdo
    if (!$pdo && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        $pdo = $GLOBALS['pdo'];
    }

    // 3) Constants
    if (!$pdo && defined('DB_HOST')) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    if (!$pdo) { throw new RuntimeException('PDO connection is not available for CodeHub'); }
    return $pdo;
}

/** Determine admin */
function codehub_is_admin(): bool {
    // 0) if not logged-in -> false
    if (!function_exists('isLoggedIn') || !isLoggedIn()) return false;

    // 1) Your project’s role system
    if (function_exists('hasRole')) {
        try {
            if (hasRole('admin')) return true;
        } catch (Throwable $e) {}
    }

    // 2) Your project’s permission system
    if (function_exists('hasPermission')) {
        try {
            if (hasPermission('manage_codehub') || hasPermission('admin') || hasPermission('view_codehub')) return true;
        } catch (Throwable $e) {}
    }

    // 3) currentUserRole()
    if (function_exists('currentUserRole')) {
        try {
            $r = strtolower((string)currentUserRole());
            if ($r === 'admin' || $r === 'administrator') return true;
        } catch (Throwable $e) {}
    }

    // 4) Session user role
    if (isset($_SESSION['user']) && isset($_SESSION['user']['role'])) {
        $r = strtolower((string)$_SESSION['user']['role']);
        if ($r === 'admin' || $r === 'administrator') return true;
    }

    // 5) getCurrentUser()['role']
    if (function_exists('getCurrentUser')) {
        try {
            $u = getCurrentUser();
            if (is_array($u)) {
                if (!empty($u['role'])) {
                    $r = strtolower((string)$u['role']);
                    if ($r === 'admin' || $r === 'administrator') return true;
                }
                if (!empty($u['id'])) {
                    $id = (int)$u['id'];
                    $ids = array_filter(array_map('trim', explode(',', CODEHUB_ADMIN_IDS)));
                    if (in_array((string)$id, $ids, true)) return true;
                }
            }
        } catch (Throwable $e) {}
    }

    // 6) Fallback to session user id in whitelist
    if (isset($_SESSION['user']['id'])) {
        $id  = (int)$_SESSION['user']['id'];
        $ids = array_filter(array_map('trim', explode(',', CODEHUB_ADMIN_IDS)));
        if (in_array((string)$id, $ids, true)) return true;
    }

    // 7) Last resort: user id == 1
    if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === 1) return true;

    return false;
}

/** Gate */
function codehub_require_admin() {
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        // align with app behavior; codehub/* در یک پوشه جدا است
        header('Location: ../login.php');
        exit();
    }
    if (!codehub_is_admin()) {
        http_response_code(403);
        exit('دسترسی غیرمجاز — فقط ادمین می‌تواند به CodeHub وارد شود.');
    }
}

/** Useful helpers */
function codehub_user_id(): int {
    if (isset($_SESSION['user']['id'])) return (int)$_SESSION['user']['id'];
    if (function_exists('getCurrentUser')) {
        $u = @getCurrentUser();
        if (is_array($u) && !empty($u['id'])) return (int)$u['id'];
    }
    return 0;
}
function codehub_normalize_tags(string $tags = null): ?string {
    if (!$tags) return null;
    $parts = array_filter(array_map(fn($t) => trim($t), preg_split('/[,،]/u', $tags)));
    $parts = array_values(array_unique($parts));
    return $parts ? implode(',', $parts) : null;
}

/** CRUD: snippets */
function codehub_snippet_create(array $data): int {
    $pdo = codehub_pdo();
    $sql = "INSERT INTO code_snippets (title, language, tags, description, content, is_private, created_by, created_at)
            VALUES (:title, :language, :tags, :description, :content, :is_private, :created_by, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title'       => $data['title'],
        ':language'    => $data['language'] ?? 'text',
        ':tags'        => codehub_normalize_tags($data['tags'] ?? ''),
        ':description' => $data['description'] ?? null,
        ':content'     => $data['content'],
        ':is_private'  => !empty($data['is_private']) ? 1 : 0,
        ':created_by'  => codehub_user_id(),
    ]);
    $id = (int)$pdo->lastInsertId();

    codehub_version_create($id, 1, $data['content'], 'ایجاد اولیه');

    if (function_exists('logActivity')) { @logActivity('codehub_create', ['id'=>$id, 'title'=>$data['title']]); }
    return $id;
}

function codehub_snippet_get(int $id): ?array {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("SELECT * FROM code_snippets WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function codehub_snippet_update(int $id, array $data): bool {
    $pdo = codehub_pdo();
    $old = codehub_snippet_get($id);
    if (!$old) return false;

    $stmt = $pdo->prepare("UPDATE code_snippets SET
        title=:title, language=:language, tags=:tags, description=:description,
        content=:content, is_private=:is_private, updated_by=:updated_by, updated_at=NOW()
        WHERE id=:id");
    $stmt->execute([
        ':title'       => $data['title'],
        ':language'    => $data['language'] ?? 'text',
        ':tags'        => codehub_normalize_tags($data['tags'] ?? ''),
        ':description' => $data['description'] ?? null,
        ':content'     => $data['content'],
        ':is_private'  => !empty($data['is_private']) ? 1 : 0,
        ':updated_by'  => codehub_user_id(),
        ':id'          => $id,
    ]);

    if (trim((string)$old['content']) !== trim((string)$data['content'])) {
        $v = codehub_next_version_no($id);
        codehub_version_create($id, $v, $data['content'], 'ویرایش محتوا');
    }

    if (function_exists('logActivity')) { @logActivity('codehub_update', ['id'=>$id]); }
    return true;
}

function codehub_snippet_delete(int $id): bool {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("DELETE FROM code_snippets WHERE id = :id");
    $ok = $stmt->execute([':id'=>$id]);
    if ($ok && function_exists('logActivity')) { @logActivity('codehub_delete', ['id'=>$id]); }
    return $ok;
}

/** Versions */
function codehub_next_version_no(int $snippet_id): int {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("SELECT MAX(version_no) as m FROM code_snippet_versions WHERE snippet_id=:id");
    $stmt->execute([':id'=>$snippet_id]);
    $row = $stmt->fetch();
    $max = $row && $row['m'] ? (int)$row['m'] : 0;
    return $max + 1;
}
function codehub_version_create(int $snippet_id, int $version_no, string $content, ?string $changelog): int {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("INSERT INTO code_snippet_versions (snippet_id, version_no, content, changelog, created_by, created_at)
                           VALUES (:sid, :v, :content, :cl, :uid, NOW())");
    $stmt->execute([
        ':sid'     => $snippet_id,
        ':v'       => $version_no,
        ':content' => $content,
        ':cl'      => $changelog,
        ':uid'     => codehub_user_id(),
    ]);
    return (int)$pdo->lastInsertId();
}
function codehub_versions(int $snippet_id): array {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("SELECT * FROM code_snippet_versions WHERE snippet_id=:id ORDER BY version_no DESC");
    $stmt->execute([':id'=>$snippet_id]);
    return $stmt->fetchAll() ?: [];
}

/** Listing / search */
function codehub_snippet_list(?string $q=null, ?string $lang=null, ?string $tag=null, int $limit=50, int $offset=0): array {
    $pdo = codehub_pdo();
    $where = []; $params = [];
    if ($q)   { $where[] = "(title LIKE :q OR description LIKE :q OR content LIKE :q)"; $params[':q'] = "%$q%"; }
    if ($lang){ $where[] = "language = :lang"; $params[':lang'] = $lang; }
    if ($tag) { $where[] = "FIND_IN_SET(:tag, REPLACE(tags, '،', ','))"; $params[':tag'] = $tag; }

    if (!codehub_is_admin()) { $where[] = "is_private = 0"; }

    $sql = "SELECT * FROM code_snippets"
         . (count($where) ? (" WHERE " . implode(" AND ", $where)) : "")
         . " ORDER BY IFNULL(updated_at, created_at) DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

/** Stars */
function codehub_star(int $snippet_id): bool {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("INSERT IGNORE INTO code_snippet_stars (snippet_id, user_id, created_at) VALUES (:sid, :uid, NOW())");
    return $stmt->execute([':sid'=>$snippet_id, ':uid'=>codehub_user_id()]);
}
function codehub_unstar(int $snippet_id): bool {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("DELETE FROM code_snippet_stars WHERE snippet_id=:sid AND user_id=:uid");
    return $stmt->execute([':sid'=>$snippet_id, ':uid'=>codehub_user_id()]);
}
function codehub_is_starred(int $snippet_id): bool {
    $pdo = codehub_pdo();
    $stmt = $pdo->prepare("SELECT 1 FROM code_snippet_stars WHERE snippet_id=:sid AND user_id=:uid");
    $stmt->execute([':sid'=>$snippet_id, ':uid'=>codehub_user_id()]);
    return (bool)$stmt->fetch();
}

/** Output escape */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Languages for selector / highlight */
function codehub_languages(): array {
    return [
        'text' => 'متن',
        'bash' => 'Bash',
        'ini'  => 'INI',
        'apache' => 'Apache',
        'nginx'  => 'Nginx',
        'php'  => 'PHP',
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'python' => 'Python',
        'java' => 'Java',
        'c' => 'C',
        'cpp' => 'C++',
        'csharp' => 'C#',
        'go' => 'Go',
        'ruby' => 'Ruby',
        'sql' => 'SQL',
        'json' => 'JSON',
        'xml' => 'XML',
        'yaml' => 'YAML',
        'markdown' => 'Markdown',
        'css' => 'CSS',
        'html' => 'HTML',
    ];
}
