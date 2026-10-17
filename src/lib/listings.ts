import { select } from './supabase';

export interface Listing {
  id: string; status: string; price: number; street: string; city: string;
  beds: number | null; baths: number | null; sqft: number | null;
  description: string | null; photos: string[]; zillow_url: string | null; realtor_url: string | null;
  featured: boolean; created_at: string;
}

const ORDER: Record<string, number> = { 'Just listed': 0, 'For sale': 1, 'Under contract': 2, 'Sold': 3 };

export const esc = (s: unknown) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]!));
export const money = (v: number | null) => (v ? '$' + Number(v).toLocaleString('en-US') : '');
export const specs = (l: Listing) => [
  l.beds && `${l.beds} bd`, l.baths && `${l.baths} ba`, l.sqft && `${Number(l.sqft).toLocaleString('en-US')} sq ft`,
].filter(Boolean).join(' · ');
export const isActive = (l: Listing) => l.status === 'Just listed' || l.status === 'For sale';

// featured: homepage picks (or active listings if none are featured).
// fill: top up to `limit` with other listings that have photos (e.g. recent sales) so the row is never sparse.
export async function fetchListings(o: { featured?: boolean; fill?: boolean; cities?: string[]; limit?: number; exclude?: string } = {}) {
  let query = 'select=*&order=created_at.desc';
  if (o.cities?.length) query += '&or=(' + o.cities.map((c) => `city.ilike.*${encodeURIComponent(c)}*`).join(',') + ')';
  const all = (await select<Listing>('listings', query)).filter((l) => l.id !== o.exclude)
    .sort((a, b) => (ORDER[a.status] ?? 9) - (ORDER[b.status] ?? 9));
  let rows = all;
  if (o.featured) {
    const f = all.filter((l) => l.featured);
    rows = f.length ? f : all.filter(isActive);
    if (o.fill && o.limit) rows = [...rows, ...all.filter((l) => !rows.includes(l) && l.photos?.length)];
  }
  return o.limit ? rows.slice(0, o.limit) : rows;
}

export async function fetchListing(id: string) {
  const [row] = await select<Listing>('listings', `select=*&id=eq.${encodeURIComponent(id)}`);
  return row ?? null;
}

export function cardHTML(l: Listing) {
  const photo = l.photos?.[0];
  return `<a class="card" href="/listing/?id=${esc(l.id)}">
    <div class="card-photo">${photo ? `<img src="${esc(photo)}" alt="${esc(l.street)}" loading="lazy">` : ''}<span class="tag" data-s="${esc(l.status)}">${esc(l.status)}</span></div>
    <div class="card-body">
      <div class="price">${money(l.price)}</div>
      <div class="card-addr">${esc(l.street)}, ${esc(l.city)}</div>
      <div class="card-specs">${esc(specs(l))}</div>
    </div>
  </a>`;
}
