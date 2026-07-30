/**
 * Serves /media/images/upload/car/* from the R2 bucket `sauto-cars`.
 *
 * The public URL is unchanged, so none of the ~74 places in the PHP that build
 * image URLs had to be touched, and nothing in Google's index or in og:image
 * moves. The Worker only decides WHERE the bytes come from.
 *
 * On a miss it falls back to the cPanel origin and copies the object into R2 in
 * the background. That is what makes the migration safe to run live: a photo not
 * copied yet is still served, just from the old place.
 *
 * Bindings required (Worker → Settings → Bindings):
 *   CARS   R2 bucket  → sauto-cars
 * Vars:
 *   ORIGIN            → http://origin.sauto.md    (DNS-only record, grey cloud)
 *   BACKFILL          → "1" to copy misses into R2, "0" to only proxy
 *
 * ORIGIN is plain http on purpose: the cPanel certificate covers only
 * sauto.md/www.sauto.md, and fetch() inside a Worker validates certificates, so
 * https would fail. Only public car photos travel that hop, and .htaccess limits
 * the origin hostname to /media/images/upload/car/ — nothing else is reachable.
 *
 * Route: www.sauto.md/media/images/upload/car/*
 */

const PREFIX = '/media/images/upload/car/';

const TYPES = {
  jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png',
  webp: 'image/webp', gif: 'image/gif', pdf: 'application/pdf',
  html: 'text/html; charset=utf-8',
};

// Photos are immutable: a car_<id>_<n>.jpg is never rewritten in place — a new
// photo gets a new name — so we can cache hard and skip revalidation.
const CACHE_CONTROL = 'public, max-age=31536000, immutable';

function contentTypeFor(key) {
  const ext = key.split('.').pop().toLowerCase();
  return TYPES[ext] || 'application/octet-stream';
}

function headers(key, source, extra = {}) {
  return {
    'Content-Type': contentTypeFor(key),
    'Cache-Control': CACHE_CONTROL,
    'X-Img-Source': source, // handy while migrating: r2 vs origin
    ...extra,
  };
}

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    // Anything that is not a plain read goes straight to the origin: uploads and
    // admin actions must keep hitting PHP.
    if (request.method !== 'GET' && request.method !== 'HEAD') {
      return fetch(new Request(env.ORIGIN + url.pathname + url.search, request));
    }
    if (!url.pathname.startsWith(PREFIX)) {
      return fetch(new Request(env.ORIGIN + url.pathname + url.search, request));
    }

    // The R2 key is the URL path with the prefix removed — the migration script
    // stores keys in exactly this shape, so there is no mapping table.
    const key = decodeURIComponent(url.pathname.slice(PREFIX.length));
    if (!key || key.includes('..')) {
      return new Response('Bad request', { status: 400 });
    }

    const rangeHeader = request.headers.get('range');

    // Edge cache first. Cloudflare does NOT store a Worker's response by itself,
    // so without this every single view would be a fresh R2 read — a billed
    // Class B op plus a round trip — even for the same photo seen a thousand
    // times. Range and conditional requests skip it: they have to reach R2 to be
    // answered correctly, and a 206 must never be written to the cache.
    const cache = caches.default;
    const cacheable = request.method === 'GET' // cache.put() rejects HEAD
      && !rangeHeader
      && !request.headers.get('if-none-match')
      && !request.headers.get('if-modified-since');
    if (cacheable) {
      const hit = await cache.match(request);
      if (hit) return hit;
    }

    // 1. R2
    // range takes R2Range | Headers — passing the Request itself throws a 500.
    const object = await env.CARS.get(key, {
      range: rangeHeader ? request.headers : undefined,
      onlyIf: request.headers,
    });

    if (object !== null) {
      // onlyIf matched → the client's cached copy is still good.
      if (!('body' in object)) {
        return new Response(null, { status: 304, headers: headers(key, 'r2') });
      }
      const h = headers(key, 'r2', { ETag: object.httpEtag });
      // Only answer 206 when a Range was actually asked for. R2 fills in
      // object.range even for a plain GET, and a 206 nobody requested breaks
      // Facebook's og:image scraper and confuses image indexers.
      if (rangeHeader && object.range) {
        const { offset = 0, length = 0 } = object.range;
        h['Content-Range'] = `bytes ${offset}-${offset + length - 1}/${object.size}`;
        return new Response(object.body, { status: 206, headers: h });
      }
      const resp = new Response(object.body, { headers: h });
      if (cacheable) ctx.waitUntil(cache.put(request, resp.clone()));
      return resp;
    }

    // 2. Not migrated yet → serve from the cPanel origin.
    const originResp = await fetch(env.ORIGIN + url.pathname, {
      cf: { cacheTtl: 0, cacheEverything: false },
    });

    if (!originResp.ok) {
      return new Response('Not found', { status: 404 });
    }

    // Copy it into R2 for next time, without making the visitor wait.
    let resp;
    if (env.BACKFILL === '1') {
      const [toClient, toR2] = originResp.body.tee();
      ctx.waitUntil(
        env.CARS.put(key, toR2, {
          httpMetadata: { contentType: contentTypeFor(key), cacheControl: CACHE_CONTROL },
        }).catch(() => {}) // a failed backfill must never break the response
      );
      resp = new Response(toClient, { headers: headers(key, 'origin') });
    } else {
      resp = new Response(originResp.body, { headers: headers(key, 'origin') });
    }
    if (cacheable) ctx.waitUntil(cache.put(request, resp.clone()));
    return resp;
  },
};
