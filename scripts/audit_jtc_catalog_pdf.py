from __future__ import annotations

import json
import re
import sys
from pathlib import Path

from pypdf import PdfReader


SKUS = [
    "JTC-1203",
    "JTC-1228",
    "JTC-1249",
    "JTC-1322-S1",
    "JTC-1529",
    "JTC-1810",
    "JTC-3216",
    "JTC-3473",
    "JTC-4145",
    "JTC-4218",
    "JTC-43930",
    "JTC-4510",
    "JTC-4682",
    "JTC-4790",
    "JTC-5631-20",
    "JTC-5631-24",
    "JTC-5702",
    "JTC-5716A",
    "JTC-6672",
    "JTC-6848",
    "JTC-7804",
    "JW0084",
    "JW0573",
    "JW0832",
]


def normalized(value: str) -> str:
    return re.sub(r"[^A-Z0-9]", "", value.upper())


pdf_path = Path(sys.argv[1])
reader = PdfReader(str(pdf_path))
targets = {sku: normalized(sku) for sku in SKUS}
matches: dict[str, list[int]] = {sku: [] for sku in SKUS}

for page_number, page in enumerate(reader.pages, start=1):
    text = normalized(page.extract_text() or "")
    for sku, token in targets.items():
        if token in text:
            matches[sku].append(page_number)

print(json.dumps({"pages": len(reader.pages), "matches": matches}, indent=2))
