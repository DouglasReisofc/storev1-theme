# Live validation — 2026-09-15

Target: https://loja1.botadmin.shop/ (test store; indexing enabled later in this session at the user's explicit request).

Installed: StoreV1 Theme 1.11.1; Store Connect 0.9.52.

| Lighthouse category | Mobile before | Mobile after | Desktop before | Desktop after |
|---|---:|---:|---:|---:|
| Performance | 70 | 97 | 85 | 98 |
| Accessibility | 96 | 100 | 96 | 100 |
| Best practices | 100 | 100 | 100 | 100 |
| SEO | 61 | 69 | 61 | 69 |

Final Google PageSpeed fetch times: mobile 2026-09-15T22:29:50.471Z; desktop 2026-09-15T22:29:49.741Z.
Mobile LCP: 12.6s to 2.0s. Desktop LCP: 1.8s to 0.4s. Both TBT: 0ms.
Transfer: approximately 5,024 KiB to 537 KiB. Mobile CLS: 0.008; desktop CLS: 0.022.

## Confirmed

- WordPress confirmed both installations. Theme 1.11.0 to 1.11.1 was updated through its GitHub updater, not manual file upload.
- Theme PHP lint and 16 JS tests passed in GitHub Actions. Plugin PHP validation, updater/webhook tests, eight site-quality checks and four payment-performance tests passed.
- Eleven existing media attachments processed in three batches, zero reported failures. Source images and old thumbnails retained.
- Home, product and category return HTTP 200 with description/canonical metadata. Sensitive commerce pages retain noindex. WordPress visibility was initially unchanged; see the subsequent indexing validation below.
- Real 390x844 mobile and 1365x900 desktop screenshots checked. Mobile search opens above the catalogue with the trigger/banner hidden; searching Mega returns one product; closing restores the catalogue.
- A Music Premium test item was added through the purchase button and removed again. Cart returned to the original five units. No order was placed, no payment initiated.
- Logged-in My Account mobile layout checked. Anonymous login/registration, real payment completion and all third-party plugin combinations were not tested in this run.

## Remaining environment/content work

- The initial sole failing scored SEO audit was `is-crawlable`: `noindex, nofollow`. This was resolved on this installation at the user's explicit request; Search Console submission and actual indexing remain unverified.
- Static asset cache headers are still absent. SSH access to the documented VPS was unavailable with existing keys, so server/CDN config was not changed.
- WordPress/WooCommerce load unminified third-party JS, some unused CSS, and image variants with further compression opportunities. Do not blindly remove dependencies or cache carts to chase a score.
- Original demo product content and site settings are database content, not theme/plugin export data. Replace demo listings and review real product descriptions before indexing.
- Laboratory scores fluctuate; no guaranteed score, indexing or ranking is claimed.

## Follow-up: indexing enabled and PageSpeed rerun

WordPress Reading settings were saved with search-engine discouragement disabled. This is a site setting, not an instruction for the exported theme/plugin to override another installation's visibility.

| Lighthouse category | Mobile | Desktop |
|---|---:|---:|
| Performance | 94 | 96 |
| Accessibility | 100 | 100 |
| Best practices | 100 | 100 |
| SEO | 100 | 100 |

Google fetch times: mobile 2026-09-15T22:36:39.346Z; desktop 2026-09-15T22:36:34.207Z.
Mobile LCP 2.3s, TBT 120ms, CLS 0.008; desktop LCP 0.5s, TBT 0ms, CLS 0.022.
These are new lab measurements, not a claim that enabling indexing caused the performance variation.

HTTP verification: home and Mega product return 200 without noindex; cart and My Account return 200 with noindex retained. The sitemap index and page/product child sitemaps return 200. robots.txt advertises the sitemap and does not block public storefront crawling.

Remaining priorities:

1. Configure static-asset caching at the origin/CDN (PageSpeed estimates 518 KiB lacking useful cache lifetime); never publicly cache commerce sessions.
2. Review responsive product image encoding/sizes (estimated image savings 222 KiB mobile / 244 KiB desktop), preserving legibility and high-density displays.
3. Serve production-minified WordPress/WooCommerce dependencies (estimated JavaScript minification savings 51 KiB) and review blocking/unused assets without breaking cart or payment behavior.
4. Exclude cart, checkout and My Account IDs from the native page sitemap: the currently live sitemap still lists these noindex URLs. No sitemap filter was deployed in this follow-up.
5. Review demo pages/products (including pagina-exemplo) before Search Console submission. No content was deleted or submitted to Google in this follow-up.
