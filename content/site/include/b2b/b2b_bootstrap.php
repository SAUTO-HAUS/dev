<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Resolves the B2B session for every public request.
 *
 * Included from index.php and ajax.php BEFORE any output, because the header and
 * the landed-cost pricing both depend on who is logged in, and currentUser() may
 * need to clear a stale cookie while headers are still open.
 *
 * Page files under content/site/page are procedural, so plain functions are the
 * natural interface here.
 */

use App\Services\B2b\B2bAudit;
use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bCsrf;
use App\Services\B2b\B2bRegions;

if (!function_exists('b2b_user')) {

    /** @return array<string, mixed>|null */
    function b2b_user(): ?array
    {
        if (!array_key_exists('b2b_user', $GLOBALS)) {
            try {
                $GLOBALS['b2b_user'] = B2bAuth::currentUser();
            } catch (\Throwable $e) {
                // Module not migrated yet (tables missing): behave as a guest
                // rather than taking the public site down.
                $GLOBALS['b2b_user'] = null;
            }
        }
        return $GLOBALS['b2b_user'];
    }

    function b2b_is_client(): bool
    {
        return b2b_user() !== null;
    }

    function b2b_user_id(): int
    {
        $u = b2b_user();
        return $u ? (int)$u['id'] : 0;
    }

    function b2b_csrf_token(): string
    {
        try {
            return B2bCsrf::token();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** @return string[] */
    function b2b_allowed_regions(): array
    {
        $id = b2b_user_id();
        return $id > 0 ? B2bRegions::allowed($id) : [];
    }

    /** Guests are never region-restricted. */
    function b2b_can_see_region(string $region): bool
    {
        if (!b2b_is_client()) {
            return true;
        }
        return B2bRegions::isAllowed(b2b_user_id(), $region);
    }

    /** SQL restriction for gh3sp_car_ctlg queries; '' for guests and full access. */
    function b2b_sql_region_filter(string $alias = ''): string
    {
        if (!b2b_is_client()) {
            return '';
        }
        try {
            return B2bRegions::sqlRestriction(b2b_user_id(), $alias);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Audit: car viewed by a partner (acceptance criteria). */
    function b2b_log_car_view(int $carId): void
    {
        if (!b2b_is_client()) {
            return;
        }
        try {
            B2bAudit::logCarView(b2b_user_id(), $carId);
        } catch (\Throwable $e) {
            // logging must not block rendering
        }
    }
}

// Resolve once, while headers can still be modified.
b2b_user();
