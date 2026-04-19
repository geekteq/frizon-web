"""
Touch target and accessibility audit for frizon.org
Checks specific CSS fixes and general tap target sizes on mobile viewport.
"""
from playwright.sync_api import sync_playwright
import json

PAGES = [
    {"name": "home", "url": "https://app.frizon.org/"},
    {"name": "shop", "url": "https://app.frizon.org/shop"},
    {"name": "plats", "url": "https://app.frizon.org/platser/trosa-havsbad-camping-e33ee8"},
]

MOBILE_VIEWPORT = {"width": 375, "height": 812}
DESKTOP_VIEWPORT = {"width": 1920, "height": 1080}
_REPO_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SCREENSHOT_DIR = os.path.join(_REPO_ROOT, "screenshots")

# Minimum tap target size (WCAG 2.5.5 / Apple HIG)
MIN_TARGET_PX = 44


def measure_elements(page):
    """Measure all interactive elements and return their bounding boxes."""
    return page.evaluate("""
    () => {
        const selectors = [
            { label: '.public-header__link', sel: '.public-header__link' },
            { label: '.btn--sm', sel: '.btn--sm' },
            { label: '.pub-detail__back', sel: '.pub-detail__back' },
            { label: '.leaflet-control-zoom-in', sel: '.leaflet-control-zoom-in' },
            { label: '.leaflet-control-zoom-out', sel: '.leaflet-control-zoom-out' },
            { label: 'nav a', sel: 'nav a' },
            { label: 'a[href]', sel: 'a[href]' },
            { label: 'button', sel: 'button' },
            { label: 'input[type=submit]', sel: 'input[type=submit]' },
            { label: '.btn', sel: '.btn' },
        ];

        const results = {};
        for (const { label, sel } of selectors) {
            const els = Array.from(document.querySelectorAll(sel));
            if (!els.length) continue;
            results[label] = els.map(el => {
                const rect = el.getBoundingClientRect();
                const styles = window.getComputedStyle(el);
                return {
                    text: (el.textContent || el.value || el.getAttribute('aria-label') || '').trim().slice(0, 60),
                    href: el.getAttribute('href') || null,
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                    top: Math.round(rect.top),
                    left: Math.round(rect.left),
                    minHeight: styles.minHeight,
                    display: styles.display,
                    visible: rect.width > 0 && rect.height > 0,
                };
            }).filter(e => e.visible);
        }
        return results;
    }
    """)


def check_x_powered_by(page):
    """Capture response headers for the page request."""
    headers = {}
    def capture_response(response):
        if response.url == page.url or response.url.rstrip('/') == page.url.rstrip('/'):
            headers.update(response.headers)
    page.on('response', capture_response)
    return headers


def run_audit():
    results = {}

    with sync_playwright() as p:
        browser = p.chromium.launch()

        for page_info in PAGES:
            name = page_info["name"]
            url = page_info["url"]
            results[name] = {"url": url, "mobile": {}, "desktop": {}, "headers": {}}

            # --- Mobile ---
            mobile_ctx = browser.new_context(
                viewport=MOBILE_VIEWPORT,
                user_agent="Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15"
            )
            mobile_page = mobile_ctx.new_page()

            # Capture headers on first navigation
            captured_headers = {}
            def on_response(resp):
                if resp.url.rstrip('/') == url.rstrip('/') or resp.url == url:
                    captured_headers.update(dict(resp.headers))
            mobile_page.on('response', on_response)

            mobile_page.goto(url, wait_until='load', timeout=30000)
            mobile_page.wait_for_timeout(3000)
            mobile_page.screenshot(
                path=f"{SCREENSHOT_DIR}/{name}_mobile_audit.png",
                full_page=True
            )
            results[name]["mobile"] = measure_elements(mobile_page)
            results[name]["headers"] = captured_headers
            mobile_ctx.close()

            # --- Desktop ---
            desktop_ctx = browser.new_context(viewport=DESKTOP_VIEWPORT)
            desktop_page = desktop_ctx.new_page()
            desktop_page.goto(url, wait_until='load', timeout=30000)
            desktop_page.wait_for_timeout(3000)
            desktop_page.screenshot(
                path=f"{SCREENSHOT_DIR}/{name}_desktop_audit.png",
                full_page=False
            )
            results[name]["desktop"] = measure_elements(desktop_page)
            desktop_ctx.close()

        browser.close()

    return results


