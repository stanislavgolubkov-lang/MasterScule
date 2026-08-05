#!/usr/bin/env python3
"""Map SPIN price-list items to pages in the official MARCOTOOLS catalog."""

from __future__ import annotations

import json
import re
import sqlite3
from pathlib import Path

from pypdf import PdfReader


ROOT = Path(__file__).resolve().parents[1]
PDF_PATH = ROOT / "tmp" / "pdfs" / "spin-marcotools-2026.pdf"
OUTPUT_PATH = ROOT / "tmp" / "deep-audit" / "spin-official-pages.json"


def manufacturer_code(name: str) -> str | None:
    match = re.search(r"\bCod\.?\s*([0-9][0-9A-Z.\-/]*)", name, flags=re.I)
    return match.group(1).rstrip(".") if match else None


def normalize(value: str) -> str:
    return re.sub(r"[^A-Z0-9]+", "", value.upper())


def main() -> None:
    connection = sqlite3.connect(ROOT / "database" / "database.sqlite")
    connection.text_factory = lambda value: value.decode("utf-8", "replace")
    rows = connection.execute(
        "SELECT sku, raw_name FROM product_parser_items WHERE batch_id = 27 AND status != 'skipped' ORDER BY row_number"
    ).fetchall()
    connection.close()

    reader = PdfReader(PDF_PATH)
    page_texts = []
    for page_number, page in enumerate(reader.pages, start=1):
        text = page.extract_text() or ""
        page_texts.append((page_number, normalize(text)))

    records = []
    for sku, raw_name in rows:
        code = manufacturer_code(raw_name or "")
        normalized_code = normalize(code or "")
        pages = [page_number for page_number, text in page_texts if normalized_code and normalized_code in text]
        records.append({
            "sku": sku,
            "raw_name": raw_name,
            "manufacturer_code": code,
            "pages": pages,
        })

    payload = {
        "pdf": str(PDF_PATH),
        "pages": len(reader.pages),
        "products": len(records),
        "with_code": sum(record["manufacturer_code"] is not None for record in records),
        "matched": sum(bool(record["pages"]) for record in records),
        "records": records,
    }
    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({key: payload[key] for key in ("pages", "products", "with_code", "matched")}, ensure_ascii=False))


if __name__ == "__main__":
    main()
