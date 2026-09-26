import { supabase } from './supabase';

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

export async function fetchListings(o: { featured?: boolean; cities?: string[]; limit?: number; exclude?: string } = {}) {
  let q = supabase.from('listings').select('*').order('created_at', { ascending: false });
  if (o.cities?.length) q = q.or(o.cities.map((c) => `city.ilike.*${c}*`).join(','));
  const { data, error } = await q;
  if (error) throw error;
  let rows = ((data || []) as Listing[]).filter((l) => l.id !== o.exclude)
    .sort((a, b) => (ORDER[a.status] ?? 9) - (ORDER[b.status] ?? 9));
  if (o.featured) {
    const f = rows.filter((l) => l.featured);
    rows = f.length ? f : rows.filter(isActive);
  }
  return o.limit ? rows.slice(0, o.limit) : rows;
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
