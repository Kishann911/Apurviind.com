# Changelog — Apurvi Industries website

All notable changes to apurviind.com are documented in this file.
Newest release at top.

---

## 2026-06-30 — International SEO foundation + 3 new blog posts

### Strategic context

This release lays the technical and content foundation for a US + UK
international SEO expansion targeting **400,000 search impressions by end of
August 2026** through a fully organic (no-paid-spend) approach. The play is
to capitalise on two macro tailwinds — US Section 232 tariffs (50%) on
Chinese stainless steel and the UK CBAM phase-in — which are driving US and
UK procurement teams to actively search for non-Chinese suppliers.

### Added — 3 new blog posts

Each post follows the AEO (Answer Engine Optimization) pattern: leads with a
"Quick answer" callout (~80 words) at the top, then deep technical content,
with FAQ schema enabling Google AI Overview, ChatGPT, Perplexity and Bing
Copilot citation.

- **Benefits of Using Stainless Steel Casing in Industrial Pumps**
  `/blog/benefits-of-stainless-steel-casing-in-industrial-pumps`
  ~1,850 words · 9 benefits · cast iron + plastic comparison · 5 industries
  Target keywords: stainless steel pump casing, industrial pump casing
  benefits, corrosion resistant pump casing, SS 304, SS 316.

- **Common Problems in Submersible Pump Pipes and How to Avoid Them**
  `/blog/common-problems-in-submersible-pump-pipes`
  ~2,100 words · 8 problem categories with cause / effect / prevention
  Target keywords: submersible pump pipe issues, pump pipe failure causes,
  industrial pump troubleshooting, stainless steel pump pipe problems.

- **Complete Guide to Submersible Motor Pipes: Types, Uses and Benefits**
  `/blog/complete-guide-to-submersible-motor-pipes`
  ~2,150 words · 3 pipe types (SS / mild steel / PVC) · 5 benefits ·
  manufacturing process · comparison table
  Target keywords: submersible motor pipes, stainless steel submersible
  motor pipe, borewell pump pipes, submersible motor components.

All 3 posts ship with three JSON-LD schema blocks each:
`Article` + `BreadcrumbList` + `FAQPage`. The FAQPage schema is the critical
qualifier for AI Overview citation in 2026.

### Added — AI search foundation

- **`llms-full.txt`** — new file. Full Markdown knowledge dump of Apurvi
  Industries' products, standards, certifications, export markets and the
  2026 tariff context. Probed by Cursor, Claude Code, GitHub Copilot, Cline,
  Aider and increasingly by Bing Copilot when crawling docs for B2B
  buyer / engineer queries.

- **`llms.txt`** — restructured. Now leads with export markets, then the
  US-buyer-angled FAQ block (8 Q&A). First 200 tokens are optimised for
  AEO extraction, since 44% of ChatGPT citations come from the first 30%
  of a page.

- **`robots.txt`** — expanded AI crawler allowlist. Explicit `Allow: /` for
  `OAI-SearchBot`, `ChatGPT-User`, `PerplexityBot`, `Perplexity-User`,
  `Claude-SearchBot`, `Claude-User`, `Google-Extended`, `Applebot-Extended`,
  `Bytespider`, `Amazonbot`, `Diffbot`, `YouBot`, `CCBot`. Without these
  explicit allows, Apurvi pages would be excluded from ChatGPT Search and
  several other AI answer engines.

### Changed — international schema

- **`index.html`** — schema graph extended:
  - `ContactPoint.areaServed` now includes `GB` (United Kingdom)
  - `LocalBusiness.areaServed` adds a United Kingdom `Country` node
  - `currenciesAccepted` extended from `INR, USD, EUR` to `INR, USD, EUR, GBP`
  - All `Country` references in `areaServed` now carry Wikidata `sameAs`
    entity links (US → Q30, UK → Q145, Canada → Q16, Germany → Q183, etc.).
    Improves AI engine entity resolution and disambiguation.

### Changed — content surface

- **`blog.html`** — index updated with three new article cards at the top of
  the grid. Existing 3 posts retain their positions below.

- **`sitemap.xml`** — three new blog URLs added with `lastmod` 2026-06-29 and
  `priority` 0.8. Blog index lastmod bumped to 2026-06-29.

### Added — strategy documents

- **`INTERNATIONAL-SEO-PLAN.md`** — 30-KB master plan synthesising 6
  parallel deep-research agent outputs (keyword research, content strategy,
  authority + link building, technical / AI search, competitor teardown,
  paid + impressions math). Includes the 30 highest-opportunity US/UK
  keywords, 3 content pillars × 8 clusters, 20 blog post titles, 10 lead
  magnets, 32 directory listings, 20 industry publication pitches, and a
  day-by-day 30-day calendar.

- **`Apurvi-SEO-Team-Brief.docx`** — audience-tailored Word brief for the
  SEO + marketing team. Strategy, keywords, content, links, calendar, KPIs,
  off-site action list, risk register.

- **`Apurvi-Tech-Team-Brief.docx`** — audience-tailored Word brief for the
  tech / dev team. Hreflang strategy, schema extensions with JSON-LD
  examples, AEO HTML template, Core Web Vitals 2026, GSC + Bing WMT +
  IndexNow setup, lead-magnet engineering, full list of ~50 URLs to build,
  4-sprint plan with owners.

### Deploy verification (2026-06-30)

The release was packaged and deployed via cPanel on 2026-06-30. Post-deploy
HTTP verification:

| URL | Status |
|---|---|
| `/blog/benefits-of-stainless-steel-casing-in-industrial-pumps` | 200 |
| `/blog/common-problems-in-submersible-pump-pipes` | 200 |
| `/blog/complete-guide-to-submersible-motor-pipes` | 200 |
| `/blog` | 200 |
| `/sitemap.xml` | 200 |
| `/llms-full.txt` | 200 |
| `/robots.txt` | 200 |

IndexNow pings fired and accepted for all 3 new blog URLs + blog index +
sitemap (1× HTTP 202, 4× HTTP 200). This notifies Bing, Yandex, Naver,
Seznam and Yep.

### Post-release manual actions (off-site, owner: marketing)

- [ ] Google Search Console — URL inspect each of the 3 new blog URLs and
      click *Request indexing* (IndexNow does not notify Google).
- [ ] GSC — re-submit `sitemap.xml` to force lastmod refresh.
- [ ] Bing Webmaster Tools — verify the domain (still pending) and add the
      sitemap so AI Performance report becomes available.
- [ ] Spot-check rich results at
      [search.google.com/test/rich-results](https://search.google.com/test/rich-results)
      for one new post — should detect Article + BreadcrumbList + FAQPage.

### Tooling

- **`build_seo_docs.py`** — Python script (uses `python-docx`) that
  generates the two team Word briefs from a single source of truth. Re-run
  with `python3 build_seo_docs.py` if the briefs need updating.

---

## Earlier releases

For releases prior to 2026-06-30, see the git history
(`git log --oneline`).
