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
2. **SQL Editor** → paste `supabase/schema.sql` → Run.
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

### Auto-deploy from GitHub (optional)
`.github/workflows/deploy.yml` builds and uploads over FTPS on every push to `main`. Add these repo secrets:
`PUBLIC_SUPABASE_URL`, `PUBLIC_SUPABASE_ANON_KEY`, `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`,
`FTP_SITE_DIR` (e.g. `/bradygrouphomes.com/public_html/`), `FTP_ADMIN_DIR` (e.g. `/admin.bradygrouphomes.com/public_html/`).
Get the FTP details from **Site Tools → Devs → FTP Accounts**.

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

## Still to fill in
- `src/data/site.ts → zillowProfile`: Devin's Zillow agent page (the link stays hidden until it's set)
- Have Devin review the area copy and facts in `site.ts`
# thebradygroup