def analyze(results):
    report = {}
    for page_name, data in results.items():
        report[page_name] = {
            "url": data["url"],
            "x_powered_by": data["headers"].get("x-powered-by", "ABSENT"),
            "specific_checks": {},
            "small_targets_mobile": [],
        }

        mobile = data["mobile"]

        # ---- Specific fix checks ----

        # 1. .public-header__link nav links
        links = mobile.get(".public-header__link", [])
        if links:
            for l in links:
                report[page_name]["specific_checks"][".public-header__link"] = {
                    "measured_height": l["height"],
                    "min_height_style": l["minHeight"],
                    "pass": l["height"] >= MIN_TARGET_PX,
                    "sample_text": l["text"],
                }
                break  # Just check first one; all share same CSS
        else:
            report[page_name]["specific_checks"][".public-header__link"] = {"found": False}

        # 2. .btn--sm
        btns = mobile.get(".btn--sm", [])
        if btns:
            min_h = min(b["height"] for b in btns)
            report[page_name]["specific_checks"][".btn--sm"] = {
                "count": len(btns),
                "min_measured_height": min_h,
                "min_height_styles": [b["minHeight"] for b in btns],
                "pass": min_h >= MIN_TARGET_PX,
                "samples": [b["text"] for b in btns[:3]],
            }
        else:
            report[page_name]["specific_checks"][".btn--sm"] = {"found": False}

        # 3. .pub-detail__back
        backs = mobile.get(".pub-detail__back", [])
        if backs:
            b = backs[0]
            report[page_name]["specific_checks"][".pub-detail__back"] = {
                "measured_height": b["height"],
                "display": b["display"],
                "min_height_style": b["minHeight"],
                "pass": b["height"] >= MIN_TARGET_PX and b["display"] in ("inline-flex", "flex"),
            }
        else:
            report[page_name]["specific_checks"][".pub-detail__back"] = {"found": False}

        # 4. Leaflet zoom controls
        zoom_in = mobile.get(".leaflet-control-zoom-in", [])
        zoom_out = mobile.get(".leaflet-control-zoom-out", [])
        if zoom_in or zoom_out:
            zi = zoom_in[0] if zoom_in else None
            zo = zoom_out[0] if zoom_out else None
            report[page_name]["specific_checks"]["leaflet_zoom"] = {
                "zoom_in_height": zi["height"] if zi else "not found",
                "zoom_out_height": zo["height"] if zo else "not found",
                "pass": (zi["height"] >= MIN_TARGET_PX if zi else True) and
                        (zo["height"] >= MIN_TARGET_PX if zo else True),
            }
        else:
            report[page_name]["specific_checks"]["leaflet_zoom"] = {"found": False}

        # ---- General small targets ----
        seen = set()
        all_interactive = []
        for key in ["a[href]", "button", "input[type=submit]", ".btn"]:
            for el in mobile.get(key, []):
                ident = (el["text"], el.get("href"), el["top"], el["left"])
                if ident in seen:
                    continue
                seen.add(ident)
                all_interactive.append(el)

        small = [
            {
                "text": el["text"],
                "href": el.get("href"),
                "width": el["width"],
                "height": el["height"],
                "min_height_style": el["minHeight"],
            }
            for el in all_interactive
            if el["height"] < MIN_TARGET_PX or el["width"] < MIN_TARGET_PX
        ]
        report[page_name]["small_targets_mobile"] = small

    return report


def print_report(report):
    print("\n" + "="*70)
    print("TOUCH TARGET & ACCESSIBILITY AUDIT — frizon.org")
    print("="*70)

    for page_name, data in report.items():
        print(f"\n{'─'*60}")
        print(f"PAGE: {page_name.upper()}  ({data['url']})")
        print(f"{'─'*60}")

        # X-Powered-By
        xpb = data["x_powered_by"]
        status = "✓ ABSENT" if xpb == "ABSENT" else f"✗ PRESENT: {xpb}"
        print(f"\n  X-Powered-By header: {status}")

        # Specific checks
        print(f"\n  Specific fix checks:")
        checks = data["specific_checks"]

        def fmt_check(label, info):
            if not info.get("found", True) and "found" in info:
                return f"    {label}: — element not found on this page"
            passed = info.get("pass", False)
            mark = "✓" if passed else "✗"
            details = []
            if "measured_height" in info:
                details.append(f"height={info['measured_height']}px")
            if "min_measured_height" in info:
                details.append(f"min_height={info['min_measured_height']}px (of {info.get('count',0)} buttons)")
            if "display" in info:
                details.append(f"display={info['display']}")
            if "min_height_style" in info:
                details.append(f"min-height CSS={info['min_height_style']}")
            if "zoom_in_height" in info:
                details.append(f"zoom-in={info['zoom_in_height']}px, zoom-out={info['zoom_out_height']}px")
            return f"    {mark} {label}: {', '.join(details)}"

        print(fmt_check(".public-header__link", checks.get(".public-header__link", {})))
        print(fmt_check(".btn--sm", checks.get(".btn--sm", {})))
        print(fmt_check(".pub-detail__back", checks.get(".pub-detail__back", {})))
        print(fmt_check("Leaflet zoom controls", checks.get("leaflet_zoom", {})))

        # Small targets
        small = data["small_targets_mobile"]
        if small:
            print(f"\n  Other tap targets below 44px (mobile):")
            for t in small:
                print(f"    ✗ \"{t['text'][:50]}\" — {t['width']}x{t['height']}px  (min-height CSS: {t['min_height_style']})  href={t['href']}")
        else:
            print(f"\n  ✓ No other tap targets below 44px found on mobile.")

    print("\n" + "="*70)


if __name__ == "__main__":
    print("Running audit on 3 pages (mobile + desktop)...")
    raw = run_audit()

    with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "touch_audit_raw.json"), "w") as f:
        json.dump(raw, f, indent=2, ensure_ascii=False)

    report = analyze(raw)

    with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "touch_audit_report.json"), "w") as f:
        json.dump(report, f, indent=2, ensure_ascii=False)

    print_report(report)
    print("\nRaw data: scripts/touch_audit_raw.json")
    print("Report:   scripts/touch_audit_report.json")
    print("Screenshots: screenshots/*_audit.png")
