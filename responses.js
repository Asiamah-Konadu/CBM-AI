/**
 * responses.js — Custo.AI Response Template Engine
 *
 * Architecture:
 *  1. INTENT DETECTION  — classifies the query into one of 7 intents
 *  2. USER PROFILING    — builds a lightweight per-session profile from
 *                         search history (tone, specificity, recency)
 *  3. TEMPLATE BANK     — 10 distinct templates per intent (70 total)
 *  4. SELECTION LOGIC   — picks the best template using profile signals
 *  5. SLOT FILLING      — replaces {{placeholders}} with live values
 */

// ─── 1. INTENT DETECTION ────────────────────────────────────────────────────

const INTENT_PATTERNS = {
  /** User mentions a price ceiling, budget, or cost */
  budget: /under|below|cheap|budget|affordable|low.?cost|price|cost|ghc|ghs|\$|less than/i,

  /** User is asking for bulk / wholesale / supplier quantities */
  bulk: /bulk|wholesale|supplier|quantity|quantities|case|stock|500|100[0-9]*|order/i,

  /** User wants something eco-friendly / sustainable */
  eco: /eco|green|sustainable|compostable|biodegradable|organic|recycl|environ/i,

  /** User wants custom / branded / printed items */
  custom: /custom|print|brand|logo|personalise|personalize|design|made.?to.?order/i,

  /** User wants outdoor / sports / footwear */
  outdoor: /hik|trail|boot|outdoor|sport|footwear|waterproof|shoe|trek/i,

  /** User wants textiles / fabric / clothing / bags */
  textile: /tote|bag|cloth|fabric|textile|shirt|wear|cotton|linen/i,

  /** Catch-all — general product or service lookup */
  general: /.*/,
};

/** Returns the first matching intent key for a given query string */
function detectIntent(query) {
  for (const [intent, pattern] of Object.entries(INTENT_PATTERNS)) {
    if (pattern.test(query)) return intent;
  }
  return 'general';
}

// ─── 2. USER PROFILING ──────────────────────────────────────────────────────

/**
 * Session profile stored in memory.
 * Resets when the page is reloaded (no persistence by design for this demo).
 */
const userProfile = {
  searchCount: 0,       // total queries this session
  intents: [],          // ordered list of past intents
  avgQueryLength: 0,    // rolling avg of query word-count
  lastIntent: null,
  isReturning: false,   // true after the first search
  verbosity: 'normal',  // 'brief' | 'normal' | 'detailed'
  tone: 'neutral',      // 'casual' | 'neutral' | 'professional'
};

/** Call this BEFORE selecting a template so profile reflects current query */
function updateProfile(query) {
  const wordCount = query.trim().split(/\s+/).length;
  userProfile.searchCount += 1;
  userProfile.isReturning = userProfile.searchCount > 1;

  // Rolling average query length
  userProfile.avgQueryLength =
    Math.round(
      (userProfile.avgQueryLength * (userProfile.searchCount - 1) + wordCount) /
        userProfile.searchCount
    );

  // Verbosity: short queries → brief; long queries → detailed
  if (userProfile.avgQueryLength <= 4) userProfile.verbosity = 'brief';
  else if (userProfile.avgQueryLength >= 10) userProfile.verbosity = 'detailed';
  else userProfile.verbosity = 'normal';

  // Tone heuristics from vocabulary
  const casualMarkers = /hey|hi|please|thanks|cool|awesome|wanna|gonna|need/i;
  const proMarkers = /require|specification|procurement|sourcing|vendor|volume/i;
  if (proMarkers.test(query)) userProfile.tone = 'professional';
  else if (casualMarkers.test(query)) userProfile.tone = 'casual';
  else userProfile.tone = 'neutral';

  const intent = detectIntent(query);
  userProfile.lastIntent = intent;
  userProfile.intents.push(intent);
}

// ─── 3. TEMPLATE BANK ───────────────────────────────────────────────────────
/**
 * Each entry is a function that receives a context object and returns
 * the intro string shown above the result cards.
 *
 * Context shape:
 *   { count, query, intent, profile, firstMatch }
 *
 *   count      – number of matching listings
 *   query      – raw user query string
 *   intent     – detected intent key
 *   profile    – the live userProfile object
 *   firstMatch – the top-ranked listing object (or null)
 */

