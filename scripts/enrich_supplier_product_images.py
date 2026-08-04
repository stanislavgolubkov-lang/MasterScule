#!/usr/bin/env python3
"""Recover real SPIN/TELWIN product images and write a verified manifest.

TELWIN images are extracted from the manufacturer's SKU-specific PDF sheets.
SPIN first reuses exact TrisTool pages and then performs exact-SKU image search.
The script never writes catalog database records; PHP applies the manifest.
"""

from __future__ import annotations

import argparse
import concurrent.futures
import html
import io
import json
import re
import sqlite3
import ssl
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any

from PIL import Image
from pypdf import PdfReader


if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")


ROOT = Path(__file__).resolve().parents[1]
DB_PATH = ROOT / "database" / "database.sqlite"
MANIFEST_PATH = ROOT / "storage" / "app" / "parser" / "supplier-image-manifest.json"
USER_AGENT = "Mozilla/5.0 (compatible; MasterSculeProductImageRecovery/1.0)"
IMAGE_EXTENSIONS = (".jpg", ".jpeg", ".png", ".webp")
KNOWN_TELWIN_PAGES = {
    "116839": "https://www.bricoutensili.com/en/telwin-spare-parts/4527-diode-for-inverter-telwin-tecnica-151-s-171-s-211-s.html",
    "121119": "https://www.bricoutensili.com/en/telwin-spare-parts/16013-thermal-protection-thermostat-for-digital-car-spotter-5500-230-v.html",
    "121329": "https://www.bricoutensili.com/en/telwin-spare-parts/8556-microswitch-for-telwin-digital-car-puller-5000.html",
    "742355": "https://www.bricoutensili.com/en/telwin-spare-parts/11834-electrode-holder-chuck-nut-for-telwin-digital-car-puller-5000.html",
    "990240": "https://www.bricoutensili.com/en/telwin-spare-parts/18666-gun-handle-kit-for-telwin-digital-car-puller-5000.html",
}
KNOWN_TELWIN_IMAGES = {
    "116839": "https://www.bricoutensili.com/53300-home_default/diode-for-inverter-telwin-tecnica-151-s-171-s-211-s.jpg",
    "990240": "https://www.bricoutensili.com/51800-home_default/gun-handle-kit-for-telwin-digital-car-puller-5000.jpg",
}


class Fetcher:
    def __init__(self) -> None:
        context = ssl.create_default_context()
        self.opener = urllib.request.build_opener(
            urllib.request.ProxyHandler({}),
            urllib.request.HTTPSHandler(context=context),
        )
        self.cache: dict[str, tuple[bytes, str]] = {}

    def get(self, url: str, timeout: int = 35, headers: dict[str, str] | None = None) -> tuple[bytes, str]:
        if url in self.cache:
            return self.cache[url]
        request_headers = {"User-Agent": USER_AGENT, "Accept": "*/*"}
        request_headers.update(headers or {})
        request = urllib.request.Request(url, headers=request_headers)
        with self.opener.open(request, timeout=timeout) as response:
            result = response.read(), response.headers.get_content_type()
        if len(result[0]) <= 2_000_000 and result[1] in ("text/html", "application/xhtml+xml"):
            self.cache[url] = result
        return result


def safe_stem(sku: str) -> str:
    value = re.sub(r"[^a-z0-9]+", "-", sku.lower()).strip("-")
    return value or "product"


def image_from_bytes(data: bytes) -> Image.Image | None:
    try:
        image = Image.open(io.BytesIO(data))
        image.load()
        if image.width < 260 or image.height < 220:
            return None
        if image.width / max(1, image.height) > 3.2 or image.height / max(1, image.width) > 3.2:
            return None
        return image.convert("RGB")
    except Exception:
        return None


