# Export and deployment checks

The ZIP does not contain site data, customer orders, payment credentials or a Google API key.

- Three responsive WebP variants of each default banner are included. Original PNG files remain for backward-compatible URLs. The first banner is prioritized; later slides load lazily.
- Custom Media Library images use WordPress responsive sizes. Optimize existing media with Store Connect > Tools > Qualidade da loja; original files and old variants remain recoverable.
- Metadata fallback respects Yoast, Rank Math, AIOSEO, SEOPress and The SEO Framework. Other SEO providers can disable it with `add_filter('storev1_fallback_metadata', '__return_false');`.
- WordPress visibility (`blog_public`) is never changed. Do not index a staging store or sample products. Keep cart, checkout, account and internal search out of search results.
- Serve static files with appropriate long-lived Cache-Control/Expires headers at the web server or CDN. Use versioned asset URLs. Do not publicly cache pages/responses containing sessions, login state, carts, checkout, payment keys or personal account information.
- Check `/wp-sitemap.xml` (or your SEO provider sitemap) and submit only the final public domain to Search Console. Product data and descriptions must be accurate; do not invent reviews or product claims for rich results.
- Verify home, category, product, mobile search, add/remove cart, login and checkout after installing. Run mobile and desktop PageSpeed after cache refresh. A ZIP cannot guarantee a score or Google indexing: hosting, content, plugins and production settings affect it.
