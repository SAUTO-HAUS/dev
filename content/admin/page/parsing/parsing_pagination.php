<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Shared pagination helper for the parsing catalog pages.
 * 100 cars per page. Reads the current page from ?pg_n, preserves all other
 * GET params (source + filter f_*), and renders numbered page links.
 */

if (!function_exists('parsing_page_size')) {
    function parsing_page_size(): int { return 100; }
}

if (!function_exists('parsing_current_page')) {
    function parsing_current_page(): int {
        $n = (int)($_GET['pg_n'] ?? 1);
        return $n > 0 ? $n : 1;
    }
}

if (!function_exists('parsing_render_pagination')) {
    /**
     * @param int $total  total matching rows
     * @return string     HTML for the pager (empty if a single page)
     */
    function parsing_render_pagination(int $total): string {
        $size = parsing_page_size();
        $pages = (int)ceil($total / $size);
        if ($pages <= 1) return '';

        $cur = parsing_current_page();
        if ($cur > $pages) $cur = $pages;

        // Build a base query string from current GET, minus pg_n (added per link).
        $params = $_GET;
        unset($params['pg_n']);
        $base = http_build_query($params);
        $link = function ($n) use ($base) {
            $qs = $base !== '' ? $base . '&pg_n=' . $n : 'pg_n=' . $n;
            return '?' . $qs;
        };

        // Windowed page list: first, last, and a few around the current.
        $show = [];
        for ($i = 1; $i <= $pages; $i++) {
            if ($i == 1 || $i == $pages || abs($i - $cur) <= 2) $show[$i] = true;
        }

        $imgBase = '/content/admin/page/parsing/media-parsing/';
        $html = '<div class="parsing-pager">';
        if ($cur > 1) {
            $html .= '<a class="pg-btn pg-arrow pg-prev" href="'.htmlspecialchars($link($cur - 1)).'">'
                   . '<img src="'.$imgBase.'left-pag.svg" alt="‹"></a>';
        }
        $prev = 0;
        foreach (array_keys($show) as $i) {
            if ($prev && $i - $prev > 1) $html .= '<span class="pg-gap">…</span>';
            $cls = $i == $cur ? 'pg-btn active' : 'pg-btn';
            $html .= '<a class="'.$cls.'" href="'.htmlspecialchars($link($i)).'">'.$i.'</a>';
            $prev = $i;
        }
        if ($cur < $pages) {
            $html .= '<a class="pg-btn pg-arrow pg-next" href="'.htmlspecialchars($link($cur + 1)).'">'
                   . '<img src="'.$imgBase.'right-pag.svg" alt="›"></a>';
        }
        $html .= '</div>';
        return $html;
    }
}