def save_webp(image: Image.Image, brand: str, sku: str) -> tuple[str, int]:
    directory = ROOT / "public" / "images" / "products" / brand.lower()
    directory.mkdir(parents=True, exist_ok=True)
    relative = Path("images") / "products" / brand.lower() / f"{safe_stem(sku)}.webp"
    target = ROOT / "public" / relative
    image.save(target, format="WEBP", quality=91, method=6)
    return "/" + relative.as_posix(), target.stat().st_size


def telwin_image(
    fetcher: Fetcher,
    sku: str,
    name: str,
    tristools_url: str | None,
) -> dict[str, Any] | None:
    lookup_sku = sku.split("/", 1)[0]
    if not re.fullmatch(r"\d{6}", lookup_sku):
        return None
    source_page = f"https://www.telwin.com/intl/en/generate-pdf/{lookup_sku}"
    try:
        data, content_type = fetcher.get(source_page)
        if not data.startswith(b"%PDF") and content_type != "application/pdf":
            raise ValueError("Official TELWIN PDF is unavailable")
        reader = PdfReader(io.BytesIO(data))
        candidates: list[tuple[int, Image.Image]] = []
        for page in reader.pages:
            for embedded in page.images:
                image = embedded.image.convert("RGB")
                if image.width < 400 or image.height < 300:
                    continue
                ratio = image.width / max(1, image.height)
                if ratio > 2.4 or ratio < 0.42:
                    continue
                square_bonus = 2_000_000 if 0.75 <= ratio <= 1.33 else 0
                candidates.append((image.width * image.height + square_bonus, image))
        if not candidates:
            raise ValueError("Official TELWIN PDF has no usable product raster")
        product_image = max(candidates, key=lambda candidate: candidate[0])[1]
        path, size = save_webp(product_image, "telwin", sku)
        return {
            "path": path,
            "source_url": source_page,
            "source_page_url": source_page,
            "source_domain": "www.telwin.com",
            "source_type": "official_manufacturer_pdf",
            "is_official": True,
            "width": product_image.width,
            "height": product_image.height,
            "file_size": size,
            "mime_type": "image/webp",
        }
    except Exception:
        pass

    known_image_url = KNOWN_TELWIN_IMAGES.get(sku)
    known_page_url = KNOWN_TELWIN_PAGES.get(sku)
    if known_image_url and known_page_url:
        try:
            known_data, _ = fetcher.get(
                known_image_url,
                timeout=20,
                headers={"Referer": known_page_url, "Accept": "image/avif,image/webp,image/apng,image/*,*/*;q=0.8"},
            )
            known_image = Image.open(io.BytesIO(known_data))
            known_image.load()
            if known_image.width >= 120 and known_image.height >= 120:
                known_image = known_image.convert("RGB")
                path, size = save_webp(known_image, "telwin", sku)
                return {
                    "path": path,
                    "source_url": known_image_url,
                    "source_page_url": known_page_url,
                    "source_domain": "www.bricoutensili.com",
                    "source_type": "verified_exact_reseller",
                    "is_official": False,
                    "width": known_image.width,
                    "height": known_image.height,
                    "file_size": size,
                    "mime_type": "image/webp",
                }
        except Exception:
            pass

    search_name = re.sub(r"\s+Telwin\b", " TELWIN", name, flags=re.I).strip()
    name_tokens = distinctive_name_tokens(search_name)
    groups: list[tuple[str, list[dict[str, str]]]] = []
    known_page = KNOWN_TELWIN_PAGES.get(sku)
    if known_page:
        groups.append(("verified_exact_page", page_image_candidates(fetcher, known_page)))
        known_domain = urllib.parse.urlparse(known_page).hostname or ""
        groups.append(("exact_image_search", bing_image_candidates(fetcher, f'"{sku}" site:{known_domain}')))
    if tristools_url:
        groups.append(("tristools_exact", page_image_candidates(fetcher, tristools_url)))
    groups.append(("exact_image_search", bing_image_candidates(fetcher, f'"{sku}" TELWIN')))
    groups.append(("exact_image_search", bing_image_candidates(fetcher, f'"{search_name[:120]}"')))

    for source_type, candidates in groups:
        for candidate in candidates:
            if source_type not in ("tristools_exact", "verified_exact_page") and not candidate_matches_product(
                fetcher,
                candidate,
                [sku],
                name_tokens,
            ):
                continue
            image = None
            for download_url in (candidate["image_url"], candidate.get("thumbnail_url", "")):
                if not download_url:
                    continue
                try:
                    image_data, image_type = fetcher.get(download_url, timeout=12)
                    if not image_type.startswith("image/") and not download_url.lower().split("?", 1)[0].endswith(IMAGE_EXTENSIONS):
                        continue
                    image = image_from_bytes(image_data)
                    if image is None and download_url == candidate.get("thumbnail_url"):
                        thumbnail = Image.open(io.BytesIO(image_data))
                        thumbnail.load()
                        if thumbnail.width >= 120 and thumbnail.height >= 120:
                            image = thumbnail.convert("RGB")
                    if image is not None:
                        break
                except Exception:
                    continue
            if image is None:
                continue
            path, size = save_webp(image, "telwin", sku)
            domain = urllib.parse.urlparse(candidate["page_url"]).hostname or urllib.parse.urlparse(candidate["image_url"]).hostname
            return {
                "path": path,
                "source_url": candidate["image_url"],
                "source_page_url": candidate["page_url"],
                "source_domain": domain,
                "source_type": source_type,
                "is_official": bool(domain and domain.endswith("telwin.com")),
                "width": image.width,
                "height": image.height,
                "file_size": size,
                "mime_type": "image/webp",
            }
    return None


