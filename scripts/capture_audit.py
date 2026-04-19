"""
Visual audit script for frizon.org public pages.
Captures desktop (1280x800) and mobile (390x844) screenshots,
and collects basic metrics (title, H1, CTA visibility, console errors).
"""

from playwright.sync_api import sync_playwright
import json, time

PAGES = [
    {"slug": "home",       "url": "https://app.frizon.org/"},
    {"slug": "shop",       "url": "https://app.frizon.org/shop"},
    {"slug": "topplista",  "url": "https://app.frizon.org/topplista"},
]

VIEWPORTS = [
    {"label": "desktop", "width": 1280, "height": 800},
    {"label": "mobile",  "width": 390,  "height": 844},
]

OUT = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "screenshots")

def audit_page(page, url):
    errors = []
    page.on("console", lambda msg: errors.append(msg.text) if msg.type == "error" else None)

    # Use 'load' instead of 'networkidle' to avoid timeout on long-polling/websocket pages
    page.goto(url, wait_until="load", timeout=30000)
    time.sleep(2)  # settle for fonts / lazy images

    title   = page.title()
    h1_text = page.locator("h1").first.inner_text() if page.locator("h1").count() > 0 else "(no H1)"
    h1_vis  = page.locator("h1").first.is_visible()  if page.locator("h1").count() > 0 else False

    # Check for any primary CTA (button or prominent link)
    cta_candidates = page.locator("a.btn, button.btn, a[class*='cta'], button[class*='cta'], .hero a, nav a").all()
    cta_visible = any(el.is_visible() for el in cta_candidates) if cta_candidates else False

    # Horizontal scroll check (scrollWidth > clientWidth means overflow)
    horiz_overflow = page.evaluate("document.documentElement.scrollWidth > document.documentElement.clientWidth")

    # Check for elements that go outside viewport (rough CLS signal)
    out_of_bounds = page.evaluate("""
        () => {
            const vw = window.innerWidth;
            const bad = [];
            document.querySelectorAll('*').forEach(el => {
                const r = el.getBoundingClientRect();
                if (r.right > vw + 2) bad.push(el.tagName + (el.className ? '.' + el.className.toString().trim().split(' ')[0] : ''));
            });
            return bad.slice(0, 10);
        }
    """)

    return {
        "title":          title,
        "h1":             h1_text,
        "h1_visible_atf": h1_vis,
        "cta_visible":    cta_visible,
        "horiz_overflow": horiz_overflow,
        "out_of_bounds":  out_of_bounds,
        "console_errors": errors[:10],
    }


results = {}

with sync_playwright() as p:
    for vp in VIEWPORTS:
        browser = p.chromium.launch(args=["--no-sandbox"])
        ctx = browser.new_context(
            viewport={"width": vp["width"], "height": vp["height"]},
            device_scale_factor=2 if vp["label"] == "mobile" else 1,
            user_agent=(
                "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) "
                "AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1"
                if vp["label"] == "mobile" else
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36"
            ),
        )

        for pg_def in PAGES:
            page = ctx.new_page()
            slug  = pg_def["slug"]
            label = vp["label"]
            key   = f"{slug}_{label}"

            print(f"  Capturing {label} → {pg_def['url']}")
            metrics = audit_page(page, pg_def["url"])

            shot_path = f"{OUT}/{slug}_{label}.png"
            page.screenshot(path=shot_path, full_page=False)
            print(f"    Saved: {shot_path}")

            results[key] = {"viewport": vp, "page": pg_def, "metrics": metrics, "screenshot": shot_path}
            page.close()

        ctx.close()
        browser.close()

# Dump JSON summary
json_path = f"{OUT}/audit_results.json"
with open(json_path, "w") as f:
    json.dump(results, f, indent=2, ensure_ascii=False)

print(f"\nAudit complete. Results: {json_path}")
