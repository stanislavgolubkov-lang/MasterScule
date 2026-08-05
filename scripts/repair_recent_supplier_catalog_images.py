#!/usr/bin/env python3
"""Replace audited supplier images with exact catalogue/product-code matches."""

from __future__ import annotations

import io
import json
import sys
import urllib.request
from pathlib import Path

from PIL import Image, ImageChops
from pypdf import PdfReader


ROOT = Path(__file__).resolve().parents[1]


PDF_URLS = {
    "tmp/pdfs/spin-marcotools-2026.pdf": "https://www.spinsrl.it/wp-content/uploads/2026/06/MARCOTOOLS-2026-web.pdf",
    "tmp/pdfs/spin-2026-it.pdf": "https://www.spinsrl.it/wp-content/uploads/2026/04/Catalogo-SPIN-2026-it-web.pdf",
    "tmp/pdfs/telwin-business-catalogue.pdf": "https://www.telwin.com/it/doc/Cataloghi/BUSINESSCATALOGUE.pdf",
}


PDF_IMAGES = {
    # SPIN / MARCOTOOLS 2026 catalogue: exact manufacturer codes.
    "WT000023L": ("tmp/pdfs/spin-marcotools-2026.pdf", 32, "X249.jpg"),
    "WT000036L": ("tmp/pdfs/spin-marcotools-2026.pdf", 106, "X871.jp2"),
    "WBIAT207A": ("tmp/pdfs/spin-marcotools-2026.pdf", 131, "X1078.jpg"),
    "WT000014P": ("tmp/pdfs/spin-marcotools-2026.pdf", 137, "X1133.jp2"),
    "WT000102R": ("tmp/pdfs/spin-marcotools-2026.pdf", 153, "X1246.jpg"),
    "WT000102S": ("tmp/pdfs/spin-marcotools-2026.pdf", 153, "X1245.jpg"),
    "WTEC02895": ("tmp/pdfs/spin-marcotools-2026.pdf", 165, "X1341.jp2"),
    # SPIN 2026 catalogue: exact headlight tester models.
    "WCFHL2907": ("tmp/pdfs/spin-2026-it.pdf", 72, "X706.jpg"),
    "WCFHL26D2": ("tmp/pdfs/spin-2026-it.pdf", 72, "X710.jpg"),
    # TELWIN 2026 catalogue: actual multi-plug power supply, not a logo or box.
    "169999": ("tmp/pdfs/telwin-business-catalogue.pdf", 198, "Im1.jp2"),
}


WEB_IMAGES = {
    # Exact official SPIN product page.
    "WTSPURF10": "https://www.spinsrl.it/wp-content/uploads/2023/10/marcotools-03.036.35-spurgafreni-00w-1024x1024.jpg",
    # Exact TELWIN catalogue accessories or supplier cards matched by manufacturer code.
    "801043": "https://www.telwin.com/TELWIN/FOTO/ACCESSORI/2460/image-thumb__2460__productDetailsThumbnailImage/801043@2x.webp",
    "804150": "https://tristool.md/uploaded_files/804150.jpg",
    "125182": "https://cdn.lincos.io/images/66886.jpg?width=1000&height=1000&quality=80",
    "802295": "https://c.cdnmp.net/144131701/p/l/0/saibe-pentru-sudura-in-puncte-telwin-802295-din-otel-8x16-mm~169990.jpg",
    # Exact Tristool product images for the four 05.090.72 replacement screws.
    "WZRIC0667": "https://tristool.md/uploaded_files/JTC-4091-M10.jpg?1613831880",
    "WZRIC0668": "https://tristool.md/uploaded_files/JTC-4091-M12.jpg?1613832011",
    "WZRIC0669": "https://tristool.md/uploaded_files/JTC-4091-M14.jpg?1613832055",
    "WZRIC0670": "https://tristool.md/uploaded_files/JTC-4091-M16.jpg?1613832095",
    "WT15CR005M12": "https://tristool.md/uploaded_files/RP1016.01.jpg?1670595171",
    "WT15CR005R": "https://tristool.md/uploaded_files/01.000.168IMP_R1234a_red.jpg?1659602203",
    "WT15CR005B": "https://tristool.md/uploaded_files/01.000.168IMP_R1234a_blu.jpg?1659602255",
    # Exact Tristool cards for UHL-MASH models that were previously assigned family photos.
    "41О2": "https://tristool.md/uploaded_files/verstak_beztumbovy_180_1_305_4-1920x1080.jpg?1731246424",
    "31О2": "https://tristool.md/uploaded_files/verstak_beztumbovy_180_1_305_4-1920x1080.jpg?1731246424",
    "Универсалисп.1/2": "https://tristool.md/uploaded_files/stellazhi_dlya_metizov_universal.jpg?1733307792",
    "МС1970x1000х400": "https://tristool.md/uploaded_files/%D0%9C%D0%A1%201970x1000%D1%85400.jpg?1674492063",
    "СТ-4/4M600": "https://tristool.md/uploaded_files/stellazh_st_usilennij_750x300x1820.jpg?1636805759",
    "СК2,0/1000х300": "https://tristool.md/uploaded_files/stellazh_sk_750kh300kh2000.jpg?1714149751",
    "СТ-4/2Муп_": "https://tristool.md/uploaded_files/stellazh_modulnyy_polochnyy.jpg?1701531704",
    # Tristool gallery details are more accurate than showing the complete workbench.
    "31": "https://tristool.md/uploaded_files/verstak_opora_logo-1920x1080.jpg?1731252752",
    "ВТ-210Г": "https://tristool.md/uploaded_files/opora_polka-1920x1080.jpg?1731252738",
    "15Г": "https://tristool.md/uploaded_files/opora_polka-1920x1080.jpg?1731252738",
    "18Г": "https://tristool.md/uploaded_files/opora_polka-1920x1080.jpg?1731252738",
    "636": "https://tristool.md/uploaded_files/opora_polka-1920x1080.jpg?1731252738",
    "636-1,08": "https://tristool.md/uploaded_files/opora_polka-1920x1080.jpg?1731252738",
}


