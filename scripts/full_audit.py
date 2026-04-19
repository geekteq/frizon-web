"""
Full visual + mobile SEO audit for frizon.org — 4 pages x 2 viewports.
Viewports: desktop 1280x800, mobile 375x812 (iPhone SE/8/X footprint).
"""

from playwright.sync_api import sync_playwright
import json, time, os

PAGES = [
    {"slug": "home",      "url": "https://app.frizon.org/"},
    {"slug": "shop",      "url": "https://app.frizon.org/shop"},
    {"slug": "topplista", "url": "https://app.frizon.org/topplista"},
    {"slug": "plats",     "url": "https://app.frizon.org/platser/trosa-havsbad-camping-e33ee8"},
]

VIEWPORTS = [
    {"label": "desktop", "width": 1280, "height": 800},
    {"label": "mobile",  "width": 375,  "height": 812},
]

OUT = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "screenshots")
os.makedirs(OUT, exist_ok=True)


def audit_page(page, url):
    errors = []
    page.on("console", lambda msg: errors.append(msg.text) if msg.type == "error" else None)

    page.goto(url, wait_until="load", timeout=30000)
    time.sleep(2)  # settle fonts/lazy images

    title   = page.title()
    h1_count = page.locator("h1").count()
    h1_text = page.locator("h1").first.inner_text() if h1_count > 0 else "(no H1)"
    h1_vis  = page.locator("h1").first.is_visible()  if h1_count > 0 else False

    # CTA: buttons, prominent links, nav links
    cta_candidates = page.locator("a.btn, button.btn, a[class*='cta'], button[class*='cta'], .hero a, nav a").all()
    cta_visible = any(el.is_visible() for el in cta_candidates) if cta_candidates else False

    # Horizontal overflow
    horiz_overflow = page.evaluate(
        "document.documentElement.scrollWidth > document.documentElement.clientWidth"
    )

    # Elements overflowing viewport width (CLS / layout signal)
    out_of_bounds = page.evaluate("""
        () => {
            const vw = window.innerWidth;
            const bad = [];
            document.querySelectorAll('*').forEach(el => {
                const r = el.getBoundingClientRect();
                if (r.right > vw + 2) {
                    const cls = el.className && typeof el.className === 'string'
                        ? '.' + el.className.trim().split(/\s+/)[0] : '';
                    bad.push(el.tagName + cls);
                }
            });
            return [...new Set(bad)].slice(0, 15);
        }
    """)

    # Small tap targets (< 44px in either dimension) — visible interactive elements
    small_targets = page.evaluate("""
        () => {
            const MIN = 44;
            const bad = [];
            document.querySelectorAll('a, button, [role="button"], input, select, textarea').forEach(el => {
                const r = el.getBoundingClientRect();
                if (r.width > 0 && r.height > 0 && (r.width < MIN || r.height < MIN)) {
                    const cls = el.className && typeof el.className === 'string'
                        ? '.' + el.className.trim().split(/\s+/)[0] : '';
                    bad.push({tag: el.tagName + cls, w: Math.round(r.width), h: Math.round(r.height)});
                }
            });
            return bad.slice(0, 20);
        }
    """)

    # Font size check — any visible text node rendered below 16px
    small_fonts = page.evaluate("""
        () => {
            const bad = [];
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_ELEMENT);
            let node;
            while ((node = walker.nextNode())) {
                const style = window.getComputedStyle(node);
                const size = parseFloat(style.fontSize);
                const text = node.innerText ? node.innerText.trim().slice(0, 40) : '';
                if (size < 16 && text.length > 10 && node.getBoundingClientRect().height > 0) {
                    bad.push({tag: node.tagName, size: size, text: text});
                }
            }
            return bad.slice(0, 10);
        }
    """)

    # Meta description check
    meta_desc = page.evaluate("""
        () => {
            const el = document.querySelector('meta[name="description"]');
            return el ? el.getAttribute('content') : null;
        }
    """)

    # Canonical URL
    canonical = page.evaluate("""
        () => {
            const el = document.querySelector('link[rel="canonical"]');
            return el ? el.getAttribute('href') : null;
        }
    """)

    # OG image
    og_image = page.evaluate("""
        () => {
            const el = document.querySelector('meta[property="og:image"]');
            return el ? el.getAttribute('content') : null;
        }
    """)

    # Check for any modal / overlay
    overlay = page.evaluate("""
        () => {
            const candidates = document.querySelectorAll('[class*="modal"],[class*="popup"],[class*="overlay"],[class*="cookie"],[id*="modal"],[id*="cookie"]');
            const visible = [];
            candidates.forEach(el => {
                const style = window.getComputedStyle(el);
                if (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0') {
                    visible.push(el.tagName + (el.id ? '#' + el.id : '') + (el.className ? '.' + el.className.trim().split(/\s+/)[0] : ''));
                }
            });
            return visible;
        }
    """)

    # Image loading — find images with explicit width/height (avoids CLS)
    img_audit = page.evaluate("""
        () => {
            const imgs = Array.from(document.querySelectorAll('img'));
            return imgs.map(img => ({
                src: img.src.split('/').slice(-2).join('/'),
                w: img.getAttribute('width'),
                h: img.getAttribute('height'),
                loading: img.getAttribute('loading'),
                hasAlt: img.hasAttribute('alt'),
            })).slice(0, 15);
        }
    """)

    return {
        "title":          title,
        "h1":             h1_text,
        "h1_visible_atf": h1_vis,
        "h1_count":       h1_count,
        "cta_visible":    cta_visible,
        "horiz_overflow": horiz_overflow,
        "out_of_bounds":  out_of_bounds,
        "small_targets":  small_targets,
        "small_fonts":    small_fonts,
        "meta_desc":      meta_desc,
        "canonical":      canonical,
        "og_image":       og_image,
        "overlays":       overlay,
        "img_audit":      img_audit,
        "console_errors": errors[:10],
    }


results = {}

with sync_playwright() as p:
    for vp in VIEWPORTS:
        is_mobile = vp["label"] == "mobile"
        browser = p.chromium.launch(args=["--no-sandbox"])
        ctx = browser.new_context(
            viewport={"width": vp["width"], "height": vp["height"]},
            device_scale_factor=2 if is_mobile else 1,
            user_agent=(
                "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) "
                "AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1"
                if is_mobile else
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
                "Chrome/120.0.0.0 Safari/537.36"
            ),
        )

        for pg_def in PAGES:
            page = ctx.new_page()
            slug  = pg_def["slug"]
            label = vp["label"]
            key   = f"{slug}_{label}"

            print(f"  [{label}] {pg_def['url']}")
            metrics = audit_page(page, pg_def["url"])

            shot_path = f"{OUT}/{slug}_{label}.png"
            page.screenshot(path=shot_path, full_page=False)
            print(f"    screenshot -> {shot_path}")

            results[key] = {
                "viewport": vp,
                "page":     pg_def,
                "metrics":  metrics,
                "screenshot": shot_path,
            }
            page.close()

        ctx.close()
        browser.close()

json_path = f"{OUT}/full_audit_results.json"
with open(json_path, "w") as f:
    json.dump(results, f, indent=2, ensure_ascii=False)

print(f"\nDone. Results: {json_path}")
