<?php

if (!defined('PARSING_FULL_IDS')) {
    define('PARSING_FULL_IDS',    [1, 6, 28]);
    define('PARSING_LIMITED_IDS', [17, 19, 27, 32, 37]);
    define('PARSING_ENCAR_IDS',   [22]);
}

/** Full access (Settings + Logs included). */
function parsing_is_full($uid): bool
{
    return in_array((int)$uid, PARSING_FULL_IDS, true);
}

/** Limited access (work pages only). */
function parsing_is_limited($uid): bool
{
    return in_array((int)$uid, PARSING_LIMITED_IDS, true);
}

function parsing_is_encar_only($uid): bool
{
    return in_array((int)$uid, PARSING_ENCAR_IDS, true);
}

function parsing_has_access($uid): bool
{
    return parsing_is_full($uid) || parsing_is_limited($uid) || parsing_is_encar_only($uid);
}

/** The menu actions a user may see, or [] if no access. */
function parsing_menu_actions($uid): array
{
    if (parsing_is_full($uid)) {
        return ['filters', 'ctlg', 'favorites', 'published', 'settings', 'logs'];
    }
    if (parsing_is_limited($uid)) {
        return ['filters', 'ctlg', 'favorites', 'published'];
    }
    if (parsing_is_encar_only($uid)) {
        return ['filters', 'ctlg'];
    }
    return [];
}
