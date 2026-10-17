// Public, read-only Supabase access for the website (listings, TikTok videos).
// Plain fetch against the REST API instead of supabase-js: the client library was ~170 KB of JS
// on every page for a couple of SELECTs. The admin still uses supabase-js (auth, writes, storage).
const URL_BASE = `${import.meta.env.PUBLIC_SUPABASE_URL}/rest/v1/`;
const KEY = import.meta.env.PUBLIC_SUPABASE_ANON_KEY as string;
const headers: Record<string, string> = { apikey: KEY };
// Legacy anon keys are JWTs and go in Authorization too; new sb_publishable_ keys must not.
if (KEY?.startsWith('eyJ')) headers.Authorization = `Bearer ${KEY}`;

/** GET /rest/v1/<table>?<query>, e.g. select('listings', 'select=*&id=eq.123') */
export async function select<T = Record<string, unknown>>(table: string, query = 'select=*'): Promise<T[]> {
  const res = await fetch(URL_BASE + table + '?' + query, { headers });
  if (!res.ok) throw new Error(`Supabase ${table}: ${res.status}`);
  return res.json();
}
