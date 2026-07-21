# Catalog content audit — 2026-07-21

Scope: all 5,739 products in the local catalog, including RU/RO text, SKU consistency, characteristics, taxonomy, source provenance, and product images.

## Corrections applied

- Removed marketplace SEO copy and contact details mentioning `maximum.md`, `maxim.md`, and `+373(22)54-54-54` from 26 products and their parser records.
- Added a curated RU/RO description for GYS SKU `082809`.
- Removed the parser placeholder `Draft parser preview` from 2,257 package-content records and prevented it from being imported again.
- Corrected seven Romanian records containing Cyrillic or broken encoding.
- Restored the KING TONY brand in five Romanian fields where machine translation had changed it to `REGELE TONY`.
- Corrected copied SKU content for KING TONY `37335-030` and `9AL12` in products and parser records.
- Corrected the average air consumption for `37335-030` from 127 l/min to the official value of 71 l/min.
- Attached official KING TONY sources to the two verified SKU corrections.
- Replaced empty characteristics for all 11 affected products with reviewed RU/RO specifications and corrected Torin `TP04001` from 360 kg to the manufacturer value of 300 kg.
- Replaced the invalid `Stock` characteristic for 12 products with SKU-specific specifications; corrected M7 `SG-911` from 180° to the official 360° swivel angle.
- Retired Maximum and its image CDN from future GYS source discovery. Removed 27 Maximum product source links and all corresponding parser source references; affected marketplace-derived images are now explicitly queued for review.
- Reclassified 70 reviewed products from broad categories into specific assignable categories.
- Rejected and removed an unverified KING TONY `203503` family image after visual inspection showed size H8 instead of the SKU's H3 variant. Official KING TONY images now require the exact SKU in the image URL.
- Rebuilt 373 KING TONY socket cards by exact SKU family: normalized RU/RO names and descriptions, added size, drive, point/profile, and socket-type characteristics, and moved them from screwdrivers/bits into sockets/ratchets.
- Corrected 16 additional KING TONY products against their stored official product sources, including tool sets, flexible handles, thread repair tools, pneumatic ratchets, number punches, tool storage, and extractor socket sets.
- Rebuilt 75 KING TONY bit and holder cards from explicit SKU-family data: normalized RU/RO content, added profile, size, shank/drive, length, and compatibility characteristics, and moved 12 RIBE socket bits out of the broad automotive-special-tools category.
- Rebuilt 55 KING TONY wrench and socket cards: 10 articulated sockets, 33 ratcheting combination wrenches, and 12 impact sockets now have normalized RU/RO content and SKU-specific dimensions, mechanisms, profiles, and drive characteristics.
- Rebuilt 33 KING TONY drive-tool cards: 17 ratchets, seven handles, five universal joints, one adapter, two articulated spark-plug sockets, and one extension now have normalized RU/RO content and explicit drive, length, mechanism, and fitment characteristics.
- Corrected and reclassified 25 KING TONY products that had been placed under torque wrenches or an inconsistent bits category, including Phillips socket bits, T-handle and truck wrenches, adapters, screwdriver and wheel-wrench sets, a diesel compression kit, and a utility cutter.
- Rebuilt 32 GYS accessory cards by explicit SKU family: welding magnets and clamps, wire and slag-cleaning tools, induction-heater conductors, plastic repair rods, TIG filler materials and electrodes, and torch/charger consumables now have normalized RU/RO content and SKU-specific characteristics.
- Rebuilt 19 KING TONY terminal-release and key-set cards: 12 individual `9DT11-*` tools no longer copy the parent set title, seven HEX/TORX sets now expose verified size, profile, material, finish, count, and execution data, and the terminal tools were moved into the electrical/cable-tool category.
- Rebuilt 16 KING TONY SQUAD and lighting cards with normalized RU/RO content and SKU-specific dimensions, compatibility, power, light output, protection, and battery data. Removed the copied 400-lumen AAA headlamp description from `9TA56`; that SKU is a 170-lumen rechargeable LED beanie with a 3.7 V / 250 mAh battery.

## Current automated audit results