UHL_OUTPUTS = {
    "41О2": ROOT / "public/images/products/uhl-mash/41-2-9561bb7a.webp",
    "31О2": ROOT / "public/images/products/uhl-mash/31-2-aa7645fe.webp",
    "Универсалисп.1/2": ROOT / "public/images/products/uhl-mash/1-2-0a692119.webp",
    "МС1970x1000х400": ROOT / "public/images/products/uhl-mash/1970x1000-400-697e708b.webp",
    "СТ-4/4M600": ROOT / "public/images/products/uhl-mash/4-4m600-ddeb4975.webp",
    "СК2,0/1000х300": ROOT / "public/images/products/uhl-mash/2-0-1000-300-aa268d08.webp",
    "СТ-4/2Муп_": ROOT / "public/images/products/uhl-mash/4-2-076e9b53.webp",
    "31": ROOT / "public/images/products/uhl-mash/31.webp",
    "ВТ-210Г": ROOT / "public/images/products/uhl-mash/210-f0272f3c.webp",
    "15Г": ROOT / "public/images/products/uhl-mash/15-16a63042.webp",
    "18Г": ROOT / "public/images/products/uhl-mash/18-eb03711d.webp",
    "636": ROOT / "public/images/products/uhl-mash/636.webp",
    "636-1,08": ROOT / "public/images/products/uhl-mash/636-1-08.webp",
}


OUTPUTS = {
    **{sku: ROOT / "public/images/products/spin" / f"{sku.lower()}.webp" for sku in PDF_IMAGES},
    "WTSPURF10": ROOT / "public/images/products/spin/wtspurf10.webp",
    **{sku: ROOT / "public/images/products/spin" / f"{sku.lower()}.webp" for sku in ("WZRIC0667", "WZRIC0668", "WZRIC0669", "WZRIC0670", "WT15CR005M12", "WT15CR005R", "WT15CR005B")},
    **UHL_OUTPUTS,
    **{sku: ROOT / "public/images/products/telwin" / f"{sku.lower()}.webp" for sku in ("801043", "804150", "125182", "169999", "802295")},
}


def pdf_image(pdf_path: Path, page_number: int, image_name: str) -> Image.Image:
    page = PdfReader(str(pdf_path)).pages[page_number - 1]
    for image_file in page.images:
        if image_file.name == image_name:
            return image_file.image.copy()
    raise RuntimeError(f"Image {image_name} not found on page {page_number} of {pdf_path}")


def web_image(url: str) -> Image.Image:
    request = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 MasterScule catalogue audit"})
    with urllib.request.urlopen(request, timeout=45) as response:
        return Image.open(io.BytesIO(response.read())).copy()


def ensure_pdf(path: Path, url: str) -> None:
    if path.is_file():
        return

    path.parent.mkdir(parents=True, exist_ok=True)
    request = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 MasterScule catalogue audit"})
    with urllib.request.urlopen(request, timeout=120) as response:
        path.write_bytes(response.read())


def trim_background(image: Image.Image) -> Image.Image:
    image = image.convert("RGBA")
    alpha = image.getchannel("A")
    if alpha.getextrema()[0] < 255:
        bbox = alpha.getbbox()
        return image.crop(bbox) if bbox else image

    rgb = image.convert("RGB")
    background = Image.new("RGB", rgb.size, rgb.getpixel((0, 0)))
    difference = ImageChops.difference(rgb, background).convert("L")
    difference = difference.point(lambda value: 255 if value > 12 else 0)
    bbox = difference.getbbox()
    return image.crop(bbox) if bbox else image


def product_canvas(image: Image.Image) -> Image.Image:
    image = trim_background(image)
    scale = min(900 / max(1, image.width), 900 / max(1, image.height))
    target_size = (max(1, round(image.width * scale)), max(1, round(image.height * scale)))
    image = image.resize(target_size, Image.Resampling.LANCZOS)
    canvas = Image.new("RGB", (1000, 1000), "white")
    x = (canvas.width - image.width) // 2
    y = (canvas.height - image.height) // 2
    if image.mode == "RGBA":
        canvas.paste(image, (x, y), image)
    else:
        canvas.paste(image.convert("RGB"), (x, y))
    return canvas


def main() -> int:
    requested = {
        value.strip().upper()
        for argument in sys.argv[1:]
        if argument.startswith("--skus=")
        for value in argument.split("=", 1)[1].split(",")
        if value.strip()
    }
    completed: list[dict[str, object]] = []

    for sku, (relative_pdf, page, name) in PDF_IMAGES.items():
        if requested and sku.upper() not in requested:
            continue
        source = ROOT / relative_pdf
        ensure_pdf(source, PDF_URLS[relative_pdf])
        image = pdf_image(source, page, name)
        output = OUTPUTS[sku]
        output.parent.mkdir(parents=True, exist_ok=True)
        product_canvas(image).save(output, "WEBP", quality=92, method=6)
        completed.append({"sku": sku, "source": str(source), "page": page, "image": name, "output": str(output)})

    for sku, url in WEB_IMAGES.items():
        if requested and sku.upper() not in requested:
            continue
        image = web_image(url)
        output = OUTPUTS[sku]
        output.parent.mkdir(parents=True, exist_ok=True)
        product_canvas(image).save(output, "WEBP", quality=92, method=6)
        completed.append({"sku": sku, "source": url, "output": str(output)})

    print(json.dumps({"replaced": len(completed), "products": completed}, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())
