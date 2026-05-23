<?php
/**
 * Authentication helpers.
 * Covers both public customer sessions and admin sessions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Admin ────────────────────────────────────────────────────────────────────

/**
 * Redirect to the admin login page if the user is not authenticated as admin.
 */
function requireAdminLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . getAdminBase() . '/login.php');
        exit;
    }
}

/**
 * Return the base URL/path for the admin panel.
 * Works regardless of how deep the calling script is.
 */
function getAdminBase(): string
{
    return '/Final_Project__DSS_Mitea_Diana-Maria/admin';
}

/**
 * Return the base URL/path for the public site.
 */
function getPublicBase(): string
{
    return '/Final_Project__DSS_Mitea_Diana-Maria';
}

/**
 * True when the current session belongs to a logged-in admin.
 */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

// ── Customer ─────────────────────────────────────────────────────────────────

/**
 * True when the current session belongs to a logged-in customer.
 */
function isCustomerLoggedIn(): bool
{
    return !empty($_SESSION['customer_id']);
}

/**
 * Redirect to the customer login page if not authenticated.
 */
function requireCustomerLogin(): void
{
    if (!isCustomerLoggedIn()) {
        header('Location: ' . getPublicBase() . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────────────────────────

/**
 * Store a flash message in the session.
 *
 * @param string $type  Bootstrap alert type: success | danger | warning | info
 * @param string $message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Render and clear the current flash message (if any).
 */
function renderFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $type    = htmlspecialchars($_SESSION['flash']['type'],    ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['flash']);

    return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">"
         . $message
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
         . '</div>';
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

/**
 * Generate (or reuse) a CSRF token for the current session.
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token supplied in a POST request.
 * Terminates with 403 if the token is missing or invalid.
 */
function verifyCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(getCsrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}
