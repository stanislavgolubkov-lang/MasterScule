#!/usr/bin/env python3
"""Build large visual-audit sheets for recent supplier products."""

from __future__ import annotations

import hashlib
import json
import sqlite3
import textwrap
from collections import defaultdict
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
DB_PATH = ROOT / "database" / "database.sqlite"
OUTPUT_DIR = ROOT / "tmp" / "deep-audit"
BATCH_BRANDS = {26: "uhl-mash", 27: "spin", 28: "telwin"}


def difference_hash(image: Image.Image) -> str:
    gray = image.convert("L").resize((9, 8))
    pixels = list(gray.getdata())
    bits = []
    for row in range(8):
        offset = row * 9
        bits.extend(pixels[offset + column] > pixels[offset + column + 1] for column in range(8))
    value = sum((1 << index) for index, bit in enumerate(bits) if bit)
    return f"{value:016x}"


def main() -> None:
    connection = sqlite3.connect(DB_PATH)
    connection.row_factory = sqlite3.Row
    connection.text_factory = lambda value: value.decode("utf-8", "replace")
    rows = connection.execute(
        """
        SELECT p.source_import_batch_id AS batch_id, p.id AS product_id, p.sku,
               p.name_ru, p.name_ro, p.main_image, c.name AS category_ru,
               i.raw_name, i.image_source_type, a.source_domain
        FROM products p
        JOIN product_parser_items i ON i.id = p.source_parser_item_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN product_parser_image_assets a
          ON a.parser_item_id = i.id AND a.is_main = 1
        WHERE p.source_import_batch_id IN (26, 27, 28)
        ORDER BY p.source_import_batch_id, i.row_number
        """
    ).fetchall()
    connection.close()

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    font_path = Path("C:/Windows/Fonts/arial.ttf")
    bold_path = Path("C:/Windows/Fonts/arialbd.ttf")
    font = ImageFont.truetype(str(font_path), 16) if font_path.exists() else ImageFont.load_default()
    small = ImageFont.truetype(str(font_path), 14) if font_path.exists() else font
    bold = ImageFont.truetype(str(bold_path), 18) if bold_path.exists() else font

    hash_records = []
    exact_groups: dict[str, list[str]] = defaultdict(list)
    perceptual_groups: dict[str, list[str]] = defaultdict(list)

    for batch_id, brand in BATCH_BRANDS.items():
        batch_rows = [row for row in rows if row["batch_id"] == batch_id]
        for page_number, offset in enumerate(range(0, len(batch_rows), 16), start=1):
            sheet = Image.new("RGB", (1600, 1600), "white")
            draw = ImageDraw.Draw(sheet)
            for position, row in enumerate(batch_rows[offset:offset + 16]):
                column = position % 4
                row_index = position // 4
                x = column * 400
                y = row_index * 400
                source = ROOT / "public" / str(row["main_image"]).lstrip("/")
                try:
                    product_image = Image.open(source).convert("RGB")
                    exact_hash = hashlib.sha256(source.read_bytes()).hexdigest()
                    perceptual_hash = difference_hash(product_image)
                    exact_groups[exact_hash].append(str(row["sku"]))
                    perceptual_groups[perceptual_hash].append(str(row["sku"]))
                    hash_records.append({
                        "batch_id": batch_id,
                        "sku": row["sku"],
                        "image": row["main_image"],
                        "sha256": exact_hash,
                        "dhash": perceptual_hash,
                    })
                    product_image.thumbnail((370, 245))
                    sheet.paste(product_image, (x + (400 - product_image.width) // 2, y + 8 + (245 - product_image.height) // 2))
                except Exception as error:
                    draw.text((x + 12, y + 80), f"BROKEN: {error}", fill="#b91c1c", font=bold)

                draw.text((x + 10, y + 260), str(row["sku"]), fill="#111827", font=bold)
                title = str(row["raw_name"] or row["name_ru"] or "")
                draw.multiline_text(
                    (x + 10, y + 284),
                    "\n".join(textwrap.wrap(title, width=43)[:3]),
                    fill="#1f2937",
                    font=font,
                    spacing=2,
                )
                metadata = f"{row['image_source_type'] or '-'} | {row['source_domain'] or '-'}"
                draw.multiline_text(
                    (x + 10, y + 350),
                    "\n".join(textwrap.wrap(metadata, width=52)[:2]),
                    fill="#6b7280",
                    font=small,
                    spacing=1,
                )
                draw.rectangle((x, y, x + 399, y + 399), outline="#9ca3af", width=1)

            target = OUTPUT_DIR / f"{brand}-{page_number:02d}.jpg"
            sheet.save(target, quality=90)
            print(target)

    duplicates = {
        "exact": {key: value for key, value in exact_groups.items() if len(value) > 1},
        "perceptual": {key: value for key, value in perceptual_groups.items() if len(value) > 1},
        "records": hash_records,
    }
    (OUTPUT_DIR / "image-hashes.json").write_text(
        json.dumps(duplicates, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )


if __name__ == "__main__":
    main()