def page_image_candidates(fetcher: Fetcher, page_url: str) -> list[dict[str, str]]:
    try:
        data, _ = fetcher.get(page_url)
    except Exception:
        return []
    text = data.decode("utf-8", "ignore").replace("\\/", "/")
    urls: list[str] = []
    for pattern in (
        r'<meta[^>]+(?:property|name)=["\'](?:og:image|twitter:image)["\'][^>]+content=["\']([^"\']+)',
        r'<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\'](?:og:image|twitter:image)["\']',
        r'(?:src|data-src|data-original|data-large_image|data-image-large-src|data-full-size-image-url)=["\']([^"\']+\.(?:jpe?g|png|webp)(?:\?[^"\']*)?)',
        r'(https?://[^"\'\s<>]+\.(?:jpe?g|png|webp)(?:\?[^"\'\s<>]*)?)',
    ):
        urls.extend(re.findall(pattern, text, flags=re.I))
    results = []
    for url in urls:
        absolute = urllib.parse.urljoin(page_url, html.unescape(url))
        lowered = absolute.lower()
        if any(token in lowered for token in ("logo", "icon", "banner", "no-image", "placeholder")):
            continue
        if absolute not in [result["image_url"] for result in results]:
            results.append({"image_url": absolute, "page_url": page_url})
    return results[:12]


def bing_image_candidates(fetcher: Fetcher, query: str) -> list[dict[str, str]]:
    url = "https://www.bing.com/images/search?q=" + urllib.parse.quote(query)
    try:
        data, _ = fetcher.get(url)
    except Exception:
        return []
    text = data.decode("utf-8", "ignore")
    results: list[dict[str, str]] = []
    for encoded in re.findall(r'\bm=["\']([^"\']+)["\']', text, flags=re.I):
        try:
            payload = json.loads(html.unescape(encoded))
        except Exception:
            continue
        image_url = str(payload.get("murl") or "")
        page_url = str(payload.get("purl") or "")
        if not image_url.startswith("http") or not page_url.startswith("http"):
            continue
        lowered = image_url.lower()
        if any(token in lowered for token in ("logo", "icon", "placeholder", "no-image")):
            continue
        results.append({
            "image_url": image_url,
            "thumbnail_url": str(payload.get("turl") or ""),
            "page_url": page_url,
            "title": str(payload.get("t") or payload.get("desc") or ""),
        })
    return results[:8]