const TEMPLATES = {
  // ── BUDGET ────────────────────────────────────────────────────────────────
  budget: [
    ({ count, firstMatch }) =>
      `Found ${count} price-conscious option${count !== 1 ? 's' : ''} in Accra. ${firstMatch ? `${firstMatch.name} looks like a strong starting point — worth getting a quote.` : ''}`,

    ({ count }) =>
      `Keeping it affordable — here ${count === 1 ? 'is' : 'are'} ${count} local option${count !== 1 ? 's' : ''} that may fit your budget. Always confirm pricing directly with the seller.`,

    ({ count, firstMatch }) =>
      `${count} nearby business${count !== 1 ? 'es' : ''} matched your budget search. ${firstMatch ? `${firstMatch.name} (${firstMatch.distance}) is the closest — tap to view their full catalogue.` : 'Contact them to confirm current prices.'}`,

    ({ count }) =>
      `Price matters. I pulled ${count} local listing${count !== 1 ? 's' : ''} that could meet your cost requirements. Ask each seller for a live quote before committing.`,

    ({ count, firstMatch }) =>
      `${count} result${count !== 1 ? 's' : ''} for your budget-focused search. ${firstMatch ? `Top pick: ${firstMatch.name} — rated ${firstMatch.rating}★ and ${firstMatch.distance} away.` : 'Reach out to sellers to negotiate terms.'}`,

    ({ count }) =>
      `Looking for value in Accra? ${count} local business${count !== 1 ? 'es' : ''} came up. Compare options and confirm availability before placing any order.`,

    ({ count, firstMatch }) =>
      `Your budget search returned ${count} match${count !== 1 ? 'es' : ''}. ${firstMatch ? `${firstMatch.name} in ${firstMatch.location} is a good lead — ${firstMatch.verified ? 'Custo-verified ✓.' : 'reach out to learn more.'}` : ''}`,

    ({ count }) =>
      `Cost efficiency first — here ${count === 1 ? 'is 1 listing' : `are ${count} listings`} that align with what you described. Sellers can usually accommodate bulk or repeat orders.`,

    ({ count, firstMatch }) =>
      `${count} result${count !== 1 ? 's' : ''} in your price range. ${firstMatch ? `${firstMatch.name} stands out with a ${firstMatch.rating}★ rating.` : ''} Always verify final pricing directly with the seller.`,

    ({ count }) =>
      `Budget search complete — ${count} local option${count !== 1 ? 's' : ''} surfaced. The right deal often comes from a quick conversation, so don't hesitate to reach out.`,
  ],

  // ── BULK / WHOLESALE ──────────────────────────────────────────────────────
  bulk: [
    ({ count, firstMatch }) =>
      `${count} supplier${count !== 1 ? 's' : ''} in Accra match your wholesale or bulk enquiry. ${firstMatch ? `${firstMatch.name} handles bulk orders — contact them with your quantity and lead-time requirements.` : ''}`,

    ({ count }) =>
      `Sourcing at scale? ${count} local business${count !== 1 ? 'es' : ''} can help. Share your quantity, specs, and delivery window when you reach out for the most accurate quote.`,

    ({ count, firstMatch }) =>
      `Bulk match — ${count} listing${count !== 1 ? 's' : ''} found. ${firstMatch ? `Start with ${firstMatch.name} in ${firstMatch.location}; they deal in wholesale volume.` : 'Each seller below can discuss minimum order quantities.'}`,

    ({ count }) =>
      `${count} Accra-based supplier${count !== 1 ? 's' : ''} surfaced for your order. For large volumes, ask about tiered pricing and lead times before confirming.`,

    ({ count, firstMatch }) =>
      `Wholesale search returned ${count} result${count !== 1 ? 's' : ''}. ${firstMatch ? `${firstMatch.name} (${firstMatch.rating}\u2605) is your highest-rated option \u2014 ${firstMatch.verified ? 'Custo-verified \u2713.' : 'reach out to verify credentials.'}` : ''}`,

    ({ count }) =>
      `${count} local source${count !== 1 ? 's' : ''} for your bulk requirement. Mention your quantity upfront to unlock the best pricing and avoid back-and-forth delays.`,

    ({ count, firstMatch }) =>
      `Found ${count} business${count !== 1 ? 'es' : ''} that handle wholesale. ${firstMatch ? `${firstMatch.name} covers ${firstMatch.category.toLowerCase()} — a solid starting point for procurement.` : 'Compare options before placing a large order.'}`,

    ({ count }) =>
      `Your wholesale search is back — ${count} potential supplier${count !== 1 ? 's' : ''} in Accra. Request samples or quotes to find the right fit before committing to volume.`,

    ({ count, firstMatch }) =>
      `${count} bulk-ready listing${count !== 1 ? 's' : ''} found. ${firstMatch ? `${firstMatch.name} at ${firstMatch.distance} is nearby — worth a call to discuss MOQ and delivery.` : ''}`,

    ({ count }) =>
      `Large order incoming? ${count} supplier${count !== 1 ? 's' : ''} in the sample catalogue match. Confirm stock levels, lead times, and packaging options directly with each seller.`,
  ],

  // ── ECO / SUSTAINABLE ─────────────────────────────────────────────────────
  eco: [
    ({ count, firstMatch }) =>
      `${count} eco-conscious option${count !== 1 ? 's' : ''} found in Accra. ${firstMatch ? `${firstMatch.name} specialises in sustainable products — a great place to start.` : 'Each listing below prioritises lower environmental impact.'}`,

    ({ count }) =>
      `Sustainable choices — ${count} local business${count !== 1 ? 'es' : ''} offer greener alternatives. Confirm certifications and materials directly with the seller.`,

    ({ count, firstMatch }) =>
      `${count} green-focused result${count !== 1 ? 's' : ''} surfaced. ${firstMatch ? `${firstMatch.name} (${firstMatch.rating}★, ${firstMatch.distance}) looks like a strong eco option.` : 'Ask sellers about their sustainability practices and material sourcing.'}`,

    ({ count }) =>
      `Your eco search returned ${count} match${count !== 1 ? 'es' : ''}. Each seller below leans toward sustainable, compostable, or low-impact products.`,

    ({ count, firstMatch }) =>
      `${count} environmentally aware business${count !== 1 ? 'es' : ''} in Accra. ${firstMatch ? `${firstMatch.name} is ${firstMatch.verified ? 'Custo-verified ✓' : 'unverified — verify independently'} and covers ${firstMatch.category.toLowerCase()}.` : ''}`,

    ({ count }) =>
      `Making sustainable choices — ${count} local option${count !== 1 ? 's' : ''} to explore. Ask about compostable materials, recycled inputs, and packaging specifics before you buy.`,

    ({ count, firstMatch }) =>
      `Eco match: ${count} listing${count !== 1 ? 's' : ''} in and around Accra. ${firstMatch ? `Leading result: ${firstMatch.name} in ${firstMatch.location}.` : 'Reach out to verify eco credentials with each supplier.'}`,

    ({ count }) =>
      `${count} sustainable supplier${count !== 1 ? 's' : ''} in the sample catalogue. For large orders, ask about bulk eco packaging to reduce per-unit footprint.`,

    ({ count, firstMatch }) =>
      `Your green search is complete — ${count} result${count !== 1 ? 's' : ''}. ${firstMatch ? `${firstMatch.name} carries ${firstMatch.category.toLowerCase()} and has a ${firstMatch.rating}★ rating.` : ''}`,

    ({ count }) =>
      `${count} eco-aligned business${count !== 1 ? 'es' : ''} found. Always ask for product spec sheets or certifications when sustainability is a hard requirement.`,
  ],

  // ── CUSTOM / BRANDED ──────────────────────────────────────────────────────
  custom: [
    ({ count, firstMatch }) =>
      `${count} business${count !== 1 ? 'es' : ''} offer custom or branded options in Accra. ${firstMatch ? `${firstMatch.name} handles made-to-order work — share your artwork and quantity when you reach out.` : ''}`,

    ({ count }) =>
      `Custom order search — ${count} local maker${count !== 1 ? 's' : ''} surfaced. Lead times vary, so the sooner you reach out the better.`,

    ({ count, firstMatch }) =>
      `${count} result${count !== 1 ? 's' : ''} for your custom or print request. ${firstMatch ? `${firstMatch.name} (${firstMatch.rating}★) is a strong pick — ask about minimum print runs and turnaround time.` : 'Contact each seller with your design brief and expected quantity.'}`,

    ({ count }) =>
      `Personalisation found — ${count} Accra-based ${count === 1 ? 'studio' : 'studios'} can bring your design to life. Prepare your artwork files and quantity before reaching out.`,

    ({ count, firstMatch }) =>
      `${count} custom-capable listing${count !== 1 ? 's' : ''} in the results. ${firstMatch ? `${firstMatch.name} is located in ${firstMatch.location} — ${firstMatch.verified ? 'Custo-verified ✓.' : 'verify before placing a large order.'}` : ''}`,

    ({ count }) =>
      `Bespoke search complete — ${count} local option${count !== 1 ? 's' : ''}. Ask each seller about sampling, proofing, and bulk discounts for large print runs.`,

    ({ count, firstMatch }) =>
      `${count} maker${count !== 1 ? 's' : ''} in Accra match your custom brief. ${firstMatch ? `${firstMatch.name} handles ${firstMatch.category.toLowerCase()} — a great starting point.` : ''}`,

    ({ count }) =>
      `Your custom order search returned ${count} result${count !== 1 ? 's' : ''}. Share your brand guidelines or colour codes so sellers can give you an accurate quote.`,

    ({ count, firstMatch }) =>
      `${count} print-and-brand option${count !== 1 ? 's' : ''} found nearby. ${firstMatch ? `${firstMatch.name} at ${firstMatch.distance} is closest \u2014 view their profile to see past work.` : 'Review each seller\u2019s portfolio before committing.'}`,

    ({ count }) =>
      `Customisation match — ${count} local business${count !== 1 ? 'es' : ''}. Lead times and minimum orders differ between sellers, so compare a few before you decide.`,
  ],

  // ── OUTDOOR / FOOTWEAR ────────────────────────────────────────────────────
  outdoor: [
    ({ count, firstMatch }) =>
      `${count} outdoor-focused listing${count !== 1 ? 's' : ''} in Accra. ${firstMatch ? `${firstMatch.name} carries trail and outdoor gear — contact them to check sizes and current stock.` : ''}`,

    ({ count }) =>
      `Getting outdoors? ${count} local shop${count !== 1 ? 's' : ''} stock outdoor and footwear products. Call ahead to confirm your size is available before making the trip.`,

    ({ count, firstMatch }) =>
      `${count} result${count !== 1 ? 's' : ''} for your outdoor search. ${firstMatch ? `Top match: ${firstMatch.name} (${firstMatch.rating}★, ${firstMatch.distance}) — ${firstMatch.verified ? 'Custo-verified ✓.' : 'verify stock availability before visiting.'}` : ''}`,

    ({ count }) =>
      `Trail-ready options — ${count} Accra shop${count !== 1 ? 's' : ''} surfaced. Stock and sizing can change fast, so reaching out first saves the journey.`,

    ({ count, firstMatch }) =>
      `${count} outdoor retailer${count !== 1 ? 's' : ''} found near Accra. ${firstMatch ? `${firstMatch.name} in ${firstMatch.location} is a good lead for footwear and outdoor kit.` : ''}`,

    ({ count }) =>
      `Outdoor search returned ${count} match${count !== 1 ? 'es' : ''}. Each seller below can advise on fit, materials, and what's currently in stock.`,

    ({ count, firstMatch }) =>
      `${count} listing${count !== 1 ? 's' : ''} for outdoor and trail gear. ${firstMatch ? `${firstMatch.name} covers ${firstMatch.category.toLowerCase()} — open their profile for the full catalogue.` : 'Confirm size availability by contacting sellers directly.'}`,

    ({ count }) =>
      `${count} local source${count !== 1 ? 's' : ''} for outdoor gear in Accra. For waterproof or technical footwear, ask about brand, sole type, and waterproofing standard.`,

    ({ count, firstMatch }) =>
      `Your outdoor search is back — ${count} result${count !== 1 ? 's' : ''}. ${firstMatch ? `${firstMatch.name} at ${firstMatch.distance} is closest — ${firstMatch.rating}★ rated.` : ''}`,

    ({ count }) =>
      `${count} Accra-based outdoor shop${count !== 1 ? 's' : ''} in the results. Availability moves quickly, so a quick message before visiting is always a good idea.`,
  ],

  // ── TEXTILE / BAGS ────────────────────────────────────────────────────────
  textile: [
    ({ count, firstMatch }) =>
      `${count} textile or fabric business${count !== 1 ? 'es' : ''} found in Accra. ${firstMatch ? `${firstMatch.name} handles ${firstMatch.category.toLowerCase()} — reach out to discuss materials and turnaround.` : ''}`,

    ({ count }) =>
      `Fabric forward — ${count} local ${count === 1 ? 'studio' : 'studios'} in the results. For custom orders, sharing fabric specs and quantities upfront speeds up quoting.`,

    ({ count, firstMatch }) =>
      `${count} textile result${count !== 1 ? 's' : ''} surfaced. ${firstMatch ? `${firstMatch.name} (${firstMatch.rating}★, ${firstMatch.location}) is your top match — ${firstMatch.verified ? 'Custo-verified ✓.' : 'verify before a bulk order.'}` : ''}`,

    ({ count }) =>
      `Your fabric and textile search returned ${count} option${count !== 1 ? 's' : ''}. Ask about weave, weight, and wash properties to make sure you get the right product.`,

    ({ count, firstMatch }) =>
      `${count} Accra-based textile supplier${count !== 1 ? 's' : ''} found. ${firstMatch ? `Start with ${firstMatch.name} in ${firstMatch.location} — they're ${firstMatch.distance} away.` : ''}`,

    ({ count }) =>
      `Textile match — ${count} local business${count !== 1 ? 'es' : ''}. If you need a sample before committing to a full order, most sellers can accommodate that.`,

    ({ count, firstMatch }) =>
      `${count} listing${count !== 1 ? 's' : ''} in textiles and bags. ${firstMatch ? `${firstMatch.name} handles ${firstMatch.category.toLowerCase()} — open their profile to see offerings.` : 'Compare lead times and minimums across sellers.'}`,

    ({ count }) =>
      `${count} fabric and textile option${count !== 1 ? 's' : ''} in Accra. For branded or printed bags, also check custom-print sellers — they often overlap.`,

    ({ count, firstMatch }) =>
      `Textile search complete — ${count} result${count !== 1 ? 's' : ''}. ${firstMatch ? `${firstMatch.name} at ${firstMatch.distance} is the nearest match.` : 'Contact sellers with your material requirements for a fast quote.'}`,

    ({ count }) =>
      `${count} textile or bag supplier${count !== 1 ? 's' : ''} surfaced. Minimum order quantities and lead times vary — always confirm before placing a large order.`,
  ],

  // ── GENERAL ───────────────────────────────────────────────────────────────
  general: [
    ({ count, firstMatch }) =>
      `${count} local ${count === 1 ? 'business' : 'businesses'} matched your search in Accra. ${firstMatch ? `${firstMatch.name} looks like the closest fit — view their profile to learn more.` : ''}`,

    ({ count }) =>
      `Here ${count === 1 ? 'is' : 'are'} ${count} local option${count !== 1 ? 's' : ''} in Accra. Contact the seller directly to confirm availability, pricing, and location.`,

    ({ count, firstMatch }) =>
      `${count} result${count !== 1 ? 's' : ''} from the local catalogue. ${firstMatch ? `${firstMatch.name} (${firstMatch.rating}★) is your top match — ${firstMatch.distance} from the centre.` : 'Reach out to any seller below to get started.'}`,

    ({ count }) =>
      `Local discovery complete — ${count} Accra-based business${count !== 1 ? 'es' : ''} found. Each seller can discuss stock, pricing, and delivery.`,

    ({ count, firstMatch }) =>
      `${count} nearby listing${count !== 1 ? 's' : ''} surfaced for your request. ${firstMatch ? `${firstMatch.name} in ${firstMatch.location} ${firstMatch.verified ? 'is Custo-verified ✓ — ' : '— '}worth a look.` : ''}`,

    ({ count }) =>
      `Custo found ${count} local option${count !== 1 ? 's' : ''} that may fit what you need. Confirm all details directly with the seller — this is a sample catalogue.`,

    ({ count, firstMatch }) =>
      `${count} match${count !== 1 ? 'es' : ''} in the sample listings. ${firstMatch ? `Leading result: ${firstMatch.name} · ${firstMatch.category} · ${firstMatch.distance}.` : 'Try a more specific query to narrow the results.'}`,

    ({ count }) =>
      `${count} Accra business${count !== 1 ? 'es' : ''} came up. Click any card below to open the full seller profile and find their contact details.`,

    ({ count, firstMatch }) =>
      `Found ${count} local option${count !== 1 ? 's' : ''} near Accra. ${firstMatch ? `${firstMatch.name} has a ${firstMatch.rating}★ rating — ${firstMatch.verified ? 'and is Custo-verified.' : 'reach out to verify credentials.'}` : ''}`,

    ({ count, profile }) =>
      profile.isReturning
        ? `Welcome back — ${count} result${count !== 1 ? 's' : ''} for your latest search. Contact sellers directly to confirm live availability.`
        : `${count} local option${count !== 1 ? 's' : ''} surfaced. Each seller card below links to their full profile and contact details.`,
  ],
};

