-- The Brady Group CRM v1.1: deals, showings, message templates, settings, past-client nurture.
-- Run once in Supabase → SQL Editor (after crm.sql). Safe to re-run.

alter table public.contacts add column if not exists birthday date;

-- Timeline gets a "deal" entry type for under contract / closed / fell through.
alter table public.activities drop constraint if exists activities_kind_check;
alter table public.activities add constraint activities_kind_check
  check (kind in ('note', 'call', 'text', 'email', 'meeting', 'form', 'stage', 'deal'));

-- Small key/value settings (default commission %, …)
create table if not exists public.settings (
  key   text primary key,
  value jsonb not null
);
insert into public.settings (key, value) values ('default_commission_pct', '3') on conflict (key) do nothing;

-- A deal per transaction. A contact can have several over the years (bought, later sold).
create table if not exists public.deals (
  id             uuid primary key default gen_random_uuid(),
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now(),
  created_by     uuid default auth.uid(),
  contact_id     uuid not null references public.contacts (id) on delete cascade,
  side           text not null default 'buyer' check (side in ('buyer', 'seller')),
  address        text,
  price          numeric(12, 2) check (price is null or price >= 0),
  commission_pct numeric(5, 3) check (commission_pct is null or (commission_pct >= 0 and commission_pct <= 100)),
  status         text not null default 'under_contract' check (status in ('under_contract', 'closed', 'fell_through')),
  contract_date  date,
  closing_date   date,
  notes          text
);
create index if not exists deals_contact on public.deals (contact_id);
create index if not exists deals_status on public.deals (status, closing_date);

-- Deal status drives the contact's stage and lands on the timeline.
--   under contract → contact Under contract;  closed → contact Closed (→ past client via contacts trigger)
create or replace function public.deals_after() returns trigger language plpgsql as $$
declare
  v_label text := concat_ws(' · ', nullif(new.address, ''), case when new.price is not null then '$' || to_char(new.price, 'FM999,999,999') end);
begin
  if tg_op = 'UPDATE' and new.status = old.status then return null; end if;
  if new.status = 'under_contract' then
    update public.contacts set stage = 'under_contract' where id = new.contact_id and stage is distinct from 'under_contract';
  elsif new.status = 'closed' then
    update public.contacts set stage = 'closed' where id = new.contact_id and stage is distinct from 'closed';
  end if;
  insert into public.activities (contact_id, kind, body, meta)
  values (new.contact_id, 'deal',
          case new.status when 'under_contract' then 'Under contract' when 'closed' then 'Closed' else 'Fell through' end
            || coalesce(': ' || nullif(v_label, ''), ''),
          jsonb_build_object('deal_id', new.id, 'status', new.status));
  return null;
end $$;
drop trigger if exists deals_after on public.deals;
create trigger deals_after after insert or update of status on public.deals
  for each row execute function public.deals_after();
drop trigger if exists deals_touch on public.deals;
create trigger deals_touch before update on public.deals
  for each row execute function public.touch_updated_at();

-- Homes a buyer toured, with their reaction.
create table if not exists public.showings (
  id          uuid primary key default gen_random_uuid(),
  created_at  timestamptz not null default now(),
  created_by  uuid default auth.uid(),
  contact_id  uuid not null references public.contacts (id) on delete cascade,
  shown_on    date not null default current_date,
  address     text not null,
  listing_id  uuid references public.listings (id) on delete set null,
  rating      text check (rating in ('loved', 'maybe', 'no')),
  feedback    text
);
create index if not exists showings_contact on public.showings (contact_id, shown_on desc);

-- Saved messages. {first_name} is filled in when sending.
create table if not exists public.templates (
  id          uuid primary key default gen_random_uuid(),
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now(),
  name        text not null,
  channel     text not null default 'text' check (channel in ('text', 'email')),
  subject     text,
  body        text not null,
  sort        integer not null default 0
);
drop trigger if exists templates_touch on public.templates;
create trigger templates_touch before update on public.templates
  for each row execute function public.touch_updated_at();
insert into public.templates (name, channel, body, sort)
select * from (values
  ('Intro', 'text', 'Hi {first_name}, this is Devin Brady with The Brady Group. Thanks for reaching out! Are you looking to buy, sell, or making a move on orders? When is a good time for a quick call?', 1),
  ('Showing follow-up', 'text', 'Hi {first_name}, thanks for touring homes with me today. Which one stood out to you? Happy to pull numbers or set up a second look whenever you are ready.', 2),
  ('Check-in', 'text', 'Hi {first_name}, Devin here. Just checking in on your home plans. Anything change on timing or what you are looking for? I am here whenever you are ready.', 3),
  ('PCS welcome', 'text', 'Hi {first_name}, welcome to the Seymour Johnson area! I am Devin, an Air Force veteran and REALTOR here in Goldsboro. When is your report date? I can send you neighborhoods near base and walk you through using your VA loan.', 4)
) as t(name, channel, body, sort)
where not exists (select 1 from public.templates);

alter table public.settings  enable row level security;
alter table public.deals     enable row level security;
alter table public.showings  enable row level security;
alter table public.templates enable row level security;
drop policy if exists "Staff all settings" on public.settings;
create policy "Staff all settings" on public.settings for all to authenticated using (true) with check (true);
drop policy if exists "Staff all deals" on public.deals;
create policy "Staff all deals" on public.deals for all to authenticated using (true) with check (true);
drop policy if exists "Staff all showings" on public.showings;
create policy "Staff all showings" on public.showings for all to authenticated using (true) with check (true);
drop policy if exists "Staff all templates" on public.templates;
create policy "Staff all templates" on public.templates for all to authenticated using (true) with check (true);

-- Quarterly check-in: past clients with no contact in 90 days and no follow-up get one for today.
-- Called by the admin when it loads and by the 7am digest. Returns how many were scheduled.
create or replace function public.schedule_nurture() returns integer
language plpgsql security definer set search_path = public as $$
declare
  v_today date := (now() at time zone 'America/New_York')::date;
  n integer;
begin
  update contacts set next_follow_up = v_today
   where category = 'past_client'
     and next_follow_up is null
     and stage is distinct from 'lost'
     and coalesce(last_contacted_at, stage_changed_at, created_at) < now() - interval '90 days';
  get diagnostics n = row_count;
  return n;
end $$;
revoke all on function public.schedule_nurture() from public, anon;
grant execute on function public.schedule_nurture() to authenticated, service_role;
