-- The Brady Group: run once in Supabase → SQL Editor.

create table if not exists public.listings (
  id           uuid primary key default gen_random_uuid(),
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now(),
  status       text not null default 'Just listed'
               check (status in ('Just listed', 'For sale', 'Under contract', 'Sold')),
  price        integer not null check (price > 0),
  street       text not null,
  city         text not null default 'Goldsboro, NC',
  beds         numeric(3,1),
  baths        numeric(3,1),
  sqft         integer,
  description  text,
  photos       text[] not null default '{}',
  zillow_url   text,
  realtor_url  text,
  featured     boolean not null default false
);

create or replace function public.touch_updated_at() returns trigger language plpgsql as $$
begin new.updated_at = now(); return new; end $$;
drop trigger if exists listings_touch on public.listings;
create trigger listings_touch before update on public.listings
  for each row execute function public.touch_updated_at();

-- Anyone can read listings; only signed-in staff (Devin + assistant) can change them.
-- Turn OFF public sign-ups in Auth settings so "authenticated" means staff only.
alter table public.listings enable row level security;
drop policy if exists "Public read" on public.listings;
create policy "Public read" on public.listings for select using (true);
drop policy if exists "Staff insert" on public.listings;
create policy "Staff insert" on public.listings for insert to authenticated with check (true);
drop policy if exists "Staff update" on public.listings;
create policy "Staff update" on public.listings for update to authenticated using (true) with check (true);
drop policy if exists "Staff delete" on public.listings;
create policy "Staff delete" on public.listings for delete to authenticated using (true);

-- Photo storage
insert into storage.buckets (id, name, public)
values ('listing-photos', 'listing-photos', true)
on conflict (id) do nothing;

drop policy if exists "Public read photos" on storage.objects;
create policy "Public read photos" on storage.objects for select using (bucket_id = 'listing-photos');
drop policy if exists "Staff upload photos" on storage.objects;
create policy "Staff upload photos" on storage.objects for insert to authenticated with check (bucket_id = 'listing-photos');
drop policy if exists "Staff update photos" on storage.objects;
create policy "Staff update photos" on storage.objects for update to authenticated using (bucket_id = 'listing-photos');
drop policy if exists "Staff delete photos" on storage.objects;
create policy "Staff delete photos" on storage.objects for delete to authenticated using (bucket_id = 'listing-photos');
