# The Brady Group — bradygrouphomes.com

Production site for Devin Brady, REALTOR® (USAF veteran), Goldsboro NC. Brokered by The Firm NC.
Built by Hustle Haus (info@hustle-haus.com). Designs were prototyped separately; this repo is the real code.

## Facts
- Phone 316-730-3642 · NC Lic #349189 · Leads email devinbrady77@gmail.com (BCC info@hustle-haus.com)
- Domain bradygrouphomes.com (DNS at GoDaddy/Namecheap) · Host: SiteGround (static files + PHP only, no Node)
- Admin: admin.bradygrouphomes.com (SiteGround subdomain) · Logins: Devin + assistant (Supabase Auth, signups OFF)
- Service areas: Goldsboro, Wayne County, Seymour Johnson AFB, Wilson, Smithfield, Raleigh
- Listings link out to Zillow / Realtor.com — no IDX/MLS feed

## Stack
- Astro 5 static build (`npm run build` → dist/). trailingSlash always, directory format.
- Supabase: `listings` table + `listing-photos` public bucket. Schema + RLS in `supabase/schema.sql` (public read, authenticated write).
- Listings fetched client-side at runtime (instant updates, no rebuild). `src/lib/listings.ts`, `src/components/ListingGrid.astro`.
- Lead forms (`src/components/LeadForm.astro`) POST JSON → `public/api/lead.php` → Supabase `ingest_lead()` RPC (dedupes by phone/email) + PHP mail(). Config in `public/api/config.php`; Supabase secret key in `tbg-secrets.php` above public_html (never in git). Honeypot + rate limit + origin check. Lead source: first-touch `tbg_src` in localStorage set by `src/layouts/Base.astro` (`?src=`/utm/referrer; `/start/` = TikTok link-in-bio forces `tiktok`).
- CRM: `supabase/crm.sql` (`contacts` + `activities`, staff-only RLS, triggers: stage log, New→Contacted on logged touch, closed lead → past_client). Admin UI in `src/components/admin/Crm.astro` (Today / Contacts / Pipeline / contact drawer / quick add / vCard+CSV import), shares `window.TBG` client from admin/index.astro; hash routes `#today #contacts #pipeline #listings #videos #c/<id>`. Daily digest `public/api/digest.php` via SiteGround cron (hourly, sends 7am ET). Alerts = email only (Devin's choice). v1.1 (`supabase/crm-v1.1.sql`): `deals` (status drives contact stage via trigger, logs 'deal' activity), `showings`, `templates` ({first_name}; sent via sms:/mailto: and auto-logged), `settings` (default_commission_pct), contacts.birthday, `schedule_nurture()` RPC (90-day past-client check-ins; called by admin + digest). Screens: `CrmStats.astro`, `CrmTemplates.astro`; Today shows pending volume/commission + birthdays/anniversaries. Texting (Twilio; Devin has an A2P registration, details TBD) and drips are later phases.
- Admin (`src/pages/admin/index.astro`) is self-contained (inline CSS/JS, supabase-js via esm.sh) so dist/admin/index.html uploads alone to the subdomain. Features: sign in/out, reset password, add/edit/delete, multi-photo upload (client-resized to 1800px JPEG), cover order, status, featured ≤3.
- TikTok: `videos` table (url, caption, views text, thumb, visible, sort). Admin → TikTok tab pastes a link; `public/api/tiktok.php` proxies TikTok oEmbed + thumbnail (CORS for admin subdomain) so thumbs are copied into Supabase storage `listing-photos/tiktok/` (TikTok CDN URLs expire). Homepage row: `src/components/TikTokRow.astro` (first 8 visible, hidden if none). Views count is typed manually (oEmbed has no stats).
- Homepage extras (from Eissa Group reference): navy top bar + socials (Nav.astro), quick tiles, stats band (STATS in site.ts: 20 sales, $5.5M, $276.2K avg, $223K–$365K, prior 5 yrs), community cards with drive time to SJAFB (DRIVE), two gated PDF guides in public/guides/ (LeadForm kind 'guide' emails Devin, then reveals download links), reviews (REVIEWS empty = hidden), booking CTA (SITE.bookingUrl).
- Area SEO pages: `src/pages/areas/[slug].astro` from `AREAS` in `src/data/site.ts` (title/meta/canonical, FAQPage + BreadcrumbList + RealEstateAgent JSON-LD). Slugs: goldsboro-nc, wayne-county-nc, seymour-johnson-afb, wilson-nc, smithfield-nc, raleigh-nc.
- Sitemap via @astrojs/sitemap (excludes /admin, /listing/). `public/.htaccess`: HTTPS, non-www, /admin → subdomain, denies config.php.
- Deploy: `.github/workflows/deploy.yml` (FTPS to SiteGround; secrets listed in README).

## Brand rules (strict)
- Colors: Navy #102756 (primary/actions), Brown #49423E (body), Taupe #6B6460 (labels), Gray #9A9591 (hairlines), Ivory #F3F1EE (ground), Paper #fbfaf8 (fields/panels), Black #040303 TEXT ONLY.
- NO black backgrounds. No gradients, no emoji, square corners, hairline gray rules.
- Type: Tinos (serif, uppercase, tracked) for headlines/prices; Mulish for body + uppercase tracked labels.
- Status tags: Just listed/Sold = navy, For sale = taupe, Under contract = brown.
- Every page footer: The Firm NC logo + TBG logo, "Brokered by The Firm NC · Equal Housing Opportunity", license #.
- Logos: public/assets/bg-primary.png (light grounds), bg-reversed.png (navy grounds).

## Status / next
- Not yet launched. Repo oliverfeher/thebradygroup was empty; this folder is the repo root.
- Google Search Console set up by user. Next: GBP (get review link for thank-you card QR), Bing Webmaster (import from GSC), analytics (GA4 or Plausible — not added yet), NAP consistency across Zillow/Realtor/Homes/Facebook/The Firm NC.
- TODO: SITE.socials / bookingUrl / googleReviewsUrl, REVIEWS (3 real Google reviews), `SITE.tiktokProfile` + `SITE.zillowProfile` in src/data/site.ts; replace public/assets/hero.png with licensed photo; Devin to review area copy; confirm service claims (video walkthroughs, lender referrals); if lead mail goes to spam, create leads@bradygrouphomes.com and set `from` in config.php.
- Code hasn't been build-tested yet — run `npm install && npm run build` first and fix any errors.
