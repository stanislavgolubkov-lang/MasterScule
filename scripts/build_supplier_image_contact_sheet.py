#!/usr/bin/env python3
"""Build temporary visual QA sheets from the supplier image manifest."""

import json
import sqlite3
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

root = Path(__file__).resolve().parents[1]
manifest = json.loads((root / "storage/app/parser/supplier-image-manifest.json").read_text(encoding="utf-8"))
connection = sqlite3.connect(root / "database/database.sqlite")
names = dict(connection.execute("SELECT sku, raw_name FROM product_parser_items"))
connection.close()

font_path = Path("C:/Windows/Fonts/arial.ttf")
font = ImageFont.truetype(str(font_path), 15) if font_path.exists() else ImageFont.load_default()
bold_path = Path("C:/Windows/Fonts/arialbd.ttf")
bold = ImageFont.truetype(str(bold_path), 17) if bold_path.exists() else font
records = [record for record in manifest if record.get("found")]

output_dir = root / "tmp/image-qa"
output_dir.mkdir(parents=True, exist_ok=True)
for page_number, offset in enumerate(range(0, len(records), 20), start=1):
    sheet = Image.new("RGB", (1500, 1200), "white")
    draw = ImageDraw.Draw(sheet)
    for position, record in enumerate(records[offset:offset + 20]):
        column = position % 5
        row = position // 5
        x = column * 300
        y = row * 300
        source = root / "public" / record["path"].lstrip("/")
        image = Image.open(source).convert("RGB")
        image.thumbnail((270, 205))
        sheet.paste(image, (x + (300 - image.width) // 2, y + 5 + (205 - image.height) // 2))
        draw.text((x + 8, y + 214), record["sku"], fill="black", font=bold)
        title = names.get(record["sku"], "")
        lines = textwrap.wrap(title, width=35)[:3]
        draw.multiline_text((x + 8, y + 236), "\n".join(lines), fill="#333", font=font, spacing=2)
        draw.rectangle((x, y, x + 299, y + 299), outline="#bbb", width=1)
    target = output_dir / f"supplier-images-{page_number}.jpg"
    sheet.save(target, quality=88)
    print(target)
