<?php
/**
 * CampusFix - Core Application Functions & Helpers
 *
 * Provides security utilities (CSRF, XSS escaping), flash notifications,
 * URL routing, taxonomies, and UI badge helpers.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// =======================================================
// 1. Complaint Taxonomies
// =======================================================

const COMPLAINT_CATEGORIES = [
    'Wi-Fi / Internet',
    'Electrical',
    'Classroom',
    'Lab Equipment',
    'Cleanliness',
    'Water Supply',
    'Furniture',
    'Washroom',
    'Security',
    'Other'
];

const COMPLAINT_PRIORITIES = ['Low', 'Medium', 'High'];

const COMPLAINT_STATUSES = ['Pending', 'In Progress', 'Resolved'];

// =======================================================
// 2. Output Escaping (XSS Prevention)
// =======================================================

/**
 * Escapes output safely for HTML representation.
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// =======================================================
// 3. CSRF Protection
// =======================================================

/**
 * Generates or retrieves the active session CSRF token.
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden input field containing the current CSRF token.
 *
 * @return string
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validates a submitted CSRF token against the active session token.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// =======================================================
// 4. Flash Messages
// =======================================================

/**
 * Sets a flash message to be displayed on the next page load.
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Checks if there are flash messages of a specific type.
 *
 * @param string $type
 * @return bool
 */
function has_flash(string $type): bool {
    return !empty($_SESSION['flash'][$type]);
}

/**
 * Retrieves and clears all flash messages for a specific type.
 *
 * @param string $type
 * @return array
 */
function get_flash(string $type): array {
    if (isset($_SESSION['flash'][$type])) {
        $messages = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }
    return [];
}

/**
 * Renders all queued flash messages as Bootstrap 5 dismissible alerts.
 *
 * @return string
 */
function render_flash_messages(): string {
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $output = '';
    $types = ['success', 'danger', 'warning', 'info'];

    foreach ($types as $type) {
        $messages = get_flash($type);
        if (!empty($messages)) {
            $alertClass = 'alert-' . $type;
            $icon = match($type) {
                'success' => 'bi-check-circle-fill',
                'danger'  => 'bi-exclamation-triangle-fill',
                'warning' => 'bi-exclamation-circle-fill',
                default   => 'bi-info-circle-fill',
            };

            foreach ($messages as $msg) {
                $output .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show shadow-sm d-flex align-items-center mb-3" role="alert">';
                $output .= '  <i class="bi ' . $icon . ' me-2 flex-shrink-0 fs-5"></i>';
                $output .= '  <div class="flex-grow-1">' . e($msg) . '</div>';
                $output .= '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                $output .= '</div>';
            }
        }
    }

    return $output;
}

// =======================================================
// 5. URL & Navigation Helpers
// =======================================================

/**
 * Computes an absolute path for the application root.
 * Works seamlessly in root or subdirectories such as /CampusFix/.
 *
 * @param string $path
 * @return string
 */
function base_url(string $path = ''): string {
    static $baseUrl = null;

    if ($baseUrl === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

        if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
            $subPath = substr($appRoot, strlen($docRoot));
            $baseUrl = '/' . trim($subPath, '/') . '/';
            if ($baseUrl === '//') {
                $baseUrl = '/';
            }
        } else {
            // Default fallback for XAMPP htdocs/CampusFix
            $baseUrl = '/CampusFix/';
        }
    }

    return $baseUrl . ltrim($path, '/');
}

/**
 * Performs a safe header redirect and terminates script execution.
 *
 * @param string $url Relative or absolute path
 * @return void
 */
function redirect(string $url): void {
    if (!preg_match('#^https?://#i', $url) && !str_starts_with($url, '/')) {
        $url = base_url($url);
    }
    header('Location: ' . $url);
    exit;
}

// =======================================================
// 6. UI Badge Helpers
// =======================================================

/**
 * Renders a color-coded Bootstrap badge for a complaint status.
 * Pending = warning, In Progress = info, Resolved = success
 *
 * @param string $status
 * @return string
 */
function status_badge(string $status): string {
    return match ($status) {
        'Pending'     => '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Pending</span>',
        'In Progress' => '<span class="badge bg-info text-dark"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>',
        'Resolved'    => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Resolved</span>',
        default       => '<span class="badge bg-secondary">' . e($status) . '</span>',
    };
}

/**
 * Renders a color-coded Bootstrap badge for a complaint priority.
 * Low = secondary, Medium = primary, High = danger
 *
 * @param string $priority
 * @return string
 */
function priority_badge(string $priority): string {
    return match ($priority) {
        'Low'    => '<span class="badge bg-secondary">Low</span>',
        'Medium' => '<span class="badge bg-primary">Medium</span>',
        'High'   => '<span class="badge bg-danger">High</span>',
        default  => '<span class="badge bg-light text-dark">' . e($priority) . '</span>',
    };
}

/**
 * Formats a SQL timestamp into a user-friendly date string.
 *
 * @param string|null $timestamp
 * @return string
 */
function format_date(?string $timestamp): string {
    if (empty($timestamp)) {
        return 'N/A';
    }
    $time = strtotime($timestamp);
    return $time !== false ? date('M d, Y h:i A', $time) : e($timestamp);
}
