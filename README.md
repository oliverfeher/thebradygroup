# The Brady Group · bradygrouphomes.com

Astro static site on SiteGround. Listings come live from Supabase, forms email Devin through a small PHP script, and the admin is at **admin.bradygrouphomes.com**.

```
src/pages/          public pages (home, listings, listing, buy, sell, military, about, contact, areas/*)
src/pages/admin/    admin page, self-contained (inline CSS/JS)
src/data/site.ts    contact info, area SEO content, step copy  ← edit copy here
src/styles/         brand styles (navy/ivory, Tinos + Mulish, square corners)
public/api/         lead.php + config.php (who gets lead emails)
supabase/schema.sql database, security rules, photo bucket
```

## 1. Supabase (one time, ~10 min)
1. Create a free project at supabase.com.
2. **SQL Editor** → paste `supabase/schema.sql` → Run. Then run `supabase/seed.sql` to load Devin's current and recent listings.
3. **Authentication → Sign In / Providers**: turn **off** "Allow new users to sign up". Email provider stays on.
4. **Authentication → Users → Add user** (auto-confirm): Devin's email, then the assistant's. Set passwords.
5. **Authentication → URL Configuration**: Site URL = `https://admin.bradygrouphomes.com`. Add the same URL to Redirect URLs (used for password resets).
6. **Project Settings → API**: copy the Project URL and the `anon` public key.

## 2. Local
```bash
cp .env.example .env      # paste the URL + anon key
npm install
npm run dev               # http://localhost:4321  (admin: /admin/)
npm run build             # → dist/
```
Forms need PHP, so they only work once the site is on SiteGround.

## 3. SiteGround
1. **Site Tools → Domain → Subdomains**: create `admin.bradygrouphomes.com`.
2. **Security → SSL Manager**: install Let's Encrypt for both `bradygrouphomes.com` and `admin.bradygrouphomes.com`.
3. Upload files (File Manager or FTP):
   - everything in `dist/` **except** the `admin/` folder → `bradygrouphomes.com/public_html/`
   - `dist/admin/index.html` → `admin.bradygrouphomes.com/public_html/`
   - include the hidden `.htaccess` file.
4. Lead emails go to `devinbrady77@gmail.com` with a BCC to `info@hustle-haus.com` (`public/api/config.php`).
   > If test leads land in spam, create `leads@bradygrouphomes.com` under **Email → Accounts** and set it as `from` in config.php.

### Auto-deploy from GitHub
`.github/workflows/deploy.yml` builds and uploads over FTPS on every push to `main`. Repo secrets (set up and working):
`PUBLIC_SUPABASE_URL`, `PUBLIC_SUPABASE_ANON_KEY` (public key only), `FTP_HOST` = `gvam1031.siteground.biz`
(must match the FTP server's certificate; the domain name fails TLS), `FTP_USER` = `deploy@bradygrouphomes.com`, `FTP_PASSWORD`,
`FTP_SITE_DIR` = `/bradygrouphomes.com/public_html/`, `FTP_ADMIN_DIR` = `/admin.bradygrouphomes.com/public_html/`.
The workflow refuses to upload unless both folders already contain an index.html. FTP account: **Site Tools → Devs → FTP Accounts**.

## 4. DNS (GoDaddy / Namecheap)
Either switch the domain's nameservers to SiteGround's (easiest: SiteGround then manages DNS and email), **or** keep DNS where it is and add:
- `A  @      → <SiteGround IP>`
- `A  www    → <SiteGround IP>`  (or CNAME www → bradygrouphomes.com)
- `A  admin  → <SiteGround IP>`

The IP is under **Site Tools → Dashboard → Site IP**. Changes can take up to a few hours.

## 5. After launch
- Submit `https://bradygrouphomes.com/sitemap-index.xml` in Google Search Console.
- Link the site from Devin's Google Business Profile.
- Replace `/public/assets/hero.png` with a licensed photo, then add real listings in the admin.

## Using the admin (for Devin)
Go to admin.bradygrouphomes.com and sign in. **+ Add listing** takes photos (the first one is the cover), address, price, status and Zillow/Realtor.com links. Change a listing's status from the list, and toggle **Featured** to show up to 3 on the homepage. Changes are live right away.

**TikTok tab:** paste a video link and click **+ Add video**. The caption and thumbnail fill in automatically; type the view count if you want it shown. Use ▲▼ to reorder and **Visible** to hide a video. The first 8 visible videos appear on the homepage.

> Already ran schema.sql before TikTok was added? Run just the `videos` section at the bottom of it.

## CRM (Today / Contacts / Pipeline in the admin)
Website leads land in the CRM automatically and Devin gets a 7am email of who to contact.

**One-time setup:**
1. Supabase **SQL Editor** → paste `supabase/crm.sql` → Run, then `supabase/crm-v1.1.sql` (deals, showings, templates).
2. Supabase **Project Settings → API Keys**: copy the **secret** key (`sb_secret_…`, or the legacy `service_role` key). Never put it in git or the browser.
3. In SiteGround File Manager, create `tbg-secrets.php` **one level above** `public_html` (i.e. `/home/customer/www/bradygrouphomes.com/tbg-secrets.php`):
   ```php
   <?php return ['supabase_url' => 'https://xxxx.supabase.co', 'supabase_key' => 'sb_secret_…'];
   ```
4. **Site Tools → Devs → Cron Jobs**: add, running **every hour** (it only sends at 7am Eastern):
   `php /home/customer/www/bradygrouphomes.com/public_html/api/digest.php`
   To test right away, run it once over SSH with `--now` at the end.
5. Submit a test lead on the site. It should show up under **Today** and in the email with an "Open in CRM" link.

If Supabase is down or not configured, lead emails still go out; check the PHP error log for `TBG CRM ingest failed`.

**TikTok link in bio:** `bradygrouphomes.com/start/`. Leads from that page are tagged TikTok. Any link can tag its source with `?src=instagram` (or `utm_source`).

**For Devin:**
- **Today** is the home screen: new leads to reach out to, overdue and due-today follow-ups, and pipeline leads with no next step. Tap **Call** or **Text**, then log what happened.
- Every contact has one **next follow-up** date. Logging a call/text clears a due follow-up and asks for the next one.
- **+ Add lead** is for TikTok DMs, calls and anyone met in person: name, phone, a couple of taps, done.
- **Contacts → Import** loads iPhone (vCard) or Google (CSV) contacts. Duplicates are skipped.
- **Pipeline**: drag cards between stages. Closing a deal moves the person to past clients.
- On iPhone, open admin.bradygrouphomes.com in Safari → Share → **Add to Home Screen** to use it like an app.
- **Deals** (on a contact): price, commission % and closing date. Today shows pending volume and expected commission; closing a deal makes them a past client.
- **Send a template** (on a contact) opens Messages or Mail with a saved message filled in, and logs it. Edit templates under **Templates**.
- **Showings** (on a buyer): address, how they liked it, and feedback.
- **Past clients**: birthdays and home anniversaries for the coming week show on Today and in the 7am email; anyone not contacted in 90 days gets a check-in follow-up automatically.
- **Stats**: leads by source, how far leads get, and closed volume, by month / year / all time.

## Still to fill in
- `src/data/site.ts → tiktokProfile`: Devin's TikTok profile URL
- `src/data/site.ts → zillowProfile`: Devin's Zillow agent page (the link stays hidden until it's set)
- Have Devin review the area copy and facts in `site.ts`
