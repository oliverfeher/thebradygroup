-- The Brady Group CRM: run once in Supabase → SQL Editor (after schema.sql). Safe to re-run.
-- Staff (signed-in users) can read/write everything. The public can't see any of it.
-- Website leads arrive through public/api/lead.php → ingest_lead() using the secret key.

-- Phone numbers are matched on their last 10 digits, so (919) 555-0100 = +1 919-555-0100.
create or replace function public.norm_phone(t text) returns text language sql immutable as $$
  select case when d = '' then null when length(d) = 11 and left(d, 1) = '1' then right(d, 10) else d end
  from (select regexp_replace(coalesce(t, ''), '\D', '', 'g') as d) s
$$;

create table if not exists public.contacts (
  id               uuid primary key default gen_random_uuid(),
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now(),
  created_by       uuid default auth.uid(),
  name             text not null,
  phone            text,
  email            text,
  phone_norm       text generated always as (public.norm_phone(phone)) stored,
  email_norm       text generated always as (lower(nullif(trim(email), ''))) stored,
  -- Who they are
  category         text not null default 'lead'
                   check (category in ('lead', 'past_client', 'sphere', 'vendor')),
  -- What they want
  lead_type        text check (lead_type in ('buyer', 'seller', 'pcs', 'investor', 'renter', 'other')),
  -- Where they are in a deal. Null = not in the pipeline (e.g. sphere, vendors).
  stage            text check (stage in ('new', 'contacted', 'active', 'under_contract', 'closed', 'nurture', 'lost')),
  stage_changed_at timestamptz,
  source           text,          -- tiktok, website, google, referral, open_house, sign_call, import, …
  source_detail    text,          -- campaign / landing page / who referred them
  area             text,
  price_range      text,
  timeline         text,
  address          text,
  base             text,          -- PCS: gaining base
  report_date      date,          -- PCS: report-no-later-than date
  company          text,          -- vendors
  role             text,          -- vendors: lender, inspector, attorney, …
  tags             text[] not null default '{}',
  about            text,          -- pinned background notes
  next_follow_up   date,
  last_contacted_at timestamptz
);

create index if not exists contacts_phone_norm on public.contacts (phone_norm);
create index if not exists contacts_email_norm on public.contacts (email_norm);
create index if not exists contacts_follow_up  on public.contacts (next_follow_up);
create index if not exists contacts_stage      on public.contacts (stage);

create table if not exists public.activities (
  id          uuid primary key default gen_random_uuid(),
  created_at  timestamptz not null default now(),
  created_by  uuid default auth.uid(),
  contact_id  uuid not null references public.contacts (id) on delete cascade,
  kind        text not null check (kind in ('note', 'call', 'text', 'email', 'meeting', 'form', 'stage')),
  body        text,
  meta        jsonb not null default '{}'
);
create index if not exists activities_contact on public.activities (contact_id, created_at desc);

-- updated_at + stage bookkeeping. Closing a lead makes them a past client.
create or replace function public.contacts_before() returns trigger language plpgsql as $$
begin
  new.updated_at = now();
  if tg_op = 'INSERT' then
    if new.stage is not null then new.stage_changed_at = now(); end if;
  elsif new.stage is distinct from old.stage then
    new.stage_changed_at = now();
    if new.stage = 'closed' and new.category = 'lead' then new.category = 'past_client'; end if;
  end if;
  return new;
end $$;
drop trigger if exists contacts_before on public.contacts;
create trigger contacts_before before insert or update on public.contacts
  for each row execute function public.contacts_before();

-- Every stage change lands on the contact's timeline.
create or replace function public.contacts_stage_log() returns trigger language plpgsql as $$
begin
  insert into public.activities (contact_id, kind, body, meta)
  values (new.id, 'stage', null, jsonb_build_object('from', old.stage, 'to', new.stage));
  return null;
end $$;
drop trigger if exists contacts_stage_log on public.contacts;
create trigger contacts_stage_log after update of stage on public.contacts
  for each row when (old.stage is distinct from new.stage) execute function public.contacts_stage_log();