// No-results templates (10 variants, chosen by search count parity + tone)
const NO_RESULTS_TEMPLATES = [
  ({ query }) =>
    `No close matches for "${query}" in this demo catalogue. Try a broader product name or drop a specific detail — the sample set is small.`,

  () =>
    `Nothing surfaced this time. The demo catalogue has only a handful of listings, so try a different keyword or a broader category.`,

  ({ query, profile }) =>
    profile.tone === 'professional'
      ? `No matching vendors found for "${query}". Broaden the product category or remove constraints to surface results.`
      : `Couldn't find a match for "${query}". Give me a broader term — like "packaging", "footwear", or "textiles" — and I'll try again.`,

  () =>
    `The sample catalogue didn't have a match this time. Rephrase your search with a product type or service category and I'll look again.`,

  ({ query }) =>
    `"${query}" didn't return any results from the demo listings. Try searching by material, category, or use case instead.`,

  ({ profile }) =>
    profile.isReturning
      ? `Still no matches — the demo catalogue is limited. Try one of the starter prompts in the sidebar to see example results.`
      : `No results this time. The demo catalogue is a small sample, so niche queries often come up empty. Try something broader.`,

  () =>
    `I looked through the sample listings and nothing matched closely. A simpler or shorter query often works better in this prototype.`,

  ({ query }) =>
    `No Accra businesses matched "${query}" in the sample data. Product names, materials, or service types tend to work best.`,

  () =>
    `No match found — but that doesn't mean the product doesn't exist in Accra! This prototype runs on a tiny demo catalogue only.`,

  ({ profile }) =>
    profile.searchCount > 3
      ? `Still no luck? The demo only has 4 sample businesses. Try "coffee cups", "hiking boots", "tote bags", or "wholesale supplier".`
      : `Couldn't find a match. Try something like "eco packaging", "outdoor footwear", or "custom bags" — those have demo listings.`,
];

