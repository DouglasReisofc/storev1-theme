# Account and mobile search — 1.12.1

Entrar and Criar conta use dedicated page templates and WooCommerce native form hooks/nonces, validation, password settings and redirects. Select the templates on published pages to use custom URLs; otherwise links fall back to My Account with an account-view query.

The My Account registration switch controls both form/link visibility and the native registration POST handler, including stale forms. Checkout customer creation, REST and admin workflows are not overridden. Existing authenticated customers use the normal account dashboard. Auth pages remain noindex and send no-cache headers.

Mobile search no longer focuses the text input automatically. The 38px input and circular close button remain sticky while browsing the results. A vertical touch gesture over results, mouse wheel or Enter dismisses input focus without blocking native scrolling. Opening search preserves the header; the banner alone is hidden, while the main catalogue is replaced by search results.

## Artwork

Created with the built-in image editor, not the API fallback. Optimized WebP derivatives are shipped alongside source PNGs.

- `assets/banners/storev1-gold-piggybank.png`: edit the original banner; change only "nosso" to "nossa", preserving layout, piggybank, coins, typography and all other text. Final sentence: "Tenha os melhores produtos por um valor muito acessível em nossa store".
- `assets/account-login-illustration.png`: gold-and-black hand-drawn ecommerce illustration, customer at a laptop with a golden user profile shield and small shopping icons, white background, no text or watermark, horizontal 3:2.
- `assets/account-register-illustration.png`: matching gold-and-black illustration, customer holding a golden key beside a signup card and shopping bag, white background, no text or watermark, horizontal 3:2.

Banner URLs are versioned together with their preload to avoid showing the previous text from the static-asset cache.

## Validation on loja1.botadmin.shop

- 18 JavaScript regression tests and 9 PHP account-switch tests pass; PHP files lint clean.
- Browser layouts checked at 390px mobile and 1365px desktop. The corrected first banner reads "em nossa store" on the live site.
- Enabled, disabled and re-enabled My Account registration through the WooCommerce settings screen. Links and forms followed the switch. A stale registration form submitted after disabling was rejected by the server; no test account was created.
- Restored the original configuration: My Account registration disabled, automatic username and password generation enabled. Checkout settings were not changed.
- Native empty-login validation and password recovery navigation checked. No real successful login, email delivery or new account creation was exercised.
- Search opens without focusing the field; all 11 products remain scrollable before typing. The 38px field stays at the top while scrolling. Filtering to one result returns the field into view; closing restores the banner.
- Focus dismissal verified through scrolling and automated gesture tests. Physical Android/iOS keyboard behavior remains unverified.

Dedicated live pages: `/entrar/` and `/criar-conta/`. The latter displays login when registration is disabled. The original `/my-account/` URL is preserved and titled "Minha conta".
