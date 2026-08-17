<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Client ID-card photos ("poza buletin") for the documents module.
 *
 * The photo belongs to the CLIENT (gh3sp_docs_u), not to a single document: it
 * is uploaded once and shows up on every document of that client, old ones
 * included. Two optional slots, front and back — a buletin carries the data on
 * one face and the address on the other, and either may be all that is needed.
 *
 * Files live in /media/docs_id, which is closed by .htaccess: an ID scan is not
 * something a guessed URL may hand out. They are served only through
 * content/admin/include/docs_id_photo.php, which checks the admin session.
 *
 * Shared by both save paths — docs_print.php (add) and ajax/docs/ajax.php
 * (edit) — so the two cannot drift apart.
 */

if (!function_exists('docs_id_photo_dir')) {

    /** Absolute storage directory, created (closed) on first use. */
    function docs_id_photo_dir(): string
    {
        $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 4), '/\\');
        $dir  = $root . '/media/docs_id';

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // Recreated if it ever goes missing: without it the folder is public.
        $ht = $dir . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
                                  . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
        }

        return $dir;
    }

    /**
     * Stored names are 32 hex chars + extension, generated here. Anything else
     * is refused, which is also what keeps "../" out of the served path.
     */
    function docs_id_photo_valid_name(?string $file): bool
    {
        return (bool)preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/', (string)$file);
    }

    /** Full path of a stored photo, or '' when the name is not one of ours. */
    function docs_id_photo_path(?string $file): string
    {
        if (!docs_id_photo_valid_name($file)) {
            return '';
        }
        $path = docs_id_photo_dir() . '/' . $file;
        return is_file($path) ? $path : '';
    }

    /** Adds the columns on installations that predate the feature. */
    function docs_id_photo_ensure_schema($db, string $prefx): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        try {
            $has = $db->query('SHOW COLUMNS FROM '.$prefx.'_docs_u LIKE "id_photo_front"')->fetch();
            if (!$has) {
                $db->exec('ALTER TABLE '.$prefx.'_docs_u
                    ADD COLUMN `id_photo_front` VARCHAR(64) DEFAULT NULL,
                    ADD COLUMN `id_photo_back`  VARCHAR(64) DEFAULT NULL,
                    ADD COLUMN `id_photo_at`    DATETIME    DEFAULT NULL');
            }
        } catch (Throwable $e) {
            // Not fatal: the document itself must still save.
        }
    }

    /**
     * Writes the two slots onto the client and removes the files they replace.
     *
     * $front/$back are the names the upload endpoint returned, '' to clear the
     * slot, or null when the form did not carry that field at all — a document
     * type without the widget must not wipe a photo somebody else uploaded.
     */
    function docs_id_photo_save($db, string $prefx, int $userId, ?string $front, ?string $back): void
    {
        if ($userId <= 0 || ($front === null && $back === null)) {
            return;
        }
        docs_id_photo_ensure_schema($db, $prefx);

        try {
            $stmt = $db->prepare('SELECT `id_photo_front`, `id_photo_back` FROM '.$prefx.'_docs_u WHERE `id`=:id LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $cur = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $set = [];
            $arg = ['id' => $userId];
            $gone = [];

            foreach (['front' => $front, 'back' => $back] as $slot => $val) {
                if ($val === null) continue;

                $col = 'id_photo_' . $slot;
                $new = docs_id_photo_valid_name($val) ? $val : '';
                $old = (string)($cur[$col] ?? '');
                if ($new === $old) continue;

                $set[] = '`'.$col.'`=:'.$col;
                $arg[$col] = $new !== '' ? $new : null;
                // An ID scan should not linger on disk once it is no longer the
                // one on file.
                if ($old !== '') $gone[] = $old;
            }

            if (!$set) return;

            $set[] = '`id_photo_at`=NOW()';
            $db->prepare('UPDATE '.$prefx.'_docs_u SET '.implode(', ', $set).' WHERE `id`=:id')->execute($arg);

            foreach ($gone as $f) {
                $p = docs_id_photo_path($f);
                if ($p !== '') @unlink($p);
            }
        } catch (Throwable $e) {
            // Same reason as above: never block the document over a photo.
        }
    }

    /**
     * The two slots as form fields. Rendered inside every document's client
     * block, so the same markup serves the add form and the edit overlay (which
     * clones it); the hidden inputs are what actually travels with the form.
     */
    function docs_id_photo_widget(string $lang = 'ro'): string
    {
        $t = [
            'ro' => ['ttl' => 'Poza buletin client', 'front' => 'Față', 'back' => 'Verso',
                     'pick' => 'Încarcă', 'change' => 'Schimbă', 'del' => 'Șterge', 'open' => 'Deschide'],
            'ru' => ['ttl' => 'Фото удостоверения клиента', 'front' => 'Лицевая', 'back' => 'Оборот',
                     'pick' => 'Загрузить', 'change' => 'Заменить', 'del' => 'Удалить', 'open' => 'Открыть'],
            'en' => ['ttl' => 'Client ID card photo', 'front' => 'Front', 'back' => 'Back',
                     'pick' => 'Upload', 'change' => 'Replace', 'del' => 'Remove', 'open' => 'Open'],
        ][$lang] ?? null;
        $t = $t ?: ['ttl' => 'Poza buletin client', 'front' => 'Față', 'back' => 'Verso',
                    'pick' => 'Încarcă', 'change' => 'Schimbă', 'del' => 'Șterge', 'open' => 'Deschide'];

        // One framed box per face: empty it is the drop target, filled it is the
        // photo itself with an × in the corner. No text buttons — the box says
        // what it is, and the label above says which face.
        $slot = function (string $key, string $label) use ($t): string {
            return '<div class="idph__slot" data-slot="'.$key.'">'
                 . '<span class="idph__lbl">'.htmlspecialchars($label).'</span>'
                 . '<input type="hidden" name="id_photo_'.$key.'" value="" />'
                 . '<div class="idph__box">'
                     // href kept so a middle-click still opens the file; a plain
                     // click is intercepted and shown in place instead.
                     . '<a class="idph__thumb" href="#" hidden><img alt="" /></a>'
                     // Replace, beside the ×: with the photo covering the frame
                     // the upload target is hidden, so a filled slot needs its
                     // own way back to the file dialog.
                     . '<button type="button" class="idph__swap" hidden'
                     . ' title="'.htmlspecialchars($t['change']).'" aria-label="'.htmlspecialchars($t['change']).'">'
                     . '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor"'
                     . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                     . '<path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg></button>'
                     . '<button type="button" class="idph__del" hidden'
                     . ' title="'.htmlspecialchars($t['del']).'" aria-label="'.htmlspecialchars($t['del']).'">&times;</button>'
                     . '<label class="idph__pick">'
                         . '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor"'
                         . ' stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                         . '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/>'
                         . '<path d="M21 16l-5-5-6 6"/></svg>'
                         . '<span>'.htmlspecialchars($t['pick']).'</span>'
                         . '<input type="file" accept="image/jpeg,image/png,image/webp" hidden />'
                     . '</label>'
                 . '</div>'
                 . '<span class="idph__msg"></span>'
                 . '</div>';
        };

        return '<div class="idph">'
             . '<span class="idph__ttl">'.htmlspecialchars($t['ttl']).'</span>'
             . '<div class="idph__slots">'.$slot('front', $t['front']).$slot('back', $t['back']).'</div>'
             . '</div>';
    }

    /**
     * Icon button for a document row: opens that client's ID scans in place.
     * Nothing is rendered when the client has none, so the bar never carries a
     * button that would open an empty box.
     */
    function docs_id_photo_row_btn(?string $front, ?string $back, string $title = 'Poza buletin'): string
    {
        $front = docs_id_photo_valid_name($front) ? (string)$front : '';
        $back  = docs_id_photo_valid_name($back)  ? (string)$back  : '';
        if ($front === '' && $back === '') {
            return '';
        }

        return '<div class="btn idph-view" data-front="'.$front.'" data-back="'.$back.'"'
             . ' title="'.htmlspecialchars($title).'" aria-label="'.htmlspecialchars($title).'">'
             . '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor"'
             . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
             . '<rect x="3" y="5" width="18" height="14" rx="2"/>'
             . '<circle cx="8.5" cy="10" r="1.5"/>'
             . '<path d="M21 16l-5-5-6 6"/>'
             . '</svg></div>';
    }

    /**
     * Styles + behaviour of the widget, emitted once per page.
     *
     * The file never travels with the form: the add form is a plain POST and
     * the edit overlay serializes its fields, so neither could carry it. It is
     * uploaded on pick, and only the returned name goes into the hidden input.
     */
    function docs_id_photo_assets(): string
    {
        static $done = false;
        if ($done) return '';
        $done = true;

        return '<style>
            .idph{display:block;margin:.8rem 0 1.1rem;padding:.9rem 1rem;background:#fafafa;border:1px solid #eee;border-radius:8px;}
            .idph__ttl{display:block;margin-bottom:.7rem;color:#e2001a;font-size:.95rem;}
            .idph__slots{display:flex;gap:1.1rem;flex-wrap:wrap;}
            .idph__slot{display:flex;flex-direction:column;gap:.35rem;}
            .idph__lbl{color:#666;font-size:.82rem;}
            /* Fixed frame, so an empty slot and a filled one occupy the same
               space and the two faces stay side by side. */
            .idph__box{position:relative;width:210px;height:132px;border-radius:8px;
                background:#fff;border:1px solid #e3e3e3;overflow:hidden;}
            .idph__thumb{display:block;width:100%;height:100%;}
            .idph__thumb img{display:block;width:100%;height:100%;object-fit:cover;}
            /* Replace + remove, over the photo corner, on a scrim so they stay
               readable on a light scan. */
            .idph__del,
            .idph__swap{position:absolute;top:6px;z-index:2;
                width:26px;height:26px;padding:0;
                display:flex;align-items:center;justify-content:center;
                border:0;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;
                line-height:1;cursor:pointer;transition:background .15s;}
            .idph__del{right:6px;font-size:19px;}
            .idph__swap{right:38px;}
            .idph__del:hover,
            .idph__swap:hover{background:#e2001a;}
            .idph__swap svg{display:block;}
            /* Empty slot: the whole frame is the upload target. */
            .idph__pick{position:absolute;top:0;right:0;bottom:0;left:0;
                display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.35rem;
                border:1px dashed #cfd4da;border-radius:8px;
                color:#8a9099;font-size:.82rem;cursor:pointer;transition:border-color .15s,color .15s;}
            .idph__pick:hover{border-color:#e2001a;color:#e2001a;}
            .idph__msg{min-height:1em;color:#888;font-size:.78rem;}
            .idph__msg.is-error{color:#e2001a;}
            /* Phone: one face per row, full width. Two 150px frames side by side
               left the scan unreadable and the corner buttons too small to hit. */
            @media (max-width:600px){
                .idph__slots{flex-direction:column;gap:.9rem;}
                .idph__slot{width:100%;}
                .idph__box{width:100%;height:190px;}
                .idph__del,
                .idph__swap{width:36px;height:36px;top:8px;}
                .idph__del{right:8px;font-size:24px;}
                .idph__swap{right:52px;}
                .idph__swap svg{width:18px;height:18px;}
            }
            /* Icon-only button in a document row; keeps the height of the text
               buttons beside it. */
            .btn.idph-view{display:inline-flex;align-items:center;justify-content:center;}
            .btn.idph-view svg{display:block;}
            /* Full-size view, in the page. Above the edit overlay, which is where
               the thumbnail is usually clicked from. */
            .idph-lb{position:fixed;top:0;right:0;bottom:0;left:0;z-index:99999;
                display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.82);}
            .idph-lb[hidden]{display:none;}
            /* One face at a time; the arrows appear only when there are two. */
            .idph-lb__wrap{display:flex;align-items:center;justify-content:center;
                max-width:94vw;max-height:90vh;}
            .idph-lb__wrap img{max-width:86vw;max-height:88vh;border-radius:4px;background:#fff;
                box-shadow:0 10px 40px rgba(0,0,0,.5);}
            .idph-lb__nav{position:absolute;top:50%;transform:translateY(-50%);
                width:44px;height:44px;padding:0;
                display:flex;align-items:center;justify-content:center;
                border:0;border-radius:50%;background:rgba(255,255,255,.9);color:#222;
                font-size:30px;line-height:1;cursor:pointer;}
            .idph-lb__nav[hidden]{display:none;}
            .idph-lb__nav:hover{background:#e2001a;color:#fff;}
            /* Tucked in beside the photo rather than at the screen edges. */
            .idph-lb__prev{left:3vw;}
            .idph-lb__next{right:3vw;}
            /* Phone: the scan takes the whole width and the controls float over
               it — beside it they were eating the room the photo needed. */
            @media (max-width:900px){
                .idph-lb__wrap{max-width:100vw;}
                .idph-lb__wrap img{max-width:100vw;max-height:82vh;border-radius:0;}
                .idph-lb__nav{width:44px;height:44px;font-size:28px;background:rgba(255,255,255,.85);}
                .idph-lb__prev{left:8px;}
                .idph-lb__next{right:8px;}
                .idph-lb__x{width:44px;height:44px;font-size:28px;top:10px;right:8px;}
            }
            /* Same right offset as the next arrow, so the two sit on one
               vertical line instead of drifting apart at the screen corner. */
            .idph-lb__x{position:absolute;top:18px;right:3vw;width:40px;height:40px;padding:0;
                display:flex;align-items:center;justify-content:center;
                border:0;border-radius:50%;background:#fff;color:#222;
                font-size:26px;line-height:1;cursor:pointer;}
            .idph-lb__x:hover{background:#e2001a;color:#fff;}
        </style>
        <script>(function(){
    if (window.docsIdPhotoSync) return;
    var URL_BASE = "/content/admin/include/docs_id_photo.php?f=";

    function msg(slot, text, isErr){
        var el = slot.querySelector(".idph__msg");
        if (!el) return;
        el.textContent = text || "";
        el.className = "idph__msg" + (isErr ? " is-error" : "");
    }

    function render(slot){
        var hid  = slot.querySelector("input[type=hidden]");
        var a    = slot.querySelector(".idph__thumb");
        var img  = a ? a.querySelector("img") : null;
        var del  = slot.querySelector(".idph__del");
        var swap = slot.querySelector(".idph__swap");
        var pick = slot.querySelector(".idph__pick");
        var val  = hid ? (hid.value || "").trim() : "";

        // Filled: the photo fills the frame, with replace + remove in its
        // corner. Empty: the frame itself is the upload target. Never both.
        if (val){
            if (a){ a.href = URL_BASE + encodeURIComponent(val); a.hidden = false; }
            if (img){ img.src = URL_BASE + encodeURIComponent(val); }
            if (del){ del.hidden = false; }
            if (swap){ swap.hidden = false; }
            if (pick){ pick.style.display = "none"; }
        } else {
            if (a){ a.hidden = true; }
            if (img){ img.removeAttribute("src"); }
            if (del){ del.hidden = true; }
            if (swap){ swap.hidden = true; }
            if (pick){ pick.style.display = ""; }
        }
    }

    // Open the viewer from anywhere: docsIdPhotoView([url, url]).
    window.docsIdPhotoView = function (sources){ openBox(sources); };

    // The edit overlay fills the hidden inputs from data-* attributes without
    // firing any event, so the caller tells us when to redraw.
    window.docsIdPhotoSync = function (scope){
        var root = (scope && scope.jquery) ? scope[0] : (scope || document);
        if (!root || !root.querySelectorAll) return;
        Array.prototype.forEach.call(root.querySelectorAll(".idph__slot"), render);
    };

    document.addEventListener("change", function(e){
        var inp = e.target;
        if (!inp || inp.type !== "file" || !inp.closest) return;
        var slot = inp.closest(".idph__slot");
        if (!slot) return;

        var file = inp.files && inp.files[0];
        inp.value = "";                 // so picking the same file again still fires
        if (!file) return;

        msg(slot, "...");
        var fd = new FormData();
        fd.append("tp", "adm");
        fd.append("pg", "docs");
        fd.append("fn", "upload_id_photo");
        fd.append("photo", file);

        fetch("/ajax.php", { method: "POST", body: fd, credentials: "same-origin" })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (!res || !res.file){ msg(slot, (res && res.error) || "Eroare la încărcare.", true); return; }
                var hid = slot.querySelector("input[type=hidden]");
                if (hid) hid.value = res.file;
                msg(slot, "");
                render(slot);
            })
            .catch(function(){ msg(slot, "Eroare de rețea.", true); });
    });

    // ---- full-size view, in the page. One face at a time; with both loaded it
    // becomes a two-slide strip rather than a wall of images.
    var box = { list: [], i: 0 };

    function lightbox(){
        var lb = document.getElementById("idph_lb");
        if (lb) return lb;

        lb = document.createElement("div");
        lb.id = "idph_lb";
        lb.className = "idph-lb";
        lb.hidden = true;
        lb.innerHTML = "<button type=\"button\" class=\"idph-lb__x\" aria-label=\"×\">&times;</button>"
                     + "<button type=\"button\" class=\"idph-lb__nav idph-lb__prev\" aria-label=\"‹\" hidden>&#8249;</button>"
                     + "<div class=\"idph-lb__wrap\"><img alt=\"\" /></div>"
                     + "<button type=\"button\" class=\"idph-lb__nav idph-lb__next\" aria-label=\"›\" hidden>&#8250;</button>";
        document.body.appendChild(lb);

        lb.addEventListener("click", function(e){
            var nav = e.target.closest && e.target.closest(".idph-lb__nav");
            if (nav){ step(nav.classList.contains("idph-lb__next") ? 1 : -1); return; }
            // The backdrop closes it too — the X is not the only way out. The
            // image itself does not, or paging through would keep closing it.
            if (e.target === lb || (e.target.closest && e.target.closest(".idph-lb__x"))) closeBox();
        });
        return lb;
    }

    function show(){
        var lb  = lightbox();
        var img = lb.querySelector(".idph-lb__wrap img");
        if (img) img.src = box.list[box.i] || "";

        var many = box.list.length > 1;
        Array.prototype.forEach.call(lb.querySelectorAll(".idph-lb__nav"), function(b){ b.hidden = !many; });
    }

    function step(d){
        if (box.list.length < 2) return;
        box.i = (box.i + d + box.list.length) % box.list.length;   // wraps: only two faces
        show();
    }

    /** @param {string[]} sources one or both faces of the ID card */
    function openBox(sources){
        sources = (sources || []).filter(Boolean);
        if (!sources.length) return;

        box.list = sources;
        box.i = 0;
        show();
        lightbox().hidden = false;
    }

    function closeBox(){
        var lb = document.getElementById("idph_lb");
        if (!lb) return;
        lb.hidden = true;
        var img = lb.querySelector(".idph-lb__wrap img");
        if (img) img.removeAttribute("src");   // stop holding the scan in view
        box.list = [];
    }

    document.addEventListener("keydown", function(e){
        var lb = document.getElementById("idph_lb");
        if (!lb || lb.hidden) return;
        if (e.key === "Escape")     { closeBox(); }
        else if (e.key === "ArrowLeft")  { step(-1); }
        else if (e.key === "ArrowRight") { step(1); }
    });

    document.addEventListener("click", function(e){
        var thumb = e.target && e.target.closest ? e.target.closest(".idph__thumb") : null;
        if (thumb){
            e.preventDefault();
            openBox([thumb.getAttribute("href")]);
            return;
        }

        // Icon in a document row: both faces at once, without opening the editor.
        // Stops the click there — the row is a <label> whose radio drives the
        // action bar, so letting it through would fold the buttons away.
        var view = e.target && e.target.closest ? e.target.closest(".idph-view") : null;
        if (view){
            e.preventDefault();
            e.stopPropagation();
            openBox([view.dataset.front, view.dataset.back].map(function(f){
                return f ? URL_BASE + encodeURIComponent(f) : "";
            }));
            return;
        }

        // Replace: the file input is inside the hidden upload label, and a
        // hidden input still opens the dialog when clicked from script.
        var swap = e.target && e.target.closest ? e.target.closest(".idph__swap") : null;
        if (swap){
            e.preventDefault();
            var file = swap.closest(".idph__slot").querySelector("input[type=file]");
            if (file) file.click();
            return;
        }

        var btn = e.target && e.target.closest ? e.target.closest(".idph__del") : null;
        if (!btn) return;
        e.preventDefault();
        var slot = btn.closest(".idph__slot");
        var hid  = slot ? slot.querySelector("input[type=hidden]") : null;
        // Only the reference is dropped here; the file itself is removed when
        // the document is saved, so cancelling the edit leaves it intact.
        if (hid) hid.value = "";
        if (slot){ msg(slot, ""); render(slot); }
    });

    document.addEventListener("DOMContentLoaded", function(){ window.docsIdPhotoSync(document); });
})();</script>';
    }
}