// ─── 4. SELECTION LOGIC ─────────────────────────────────────────────────────

/**
 * Picks one of the 10 templates for a given intent using a deterministic
 * but varied selection strategy based on the user profile.
 *
 * Strategy (priority order):
 *   A. Rotate through 10 templates across the session (avoid repeating)
 *   B. Prefer templates that match the user's verbosity level
 *   C. Add intent-streak bonus: if user has searched the same intent
 *      multiple times, skip to the next template to avoid repetition
 */
function selectTemplate(intent, hasResults, context) {
  if (!hasResults) {
    return selectNoResultsTemplate(context);
  }

  const pool = TEMPLATES[intent] || TEMPLATES.general;
  const count = pool.length; // always 10

  const { searchCount, intents, verbosity } = userProfile;

  // Count how many times this exact intent has appeared this session
  const intentOccurrences = intents.filter((i) => i === intent).length;

  // Base index cycles through templates as the session progresses
  const baseIndex = (searchCount - 1) % count;

  // Intent streak offset: each repeated same-intent search moves one step
  const streakOffset = (intentOccurrences - 1) % count;

  // Verbosity nudge: detailed queries lean toward later templates (richer copy)
  const verbosityNudge =
    verbosity === 'brief' ? 0 : verbosity === 'detailed' ? 2 : 1;

  const finalIndex = (baseIndex + streakOffset + verbosityNudge) % count;

  return pool[finalIndex](context);
}

function selectNoResultsTemplate(context) {
  const { searchCount } = userProfile;
  const index = (searchCount - 1) % NO_RESULTS_TEMPLATES.length;
  return NO_RESULTS_TEMPLATES[index](context);
}

// ─── 5. PUBLIC API ───────────────────────────────────────────────────────────

/**
 * Main entry point called by app.js.
 *
 * @param {string} query        - Raw user query
 * @param {Array}  matches      - Ranked listing objects from rankListings()
 * @returns {string}            - Intro sentence to display above result cards
 */
window.CUSTO_RESPONSES = {
  /**
   * Call once per search, before rendering, so the profile is up to date
   * and the returned intro text reflects the current query context.
   */
  getIntro(query, matches) {
    updateProfile(query);

    const intent = detectIntent(query);
    const hasResults = matches.length > 0;
    const context = {
      count: matches.length,
      query,
      intent,
      profile: userProfile,
      firstMatch: matches[0] || null,
    };

    return selectTemplate(intent, hasResults, context);
  },

  /** Expose profile for debugging (accessible via console) */
  get profile() {
    return { ...userProfile };
  },
};
