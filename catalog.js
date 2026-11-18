window.CUSTO_ART = {
  cups: '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M13 13h21l-3 26H16l-3-26Zm3 0-2-5h19l-2 5M19 19h10m-9 6h9m-8 6h7M35 18h5v13h-7"/></svg>',
  outdoors: '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 31c4 0 7-2 9-6l4-8 7 4 5 8 6 3v6H9v-7Zm4 7h27M26 19l-2-4m7 6 4-2M15 34l4 4"/></svg>',
  textile: '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="m16 11 8 4 8-4 9 7-5 7-5-3v17H17V22l-5 3-5-7 9-7Zm8 4v23m-5-18h10"/></svg>',
  service: '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 20 24 9l15 11v18H9V20Zm-3 0h36M18 38V26h12v12m-3-20h5"/></svg>',
};

window.CUSTO_DATA = [
  {
    id: 'green-cup', name: 'Green Cup Collective', category: 'Packaging · Retail & wholesale', kind: 'cups',
    description: 'Compostable cups, lids and takeaway packaging for cafés and events.',
    location: 'Osu, Accra', distance: '2.4 km', rating: '4.8', verified: true,
    keywords: 'eco friendly coffee cups café compostable packaging bulk wholesale',
    about: 'A sample local supplier of compostable takeaway packaging for cafés, restaurants and events. Ask the team about materials, case quantities and custom printing.',
    address: 'Osu, Accra, Ghana', hours: 'Contact the business to confirm hours',
    offerings: [
      { name: 'Compostable hot cups', detail: 'Sample listing · Multiple sizes', kind: 'cups' },
      { name: 'Cold cups & lids', detail: 'Sample listing · Ask about case quantities', kind: 'cups' },
      { name: 'Custom printed packaging', detail: 'Sample listing · Made to order', kind: 'textile' },
    ],
  },
  {
    id: 'trail-house', name: 'Trail House Ghana', category: 'Outdoor · Footwear', kind: 'outdoors',
    description: 'Everyday and trail-ready footwear from a locally run outdoor shop.',
    location: 'Labone, Accra', distance: '3.1 km', rating: '4.7', verified: true,
    keywords: 'waterproof hiking boots shoes outdoor footwear size in stock',
    about: 'A sample outdoor retailer for people getting ready for a trail or a rainy commute. Contact the shop to confirm sizes and current stock.',
    address: 'Labone, Accra, Ghana', hours: 'Contact the business to confirm hours',
    offerings: [
      { name: 'Waterproof hiking boots', detail: 'Sample listing · Check available sizes', kind: 'outdoors' },
      { name: 'Trail day packs', detail: 'Sample listing · Assorted capacities', kind: 'service' },
      { name: 'Rain layers', detail: 'Sample listing · Ask about current range', kind: 'textile' },
    ],
  },
  {
    id: 'woven-roots', name: 'Woven Roots Studio', category: 'Textiles · Custom orders', kind: 'textile',
    description: 'Custom-printed cotton totes and small-batch fabric goods.',
    location: 'East Legon, Accra', distance: '6.8 km', rating: '4.9', verified: true,
    keywords: 'custom printed tote bags cotton textile eco friendly wholesale',
    about: 'A sample textile studio creating custom totes and small-batch fabric goods. Share your preferred quantity, artwork and timing when you get in touch.',
    address: 'East Legon, Accra, Ghana', hours: 'Contact the business to confirm hours',
    offerings: [
      { name: 'Custom cotton tote bags', detail: 'Sample listing · Custom print options', kind: 'textile' },
      { name: 'Branded event bags', detail: 'Sample listing · Made to order', kind: 'textile' },
      { name: 'Small-batch fabric goods', detail: 'Sample listing · Ask about current designs', kind: 'cups' },
    ],
  },
  {
    id: 'cornerstone', name: 'Cornerstone Supply Co.', category: 'Business supplies · Wholesale', kind: 'service',
    description: 'Practical supplies and sourcing support for growing local businesses.',
    location: 'Adabraka, Accra', distance: '1.8 km', rating: '4.6', verified: false,
    keywords: 'supplier business wholesale order bulk custom sourcing',
    about: 'A sample supplier listing for local businesses looking for practical supplies and sourcing support. Ask for a quote based on your quantity and delivery needs.',
    address: 'Adabraka, Accra, Ghana', hours: 'Contact the business to confirm hours',
    offerings: [
      { name: 'Business supply sourcing', detail: 'Sample service · Request a quote', kind: 'service' },
      { name: 'Bulk order support', detail: 'Sample service · Lead times vary', kind: 'cups' },
      { name: 'Product procurement', detail: 'Sample service · Share your requirements', kind: 'outdoors' },
    ],
  },
];
