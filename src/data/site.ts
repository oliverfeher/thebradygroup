export const SITE = {
  name: 'The Brady Group',
  agent: 'Devin Brady',
  phone: '316-730-3642',
  phoneHref: 'tel:+13167303642',
  smsHref: 'sms:+13167303642',
  email: 'devinbrady77@gmail.com',
  license: '349189',
  brokerage: 'The Firm NC',
  url: 'https://bradygrouphomes.com',
  // TODO: Devin's Zillow agent profile URL (leave '' to hide the link)
  zillowProfile: '',
};

export const NAV = [
  ['Listings', '/listings/'], ['Buy', '/buy/'], ['Sell', '/sell/'], ['Military', '/military/'],
  ['Areas', '/areas/'], ['About', '/about/'], ['Contact', '/contact/'],
] as const;

export const BUY_STEPS = [
  ['Consult', 'Talk through budget, timing and must-haves so every showing is worth your time.'],
  ['Pre-approval', 'Devin connects you with trusted local lenders so you can offer with confidence.'],
  ['Tour & offer', 'Private showings and an offer strategy built on recent sales nearby.'],
  ['Close', 'Inspection, appraisal and closing, handled with steady updates at every step.'],
];
export const SELL_STEPS = [
  ['Walkthrough & price', 'A walk through your home and a written market analysis within 48 hours.'],
  ['Prepare', 'Clear advice on what to fix, what to leave and how to stage for photos.'],
  ['Market', 'Professional photography, MLS, Zillow, Realtor.com and social promotion.'],
  ['Negotiate & close', 'Disciplined negotiation from first offer through inspection and closing.'],
];
export const MIL_POINTS = [
  ['VA loans', 'Guidance on VA financing and lenders who close VA loans regularly.'],
  ['Buy remotely', 'Video walkthroughs and a clear plan when you can’t be here in person.'],
  ['Near the base', 'Neighborhoods and commutes around Seymour Johnson, explained honestly.'],
  ['Next orders', 'Advice on resale and rental potential for when it’s time to move again.'],
];

export interface Area {
  slug: string; name: string; h1: string; title: string; meta: string; blurb: string;
  cities: string[]; photo?: string; body: string[]; facts: [string, string][];
  hoodsLabel: string; hoods: string[]; faq: [string, string];
}

