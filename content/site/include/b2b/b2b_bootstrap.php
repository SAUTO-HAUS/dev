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

    /**
     * Whether the partner may see a catalog type ('in_stock' / 'on_order').
     * Guests always may. The flags live on the b2b_users row (already loaded).
     */
    function b2b_can_see_catalog(string $catalogType): bool
    {
        $u = b2b_user();
        if ($u === null) {
            return true;
        }
        if ($catalogType === 'on_order') {
            return (int)($u['allow_on_order'] ?? 1) === 1;
        }
        // in_stock, plus legacy NULL catalog_type rows, count as in-stock access.
        return (int)($u['allow_in_stock'] ?? 1) === 1;
    }

    /**
     * SQL guard for a listing already restricted to one catalog type: an
     * impossible condition when the partner has no access, '' otherwise. Mirrors
     * the "no region" case in B2bRegions::sqlRestriction.
     */
    function b2b_sql_catalog_filter(string $catalogType): string
    {
        return b2b_can_see_catalog($catalogType) ? '' : ' AND 1=0';
    }

    /**
     * Gifts granted to this partner that they have not opened yet.
     *
     * Resolved once per request: the header renders on every page, and this must
     * not cost a query per call. 0 for guests and when the module is not migrated.
     */
    function b2b_gift_unseen(): int
    {
        if (!array_key_exists('b2b_gift_unseen', $GLOBALS)) {
            $GLOBALS['b2b_gift_unseen'] = 0;
            if (b2b_is_client()) {
                try {
                    $GLOBALS['b2b_gift_unseen'] = \App\Services\B2b\B2bGift::unseenCount(b2b_user_id());
                } catch (\Throwable $e) {
                    // Header must render even if the gifts table is missing.
                }
            }
        }
        return (int)$GLOBALS['b2b_gift_unseen'];
    }

    /**
     * Cars matching the partner's saved filters that they have not seen yet.
     * Resolved once per request, like b2b_gift_unseen(): the header renders on
     * every page and must not pay for a scan per call.
     */
    function b2b_filter_unseen(): int
    {
        if (!array_key_exists('b2b_filter_unseen', $GLOBALS)) {
            $GLOBALS['b2b_filter_unseen'] = 0;
            if (b2b_is_client()) {
                try {
                    $GLOBALS['b2b_filter_unseen'] = \App\Services\B2b\B2bSavedFilter::unseenTotal(b2b_user_id());
                } catch (\Throwable $e) {
                    // Header must render even if the filters table is missing.
                }
            }
        }
        return (int)$GLOBALS['b2b_filter_unseen'];
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
