# Catalog category agent

The catalog uses a canonical taxonomy and a fail-closed category agent. Direct category pages never borrow products from parent or task collections.

## Automatic operation

- New or materially updated products are classified after the database transaction commits.
- Without `OPENAI_API_KEY`, strict deterministic rules run synchronously and require at least 0.97 confidence.
- With `OPENAI_API_KEY`, classification and independent verification use structured OpenAI responses and require both checks to pass.
- Rejected decisions never move a product. Every applied, confirmed, or rejected decision is stored in `product_category_decisions`.
- Unknown source breadcrumbs cannot create public categories.
- Parser category learning is accepted only from `admin_verified` or `catalog_agent_verified` sources and only by exact SKU.
- `CATALOG_AI_AUTO_APPLY` is enabled for this catalog. Only decisions that pass the strict confidence threshold and all validation guards can move a product; ambiguous decisions remain rejected.
- Canonical tree synchronization and a read-only integrity audit run nightly.

## Commands

Read-only preview:

```shell
php artisan masterscule:reclassify-catalog --no-ai
```

Apply only validated changes:

```shell
php artisan masterscule:reclassify-catalog --apply --force --changed
```

Preview or synchronize the canonical tree:

```shell
php artisan masterscule:sync-catalog-taxonomy
php artisan masterscule:sync-catalog-taxonomy --apply
```

Move deprecated aliases to canonical categories and run the integrity audit:

```shell
php artisan masterscule:migrate-catalog-aliases
php artisan masterscule:migrate-catalog-aliases --apply --force
php artisan masterscule:audit-catalog-taxonomy --json
```

Inspect one product or transition:

```shell
php artisan masterscule:reclassify-catalog --product=SKU-123
php artisan masterscule:reclassify-catalog --from=old-slug --to=new-slug --show=50
```

## Optional OpenAI verification

Set `OPENAI_API_KEY`, keep `CATALOG_AI_ENABLED=true`, and run a queue worker for asynchronous AI checks. Models, thresholds, timeouts, and taxonomy version are configured through the `CATALOG_AI_*` environment variables documented in `.env.example`.

Laravel Scheduler must be running in production (`php artisan schedule:work`, or one `schedule:run` call per minute). A queue worker is only required when an OpenAI key is configured.

When category rules change, bump `CATALOG_AI_TAXONOMY_VERSION`, temporarily disable `CATALOG_AI_AUTO_APPLY`, run the read-only reclassification and integrity audit, and re-enable auto-apply only after the dry-run has no unsafe accepted transitions. The next scheduled run will then re-evaluate changed products with the same confidence and validation gates.
