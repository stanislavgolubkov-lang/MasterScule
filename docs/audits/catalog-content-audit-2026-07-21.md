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
- Rebuilt 16 HOEGERT cards: eight CrV combination wrenches, six CrMo TORX impact sockets, and two polyurethane spiral air hoses now have normalized RU/RO descriptions and SKU-specific specifications instead of generic category text or producer contact details.
- Rebuilt all 21 remaining one-characteristic M7 cards across cordless tools, impact wrenches, sanders, pneumatic consumables, hose reels, hammer kits, and couplings. Added explicit SKU data and removed generic catalog copy; the M7 one-characteristic queue is now empty.
- Rebuilt all seven remaining one-characteristic Torin BIG RED cards across jack stands, a trolley jack, tire bead seater, industrial vacuum, injector-puller kit, body-repair kit, and hydraulic press. Official Tongrun data corrected T84007 from the imported 98–508 mm/twin-cylinder claim to 100–585 mm with a single pump piston, and corrected TRAD036 from 45 l to the official 10-gallon specification. Added a dedicated industrial-vacuum category and queued three distributor/import-only records plus the DATET-watermarked TRHS-8781 image for review.
- Rebuilt all seven remaining one-characteristic HOEGERT cards from exact official SKU pages: a T-handle ball-end HEX key, swivel bench vise, welding magnet, two EVA tool sets, breathalyzer, and upholstery-clip remover. Corrected HT1W854 from an L-key to its actual T-handle form, added four exact leaf categories, and replaced stale Tristool parser image references for HT3B651, HT8G011, and HT8G393 with their official HOEGERT image URLs after visual verification.
- Rebuilt all six remaining one-characteristic JTC cards with complete RU/RO descriptions and specifications. Four products now reference exact official JTC pages and official image candidates; `JTC-4145` is explicitly marked as discontinued and replaced by `JTC-6816`; `JW0832` remains in source review because its exact manufacturer and kit contents are not confirmed by a primary catalog. Reclassified `JTC-4729` into automotive air-conditioning tools and `JTC-4822` into a new automotive cooling-system leaf.
- Rebuilt 18 KING TONY hand-tool cards from exact official product pages: punch and file sets, plier sets and individual pliers, wire/cable tools, aviation snips, a ratcheting tubing cutter, and a four-size tube bender now have complete RU/RO descriptions and SKU-specific characteristics. Corrected the critical `6411MP` mismatch from end-cutting pliers to the actual 11-piece 3/4″ impact HEX bit-socket set; removed its wrong pliers image, returned the card to draft, and queued the exact official `6411MP` image. Added a dedicated pipe-tools category for `7912-23` and `7CA15-10M`.

## Current automated audit results

| Area | Result |
|---|---:|
| Products | 5,739 |
| Published / drafts | 5,673 / 66 |
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
| Products passing all publication rules | 4,749 |
| Products blocked by one or more publication rules | 990 |

## Remaining work requiring source review

These counts are review queues, not automatically proven content errors:

- 264 products need content review (down from 833 before the curated family passes).
- 805 products need source review. Four JTC records were cleared against exact official manufacturer pages; `JTC-4145` and `JW0832` remain in review because no current primary page confirms those exact records.
- 193 products have only one characteristic (down from 910); no products are left without characteristics. The remaining queue is GYS 124 and KING TONY 69; JTC, HOEGERT, M7, and Torin are now at 0.
- Short generic catalog descriptions remain concentrated in JTC, Hoegert, M7, and the unreviewed KING TONY imports.
- 701 products are assigned to broad non-leaf categories; 680 of them are published. The largest broad buckets are now `scule-speciale-auto` (409), `instrument-manual` (137), and `echipamente-pentru-service` (43).

## Images

- 698 products have no main image: 633 KING TONY products and 65 JTC drafts. The additional KING TONY item is `6411MP`, whose unrelated pliers image was deliberately removed.
- 46 GYS products still use the shared placeholder `/images/products/gys-product.svg`.
- 744 products are explicitly queued for image review. This includes 12 individual KING TONY `9DT11-*` tools that all use the same parent-set photo, KING TONY `6411MP` with its exact replacement image candidate, four HOEGERT products whose stored source image belongs to a neighboring SKU, M7 `SC-2A`/`SC-2B`, whose distinct official source images were processed into one identical local file, and Torin `TRHS-8781`, whose current image carries a DATET watermark. Exact official image candidates are also recorded for `JTC-4181`, `JTC-4338`, `JTC-4729`, and `JTC-4822`, but the cards remain blocked until those files are processed into local main/preview/thumbnail assets.
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

1. Recover exact-SKU official images for the 633 KING TONY products, then replace the 46 GYS placeholders.
2. Review the remaining 264 content and 805 source queues by brand, beginning with the 124 GYS and 69 KING TONY one-characteristic cards.
3. Reclassify the remaining 683 published products in broad categories after checking product semantics and official source data.
4. Review duplicate images and characteristic groups only with SKU-level source confirmation; do not mass-delete shared family assets.

Selected official verification sources used during the curated passes:

- `37335-030`: https://www.kingtony.com/product_detail.php?Key=2914&cID=208&uID=75
- `9AL12`: https://www.kingtony.com/product_detail.php?Key=982&cID=661&uID=61
- `T412002C`: https://en.tongrunjacks.com/products_details/83.html
- `T84007`: https://en.tongrunjacks.com/product/134.html
- `TRAD036`: https://en.tongrunjacks.com/products_details/608.html
- `TY30001`: https://en.tongrunjacks.com/product/868.html
- `HT1W854`: https://en.hoegert.com/product/hexagonal-wrenches-type-t-with-ball-long-4/
- `HT3B618`: https://en.hoegert.com/product/swivel-bench-vise-150-mm/
- `HT3B651`: https://hoegert.com/produkt/magnetyczny-katownik-spawalniczy-strzalkowy-225-kg/
- `HT7G120-1`: https://en.hoegert.com/product/combination-wrench-set-16-pcs-technical-foam-2/
- `HT7G139`: https://en.hoegert.com/product/tool-set-4/
- `HT8G011`: https://en.hoegert.com/product/breathalyzer-with-lcd-display/
- `HT8G393`: https://en.hoegert.com/product/clamp-for-upholstery-pins-230-mm/
- `JTC-4181`: https://eng.jtc.com.tw/product/?id=3865&mode=data
- `JTC-4338`: https://eng.jtc.com.tw/product/?id=4318&mode=data&top=2
- `JTC-4729`: https://eng.jtc.com.tw/product/?id=1577&mode=data&top=2
- `JTC-4822`: https://eng.jtc.com.tw/product/?id=1807&mode=data&top=2
- `JTC-4145` discontinued-record reference: https://specinstrument.ru/catalog/specinstrument/spetsinstrument_dlya_legkovykh_mashin/bmw/semnik_tnvd_bmw_dvig_n47_nov_art_jtc_6816/
- `6411MP`: https://www.kingtony.com/product/11-PC-Impact-Bit-Socket-Set-6411MP
- `7912-23`: https://www.kingtony.com/product/Ratchet-Tubing-Cutter-for-Stainless-Steel-28~67mm-7912-23
- `7CA15-10M`: https://www.kingtony.com/product/90˚-Tube-Bender-7CA15-10M