def manufacturer_code(name: str) -> str | None:
    match = re.search(r"\bCod\.\s*([0-9][0-9A-Z.\-/]*)", name, flags=re.I)
    return match.group(1).rstrip(".") if match else None


def normalized_identity(value: str) -> str:
    return re.sub(r"[^a-z0-9а-яё]", "", value.lower())


def distinctive_name_tokens(name: str) -> list[str]:
    stop = {
        "для", "with", "spin", "cod", "комплект", "набор", "оборудование",
        "инструмент", "универсальный", "профессиональный", "предметов",
    }
    clean = re.sub(r"\s*Cod\..*$", "", name, flags=re.I)
    return [
        token.lower()
        for token in re.findall(r"[A-Za-zА-Яа-яЁё]{5,}", clean)
        if token.lower() not in stop
    ][:8]


def candidate_matches_product(
    fetcher: Fetcher,
    candidate: dict[str, str],
    identities: list[str],
    name_tokens: list[str],
) -> bool:
    summary = " ".join((candidate.get("image_url", ""), candidate.get("page_url", ""), candidate.get("title", "")))
    normalized_summary = normalized_identity(summary)
    normalized_ids = [normalized_identity(value) for value in identities if value]
    if any(value and value in normalized_summary for value in normalized_ids):
        return True

    try:
        page, content_type = fetcher.get(candidate["page_url"], timeout=8)
    except Exception:
        return False
    if content_type not in ("text/html", "application/xhtml+xml"):
        return False
    page_text = html.unescape(re.sub(r"<[^>]+>", " ", page.decode("utf-8", "ignore"))).lower()
    normalized_page = normalized_identity(page_text)
    if any(value and value in normalized_page for value in normalized_ids):
        return True

    matched_tokens = sum(token in page_text for token in name_tokens)
    return len(name_tokens) >= 2 and matched_tokens >= min(3, len(name_tokens))


def spin_image(
    fetcher: Fetcher,
    sku: str,
    name: str,
    tristools_url: str | None,
    probe: bool = False,
) -> dict[str, Any] | None:
    groups: list[tuple[str, list[dict[str, str]], list[str], list[str]]] = []
    if tristools_url:
        groups.append(("tristools_exact", page_image_candidates(fetcher, tristools_url), [sku], []))

    code = manufacturer_code(name)
    search_name = re.sub(r"\s*Cod\..*$", "", name, flags=re.I).strip()
    name_tokens = distinctive_name_tokens(search_name)
    groups.append(("exact_image_search", bing_image_candidates(fetcher, f'"{sku}" SPIN'), [sku, code or ""], name_tokens))
    if code:
        groups.append(("exact_image_search", bing_image_candidates(fetcher, f'"{code}" "{search_name[:90]}"'), [code, sku], name_tokens))
    groups.append(("exact_image_search", bing_image_candidates(fetcher, f'"{search_name[:120]}"'), [sku, code or ""], name_tokens))

    if probe:
        print(json.dumps({"sku": sku, "code": code, "groups": groups}, ensure_ascii=False, indent=2))
        return None

    for source_type, candidates, identities, expected_tokens in groups:
        for candidate in candidates:
            if source_type != "tristools_exact" and not candidate_matches_product(
                fetcher,
                candidate,
                identities,
                expected_tokens,
            ):
                continue
            try:
                data, content_type = fetcher.get(candidate["image_url"], timeout=12)
            except Exception:
                continue
            if not content_type.startswith("image/") and not candidate["image_url"].lower().split("?", 1)[0].endswith(IMAGE_EXTENSIONS):
                continue
            image = image_from_bytes(data)
            if image is None:
                continue
            path, size = save_webp(image, "spin", sku)
            domain = urllib.parse.urlparse(candidate["page_url"]).hostname or urllib.parse.urlparse(candidate["image_url"]).hostname
            return {
                "path": path,
                "source_url": candidate["image_url"],
                "source_page_url": candidate["page_url"],
                "source_domain": domain,
                "source_type": source_type,
                "is_official": bool(domain and (domain.endswith("spinsrl.it") or domain.endswith("spin.it"))),
                "width": image.width,
                "height": image.height,
                "file_size": size,
                "mime_type": "image/webp",
            }
    return None