| Area | Result |
|---|---:|
| Products | 5,739 |
| Published / drafts | 5,674 / 65 |
| Duplicate SKUs | 0 |
| Missing categories | 0 |
| Non-positive prices | 0 |
| Missing RU names | 0 |
| Missing RO names | 0 |
| Missing RU descriptions | 0 |
| Missing RO descriptions | 0 |
| Romanian text containing Cyrillic | 0 |
| Marketplace domains/contact copy in product text | 0 |
| Exact copied descriptions pointing to another catalog SKU | 0 |
| Marketplace source links in product/parser records | 0 |
| Products passing all publication rules | 4,746 |
| Products blocked by one or more publication rules | 993 |

## Remaining work requiring source review

These counts are review queues, not automatically proven content errors:

- 286 products need content review (down from 833 before the KING TONY passes).
- 806 products need source review; the increase is intentional because 27 Maximum-derived records were returned to the verification queue.
- 268 products have only one characteristic (down from 910); no products are left without characteristics. The GYS portion is 124 and the KING TONY portion fell from 122 to 87.
- Short generic catalog descriptions remain concentrated in JTC, Hoegert, M7, and the unreviewed KING TONY imports.
- 704 products are assigned to broad non-leaf categories; 683 of them are published. The largest broad buckets are now `scule-speciale-auto` (409), `instrument-manual` (139), and `echipamente-pentru-service` (43).

## Images

- 697 products have no main image: 632 KING TONY products and 65 JTC drafts.
- 46 GYS products still use the shared placeholder `/images/products/gys-product.svg`.
- 736 products are explicitly queued for image review. This includes 12 individual KING TONY `9DT11-*` tools that all use the same parent-set photo; the files were visually checked and confirmed byte-identical, so these products now require exact-SKU images before publication.
- 678 published products therefore have no valid unique main image.
- The 4,996 available product images passed the technical size checks: none are below 300 px or 500 px, and none have an extreme aspect ratio.
- 1,179 products share an exact image file with another product. Most are legitimate size/model families, so these must be compared by SKU rather than deleted automatically.

High-priority duplicate-image review samples:

- Hoegert `HT8G058` (jack) and `HT8G063` (stands).
- KING TONY `87301` hook products and `8730`/`8740` clip products.
- KING TONY `7842-28`, `7842-35`, `7842-45`, and `7842-60`, currently split across unrelated categories.
- Hoegert `HT1A755`, `HT1A771`, `HT1A703`, and `HT1A701`, currently sharing one image across joint, adapter, and extension products.

## Characteristics and taxonomy review samples

Suspicious exact characteristic groups spanning unrelated products or categories:

- JTC: `JTC-1322-S1`, `JTC-3520D`, `JTC-3520A`, `JW0573`, `JTC-5702`, `JTC-3216`, `JW0084`, `JTC-3473`.
- JTC: `JTC-1733`, `JTC-1217`, `JTC-5631-20`, `JTC-5631-24`, `JTC-4659`, `JTC-7807`, `JTC-1241`.
- JTC: `JTC-1249`, `JTC-1827`, `JTC-1228`, `JTC-1529`, `JTC-4790`, `JTC-1260`, `JTC-1524`.
- JTC: `JTC-2542`, `JTC-2543`, `PB810`, `JTC-7941`, `JTC-8P110`.
- Hoegert `HT8G947` and KING TONY `9TT11-18`.

## Recommended next pass

1. Recover exact-SKU official images for the 632 KING TONY products, then replace the 46 GYS placeholders.
2. Review the remaining 286 content and 806 source queues by brand, beginning with JTC and the unreviewed KING TONY families.
3. Reclassify the remaining 683 published products in broad categories after checking product semantics and official source data.
4. Review duplicate images and characteristic groups only with SKU-level source confirmation; do not mass-delete shared family assets.

Official verification used for the two corrected KING TONY products:

- `37335-030`: https://www.kingtony.com/product_detail.php?Key=2914&cID=208&uID=75
- `9AL12`: https://www.kingtony.com/product_detail.php?Key=982&cID=661&uID=61