export const AREAS: Area[] = [
  {
    slug: 'goldsboro-nc', name: 'Goldsboro', h1: 'Goldsboro, NC homes for sale', title: 'Goldsboro, NC Homes for Sale',
    meta: 'Homes for sale in Goldsboro, NC. Devin Brady, REALTOR® and Air Force veteran, helps buyers, sellers and military families across Goldsboro and Wayne County.',
    blurb: 'The Wayne County seat and home to Seymour Johnson AFB. Established neighborhoods, newer subdivisions and a wide range of price points.',
    cities: ['Goldsboro'], photo: '/assets/hero.png',
    body: [
      'Goldsboro sits on the Neuse River in the middle of Wayne County, with US-70 running east to the coast and west to Raleigh. Buyers find older brick ranches near downtown, newer construction on the edges of town, and lake and golf communities nearby.',
      'With Seymour Johnson AFB on the southeast side of town, the market moves with PCS season. Homes near the base and along Berkeley Boulevard tend to see steady demand from military families.',
    ],
    facts: [['County', 'Wayne'], ['To Raleigh', 'About 1 hr'], ['Home to', 'Seymour Johnson AFB'], ['River', 'Neuse']],
    hoodsLabel: 'Neighborhoods',
    hoods: ['Downtown Goldsboro', 'Herman Park area', 'Village of Walnut Creek', 'Rosewood', 'Grantham', 'Berkeley Boulevard corridor'],
    faq: ['Is Goldsboro a good place to live near the base?', 'Many airmen and families live in Goldsboro for the short commute to Seymour Johnson AFB. Devin can walk you through neighborhoods by drive time, school district and budget.'],
  },
  {
    slug: 'wayne-county-nc', name: 'Wayne County', h1: 'Wayne County, NC homes for sale', title: 'Wayne County, NC Homes for Sale',
    meta: 'Homes for sale across Wayne County, NC, including Goldsboro, Mount Olive, Fremont and Pikeville. Work with Devin Brady, a local REALTOR® and Air Force veteran.',
    blurb: 'Beyond Goldsboro: Mount Olive, Fremont, Pikeville and rural homes with acreage.',
    cities: ['Goldsboro', 'Mount Olive', 'Fremont', 'Pikeville', 'Seven Springs', 'Dudley', 'Walnut Creek', 'La Grange'],
    body: [
      'Wayne County mixes small towns with open farmland. Mount Olive, Fremont and Pikeville offer quieter streets and more land for the money, while staying within a short drive of Goldsboro.',
      'If you want acreage, a shop or room for animals, the county is where to look. Devin can help you weigh well and septic, flood zones and commute times before you make an offer.',
    ],
    facts: [['County seat', 'Goldsboro'], ['Setting', 'Small towns & farmland'], ['Major routes', 'US-70 · I-795'], ['Base', 'Seymour Johnson AFB']],
    hoodsLabel: 'Towns & communities',
    hoods: ['Mount Olive', 'Fremont', 'Pikeville', 'Seven Springs', 'Dudley', 'Walnut Creek'],
    faq: ['Can I find land in Wayne County?', 'Yes. Homes on an acre or more are common outside town limits. Devin can help you check well, septic and flood-zone details early.'],
  },
  {
    slug: 'seymour-johnson-afb', name: 'Seymour Johnson AFB', h1: 'Homes near Seymour Johnson AFB', title: 'Homes Near Seymour Johnson AFB',
    meta: 'Homes near Seymour Johnson AFB in Goldsboro, NC. PCS and VA loan guidance from Devin Brady, a REALTOR® and U.S. Air Force veteran.',
    blurb: 'Homes within an easy commute of the base for airmen and families on PCS orders, with VA loan guidance from a fellow veteran.',
    cities: ['Goldsboro', 'Dudley', 'Walnut Creek'],
    body: [
      'Seymour Johnson AFB sits on the southeast side of Goldsboro and is home to the 4th Fighter Wing. Most families live within a 20-minute drive, in Goldsboro, Walnut Creek, Dudley or the neighborhoods west of town.',
      'Devin is an Air Force veteran. He plans your search around your report date, works with lenders who close VA loans regularly, and can run video walkthroughs if you are buying before you arrive.',
    ],
    facts: [['Location', 'Goldsboro, NC'], ['Host unit', '4th Fighter Wing'], ['Common loan', 'VA'], ['Typical commute', 'Under 20 min']],
    hoodsLabel: 'Neighborhoods',
    hoods: ['Downtown Goldsboro', 'Village of Walnut Creek', 'Dudley', 'Rosewood', 'Grantham', 'Berkeley Boulevard corridor'],
    faq: ['Can I use a VA loan near Seymour Johnson AFB?', 'Yes. VA loans are common here. Devin works with lenders who close them regularly and can explain what to expect on appraisal and timelines.'],
  },
  {
    slug: 'wilson-nc', name: 'Wilson', h1: 'Wilson, NC homes for sale', title: 'Wilson, NC Homes for Sale',
    meta: 'Homes for sale in Wilson, NC. Devin Brady, REALTOR®, helps buyers and sellers in Wilson and across Eastern North Carolina.',
    blurb: 'North of Goldsboro along I-795, with a historic downtown and neighborhoods at every stage of life.',
    cities: ['Wilson', 'Elm City'],
    body: [
      'Wilson is about half an hour north of Goldsboro on I-795, with I-95 and US-264 close by. Its historic downtown and Whirligig Park anchor a city with older homes on tree-lined streets and newer subdivisions on the outskirts.',
      'Wilson works well for buyers who split time between Goldsboro, Rocky Mount and the Triangle.',
    ],
    facts: [['County', 'Wilson'], ['To Goldsboro', 'About 30 min'], ['Highways', 'I-95 · I-795 · US-264'], ['Known for', 'Whirligig Park']],
    hoodsLabel: 'Neighborhoods',
    hoods: ['Downtown Wilson', 'West Nash Street', 'Old Wilson Historic District', 'Lake Wilson area', 'Elm City'],
    faq: ['How far is Wilson from Goldsboro?', 'About 30 minutes by car on I-795, which makes Wilson a practical option if you work in Goldsboro or at the base.'],
  },
  {
    slug: 'smithfield-nc', name: 'Smithfield', h1: 'Smithfield, NC homes for sale', title: 'Smithfield, NC Homes for Sale',
    meta: 'Homes for sale in Smithfield and Johnston County, NC. Work with Devin Brady, REALTOR®, between Goldsboro and Raleigh.',
    blurb: 'The Johnston County seat on I-95, between Goldsboro and Raleigh.',
    cities: ['Smithfield', 'Selma', 'Four Oaks', 'Clayton'],
    body: [
      'Smithfield sits where I-95 meets US-70, roughly halfway between Goldsboro and Raleigh. It is a common landing spot for buyers who want Triangle access without Triangle prices.',
      'Nearby Selma, Four Oaks and Clayton round out the Johnston County options, from small-town lots to newer planned neighborhoods.',
    ],
    facts: [['County', 'Johnston'], ['To Raleigh', 'About 35 min'], ['Highways', 'I-95 · US-70'], ['River', 'Neuse']],
    hoodsLabel: 'Towns & communities',
    hoods: ['Downtown Smithfield', 'Selma', 'Four Oaks', 'Clayton'],
    faq: ['Is Smithfield a good middle ground between Goldsboro and Raleigh?', 'For many buyers, yes. It keeps both cities within about 35 minutes while offering more home for the money than Wake County.'],
  },
  {
    slug: 'raleigh-nc', name: 'Raleigh', h1: 'Raleigh, NC homes for sale', title: 'Raleigh, NC Homes for Sale',
    meta: 'Homes for sale in Raleigh, NC. Devin Brady, REALTOR®, helps Eastern North Carolina buyers and sellers moving to or from the Triangle.',
    blurb: 'The state capital, about an hour west of Goldsboro, for buyers who want Triangle access.',
    cities: ['Raleigh', 'Garner', 'Knightdale'],
    body: [
      'Raleigh is about an hour west of Goldsboro on US-70 and I-40. Buyers moving between Eastern North Carolina and the Triangle often need someone who knows both markets.',
      'Devin helps clients sell in Wayne County and buy in the Raleigh area, or the reverse, so both sides of the move stay coordinated.',
    ],
    facts: [['County', 'Wake'], ['To Goldsboro', 'About 1 hr'], ['Highways', 'I-40 · I-440 · US-70'], ['Role', 'State capital']],
    hoodsLabel: 'Towns & communities',
    hoods: ['Garner', 'Knightdale', 'Southeast Raleigh', 'North Raleigh'],
    faq: ['Can you help me sell in Goldsboro and buy in Raleigh?', 'Yes. Devin coordinates both sides so your sale and purchase line up, including timing and contingencies.'],
  },
];