def load_items(batch_ids: list[int]) -> list[sqlite3.Row]:
    connection = sqlite3.connect(DB_PATH)
    connection.row_factory = sqlite3.Row
    placeholders = ",".join("?" for _ in batch_ids)
    rows = connection.execute(
        f"""
        SELECT i.id AS item_id, i.batch_id, i.sku, i.raw_name, i.brand,
               i.tristools_url, i.created_product_id, i.existing_product_id
        FROM product_parser_items i
        WHERE i.batch_id IN ({placeholders}) AND i.status != 'skipped'
        ORDER BY i.batch_id, i.row_number
        """,
        batch_ids,
    ).fetchall()
    connection.close()
    return rows


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--batches", default="27,28")
    parser.add_argument("--brand", choices=("SPIN", "TELWIN"))
    parser.add_argument("--sku")
    parser.add_argument("--limit", type=int, default=0)
    parser.add_argument("--workers", type=int, default=5)
    parser.add_argument("--probe-spin")
    parser.add_argument("--probe-page")
    args = parser.parse_args()

    fetcher = Fetcher()
    if args.probe_page:
        print(json.dumps(page_image_candidates(fetcher, args.probe_page), ensure_ascii=False, indent=2))
        return 0
    if args.probe_spin:
        connection = sqlite3.connect(DB_PATH)
        connection.row_factory = sqlite3.Row
        item = connection.execute(
            "SELECT sku,raw_name,tristools_url FROM product_parser_items WHERE batch_id=27 AND sku=?",
            (args.probe_spin,),
        ).fetchone()
        connection.close()
        if not item:
            print("SPIN item not found", file=sys.stderr)
            return 2
        spin_image(fetcher, item["sku"], item["raw_name"] or "", item["tristools_url"], probe=True)
        return 0

    batch_ids = [int(value) for value in args.batches.split(",") if value.strip()]
    items = load_items(batch_ids)
    if args.brand:
        items = [item for item in items if (item["brand"] or "").upper() == args.brand]
    if args.sku:
        requested_skus = {value.strip() for value in args.sku.split(",") if value.strip()}
        items = [item for item in items if item["sku"] in requested_skus]
    if args.limit > 0:
        items = items[: args.limit]

    def recover_item(item: sqlite3.Row) -> dict[str, Any]:
        item_fetcher = Fetcher()
        brand = (item["brand"] or "").upper()
        if brand == "TELWIN":
            recovered = telwin_image(item_fetcher, item["sku"], item["raw_name"] or "", item["tristools_url"])
        elif brand == "SPIN":
            recovered = spin_image(item_fetcher, item["sku"], item["raw_name"] or "", item["tristools_url"])
        else:
            recovered = None
        return {
            "item_id": item["item_id"],
            "batch_id": item["batch_id"],
            "sku": item["sku"],
            "brand": brand,
            "found": recovered is not None,
            **(recovered or {}),
        }

    manifest: list[dict[str, Any]] = []
    found = 0
    with concurrent.futures.ThreadPoolExecutor(max_workers=max(1, args.workers)) as executor:
        recovered_records = executor.map(recover_item, items)
        for index, record in enumerate(recovered_records, start=1):
            brand = record["brand"]
            manifest.append(record)
            found += int(record["found"])
            MANIFEST_PATH.parent.mkdir(parents=True, exist_ok=True)
            MANIFEST_PATH.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
            print(f"[{index}/{len(items)}] {brand} {record['sku']}: {'found' if record['found'] else 'missing'}", flush=True)

    MANIFEST_PATH.parent.mkdir(parents=True, exist_ok=True)
    MANIFEST_PATH.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({"total": len(manifest), "found": found, "missing": len(manifest) - found, "manifest": str(MANIFEST_PATH)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
