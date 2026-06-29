# Apurvi Industries — India Fast-Rank Action Checklist

Everything below is **off-site work that only you can do** — Claude Code has already shipped the on-site SEO. Follow this in order. Most of these take under 10 minutes each.

---

## DAY 1 — Make Google and Bing crawl you (critical)

### 1. Google Search Console
1. Go to https://search.google.com/search-console
2. Add property → URL prefix → `https://apurviind.com`
3. Verify via HTML tag (paste it into `<head>` of `index.html`) or DNS
4. Submit sitemap → enter `sitemap.xml`
5. Use the URL Inspection tool to manually request indexing for:
   - `/` (homepage)
   - `/products`
   - `/service-areas`
   - `/blog/stainless-steel-motor-tubes-submersible-pumps`
   - `/blog/ss304-vs-ss316-stainless-steel-comparison`
   - `/blog/how-to-source-stainless-steel-pipes-india`

### 2. Bing Webmaster Tools
1. Go to https://www.bing.com/webmasters
2. Add site → import from Google Search Console (one-click)
3. Submit sitemap

### 3. IndexNow (instant indexing)
The IndexNow key file is already at `https://apurviind.com/9c0159795a04f63aee2e0d1a2be2401d.txt`.
To ping Bing + Yandex any time you publish a new page, just hit:
```
curl "https://api.indexnow.org/indexnow?url=https://apurviind.com/your-new-page&key=9c0159795a04f63aee2e0d1a2be2401d"
```
Or POST a batch of URLs — see https://www.indexnow.org/documentation

---

## DAY 2 — Google Business Profile (massive for India local SEO)

1. Go to https://www.google.com/business
2. Create a profile for **"Apurvi Industries"** with:
   - Category: **Stainless steel tube supplier** (primary) + **Manufacturer** (secondary)
   - Address: G-1112 Titanium City Center, Prahladnagar Road, Ahmedabad-380015
   - Phone: +91 8128664329
   - Website: https://apurviind.com
   - Hours: Mon–Sat 9:30 AM – 6:30 PM
3. **Create a second profile** for the Mehsana plant (different physical location → separate listing allowed):
   - Survey No. 1383/D/Paiki 1, Village Rajpur, Taluka Kadi, Mehsana-382715
4. Upload 8–12 photos of: facility, products, team, manufacturing floor, finished goods
5. Add 3–5 posts (use product highlights from the website)
6. Verify by postcard (takes 5–14 days — start now)

**Once verified, this single asset alone will drive most India-rank wins for "ss pipe manufacturer in ahmedabad" / "motor tube supplier gujarat" type queries.**

---

## DAY 3–7 — Indian B2B directory citations (NAP backlinks)

Create free listings on these — they all count as authoritative India citations:

| Site | Priority |
|---|---|
| https://www.indiamart.com | **MUST** — biggest B2B in India |
| https://www.tradeindia.com | **MUST** |
| https://www.exportersindia.com | High |
| https://www.justdial.com | High (local search) |
| https://www.sulekha.com | Medium |
| https://www.go4worldbusiness.com | High (export) |
| https://www.exporthub.com | Medium |
| https://www.connect2india.com | Medium |
| https://www.kompass.com | High (international) |
| https://www.thomasnet.com | High (US export) |
| https://www.europages.com | High (EU export) |

**Rule:** Use the **exact same** NAP everywhere. Address, phone, name must match the website character-for-character. Inconsistent NAP kills local SEO.

---

## WEEK 2 — Content & backlinks

### Off-page
- Write 2 LinkedIn posts per week (you already have a strong LinkedIn presence)
- Get listed in trade association directories:
  - https://www.assocham.org
  - https://www.cii.in
  - https://www.gci.org.in (Gujarat Chamber)
  - https://www.jcsai.in (Jaihind Chamber)
- Write 1 guest article per month for industry sites (e.g., Pipes & Tubes Magazine, Industry Outlook)

### Content
- Add 1 blog post per week. High-priority topics:
  - "Submersible pump motor failure: top 5 causes from a pipe manufacturer's view"
  - "How to read a Mill Test Certificate (EN 10204 Type 3.1) — buyer's guide"
  - "Aluminum vapour tubing for petrol pumps: specs & compliance"
  - "Best practices for storing stainless steel pipes before installation"
  - "Pump pipe sizing for borewells: 4-inch vs 6-inch vs 8-inch"
- Re-publish each one as a LinkedIn article 24h after the blog publish

---

## ONGOING — track and iterate

### Weekly
- Check Google Search Console "Performance" — what queries are you ranking for?
- Check "Coverage" — any indexing errors?
- Reply to all GMB reviews within 24h

### Monthly
- Add 1 new product spec page or industry sub-page
- Refresh the `<lastmod>` dates in `sitemap.xml` after any content update
- Run https://pagespeed.web.dev on the homepage — target 90+ mobile

---

## What's already shipped on-site (no action needed)

- `robots.txt`, `sitemap.xml`, `llms.txt`, `manifest.json` at root
- IndexNow key file at root
- All 13 HTML pages have: title, meta description, canonical, Open Graph, Twitter Card, geo meta, robots meta
- Schema graph on homepage: Organization + LocalBusiness (with India service areas, Indian states, 17 Indian cities, hours, payment, sub-org for plant) + WebSite + FAQPage + Speakable
- HowTo schema + 5-FAQ schema on `/products`
- 5-FAQ schema on `/industries`
- ItemList schema on `/products` with 6 products
- BreadcrumbList schema on every internal page
- Article + BreadcrumbList schema on each of 3 blog posts
- ContactPage + Person + AboutPage + Blog + Service schemas on relevant pages
- NAP block (microdata-marked Organization + PostalAddress) in footer of every page
- Hreflang en-IN / en / x-default on homepage
- New `/service-areas` page targeting 50+ Indian cities (the long-tail goldmine)
- 3 in-depth blog posts (1000–1500 words each, Article schema, internal links)
- Image alt text rewritten with target keywords + lazy loading
- Counter defaults filled in (so crawlers see real numbers)
- FAQ section visible on homepage

---

## Expected timeline

| Action | Expected India ranking impact |
|---|---|
| GSC + sitemap submitted | Indexed within 2–7 days |
| GMB verified | Local pack ranking within 2–4 weeks |
| 5+ IndiaMART/TradeIndia citations | Authority signal within 2–4 weeks |
| 3 blog posts indexed + LinkedIn reshares | Long-tail rankings within 4–8 weeks |
| All of the above sustained 90 days | "Stainless steel pipe manufacturer Ahmedabad" type rankings in top 10 |

**Critical:** Google won't rank you until it trusts you. Trust comes from (a) NAP consistency, (b) GMB verification, (c) directory citations, (d) backlinks, (e) time. There is no shortcut to (e). Everything else above accelerates the first four.
