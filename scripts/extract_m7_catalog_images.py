#!/usr/bin/env python3
"""Extract reviewed product panels from the official M7 catalogue."""

from __future__ import annotations

import argparse
from pathlib import Path

import pymupdf


CATALOG_CROPS = {
    "QB-9434W": (140, (250, 465, 420, 625)),
    "QB-9433W": (140, (410, 465, 585, 625)),
    "QB-9211A": (143, (20, 55, 290, 295)),
    "QB-9213A": (143, (285, 55, 575, 295)),
    "QA-611A": (51, (195, 440, 395, 620)),
    "DB-1850": (17, (75, 385, 320, 575)),
    "DRH-101A": (22, (350, 50, 610, 300)),
    "DHG-101A": (25, (310, 535, 610, 790)),
    "DC-18A": (17, (55, 560, 250, 755)),
    "NC-4650KIT": (43, (20, 445, 305, 650)),
    "QE-3B": (73, (20, 290, 205, 480)),
    "QE-3A": (71, (205, 55, 395, 275)),
    "QE-833P02": (72, (210, 265, 415, 460)),
    "SC-9337R": (145, (185, 635, 385, 815)),
    "SC-415": (145, (20, 345, 365, 635)),
    "SC-331-KIT": (84, (20, 255, 400, 620)),
    "RA-505AN02": (99, (20, 40, 580, 215)),
    "QD-221T49": (67, (25, 85, 275, 350)),
    "QD-230T36": (67, (270, 85, 515, 350)),
    "QD-924": (67, (220, 445, 420, 705)),
    "QD-932": (67, (400, 445, 595, 705)),
    "QP-123P31": (64, (395, 355, 605, 595)),
    "QB-9323F": (140, (175, 75, 315, 255)),
    "QB-59642P39": (62, (455, 355, 585, 495)),
}


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("catalog", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--zoom", type=float, default=4.0)
    args = parser.parse_args()

    args.output.mkdir(parents=True, exist_ok=True)
    document = pymupdf.open(args.catalog)

    for sku, (page_number, coordinates) in CATALOG_CROPS.items():
        page = document[page_number - 1]
        clip = pymupdf.Rect(*coordinates) & page.rect
        if clip.is_empty:
            raise RuntimeError(f"Invalid catalogue crop for {sku}")

        pixmap = page.get_pixmap(
            matrix=pymupdf.Matrix(args.zoom, args.zoom),
            clip=clip,
            alpha=False,
        )
        if pixmap.width < 220 or pixmap.height < 220:
            raise RuntimeError(f"Catalogue crop for {sku} is too small")

        pixmap.save(args.output / f"{sku.lower()}.png")

    print(f"Extracted {len(CATALOG_CROPS)} reviewed M7 catalogue images")


if __name__ == "__main__":
    main()
