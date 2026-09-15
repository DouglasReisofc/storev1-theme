# Live validation — 2026-09-15

Target: https://loja1.botadmin.shop/ (test store; WordPress indexing remains disabled).

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
- Home, product and category return HTTP 200 with description/canonical metadata. Sensitive commerce pages retain noindex. WordPress staging visibility is unchanged.
- Real 390x844 mobile and 1365x900 desktop screenshots checked. Mobile search opens above the catalogue with the trigger/banner hidden; searching Mega returns one product; closing restores the catalogue.
- A Music Premium test item was added through the purchase button and removed again. Cart returned to the original five units. No order was placed, no payment initiated.
- Logged-in My Account mobile layout checked. Anonymous login/registration, real payment completion and all third-party plugin combinations were not tested in this run.

## Remaining environment/content work

- The sole failing scored SEO audit is `is-crawlable`: intentional `noindex, nofollow`. Publishing the final domain and validating a sitemap/Search Console remains a separate installation decision.
- Static asset cache headers are still absent. SSH access to the documented VPS was unavailable with existing keys, so server/CDN config was not changed.
- WordPress/WooCommerce load unminified third-party JS, some unused CSS, and image variants with further compression opportunities. Do not blindly remove dependencies or cache carts to chase a score.
- Original demo product content and site settings are database content, not theme/plugin export data. Replace demo listings and review real product descriptions before indexing.
- Laboratory scores fluctuate; no guaranteed score, indexing or ranking is claimed.