-- Logging a call/text/email/meeting stamps "last contacted" and moves New → Contacted.
create or replace function public.activities_after() returns trigger language plpgsql as $$
begin
  if new.kind in ('call', 'text', 'email', 'meeting') then
    update public.contacts
       set last_contacted_at = greatest(coalesce(last_contacted_at, new.created_at), new.created_at),
           stage = case when stage = 'new' then 'contacted' else stage end
     where id = new.contact_id;
  end if;
  return null;
end $$;
drop trigger if exists activities_after on public.activities;
create trigger activities_after after insert on public.activities
  for each row execute function public.activities_after();

alter table public.contacts enable row level security;
alter table public.activities enable row level security;
drop policy if exists "Staff all contacts" on public.contacts;
create policy "Staff all contacts" on public.contacts for all to authenticated using (true) with check (true);
drop policy if exists "Staff all activities" on public.activities;
create policy "Staff all activities" on public.activities for all to authenticated using (true) with check (true);

-- Website form → contact. Matches an existing contact by phone or email instead of duplicating.
-- A returning contact who was closed/lost/nurture (or never in the pipeline) goes back to New.
create or replace function public.ingest_lead(p jsonb) returns jsonb
language plpgsql security definer set search_path = public as $$
declare
  v_phone    text := nullif(trim(p->>'phone'), '');
  v_email    text := lower(nullif(trim(p->>'email'), ''));
  v_kind     text := p->>'kind';
  v_interest text := coalesce(p->>'interest', '');
  v_today    date := (now() at time zone 'America/New_York')::date;
  v_type     text;
  v_tag      text;
  v_prev     public.contacts;
  c          public.contacts;
begin
  v_type := case
    when v_interest ilike 'sell%' or v_kind = 'valuation' then 'seller'
    when v_interest ilike 'military%' then 'pcs'
    when v_interest ilike 'buy%' or v_kind in ('alerts', 'guide') then 'buyer'
  end;
  v_tag := case v_kind when 'guide' then 'guide download' when 'valuation' then 'home valuation' when 'alerts' then 'listing alerts' end;

  select * into v_prev from contacts
   where (v_phone is not null and phone_norm = norm_phone(v_phone)) or (v_email is not null and email_norm = v_email)
   order by created_at desc limit 1;

  if found then
    update contacts set
      phone          = coalesce(phone, v_phone),
      email          = coalesce(email, v_email),
      lead_type      = coalesce(lead_type, v_type),
      area           = coalesce(area, nullif(p->>'area', '')),
      address        = coalesce(address, nullif(p->>'address', '')),
      tags           = case when v_tag is null or v_tag = any(tags) then tags else tags || v_tag end,
      stage          = case when stage is null or stage in ('closed', 'lost', 'nurture') then 'new' else stage end,
      next_follow_up = least(coalesce(next_follow_up, v_today), v_today)
    where id = v_prev.id returning * into c;
  else
    insert into contacts (name, phone, email, category, lead_type, stage, source, source_detail, area, address, tags, next_follow_up)
    values (
      left(coalesce(nullif(trim(p->>'name'), ''), 'Website lead'), 120), v_phone, v_email, 'lead', v_type, 'new',
      left(coalesce(nullif(p->>'source', ''), 'website'), 40), left(nullif(p->>'source_detail', ''), 200),
      nullif(p->>'area', ''), nullif(p->>'address', ''),
      case when v_tag is null then '{}'::text[] else array[v_tag] end, v_today
    ) returning * into c;
  end if;

  insert into activities (contact_id, kind, body, meta) values (c.id, 'form', p->>'summary', p - 'summary');

  return jsonb_build_object(
    'id', c.id,
    'existing', v_prev.id is not null,
    'prev_stage', v_prev.stage,
    'prev_category', v_prev.category
  );
end $$;
revoke all on function public.ingest_lead(jsonb) from public, anon, authenticated;
grant execute on function public.ingest_lead(jsonb) to service_role;
