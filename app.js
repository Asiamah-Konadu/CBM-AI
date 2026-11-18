const listings = window.CUSTO_DATA;
const art = window.CUSTO_ART;

const form = document.querySelector('#chat-form');
const input = document.querySelector('#query');
const conversation = document.querySelector('#conversation');
const welcome = document.querySelector('#welcome-block');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function assistantIcon() {
  return '<span class="assistant-mini"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M25 10.2a11 11 0 1 0 .1 11.7M25 10.2v7.2m0-7.2h-7.2"/></svg></span>';
}

function rankListings(query) {
  const ignored = new Set(['the', 'for', 'and', 'with', 'from', 'that', 'this', 'have', 'need', 'want', 'find', 'available', 'local', 'accra', 'ghana', 'my', 'in', 'of', 'a', 'an', 'to', 'under', 'near']);
  const terms = query.toLowerCase().split(/[^a-z0-9]+/).filter((word) => word.length > 2 && !ignored.has(word));
  return listings.map((item) => {
    const haystack = `${item.name} ${item.category} ${item.description} ${item.keywords}`.toLowerCase();
    const score = terms.reduce((total, word) => total + (haystack.includes(word) ? 1 : 0), 0);
    return { item, score };
  }).filter(({ score }) => score > 0).sort((a, b) => b.score - a.score).map(({ item }) => item);
}

function appendUserMessage(text) {
  const row = document.createElement('div');
  row.className = 'user-message-row';
  const bubble = document.createElement('div');
  bubble.className = 'user-message';
  bubble.textContent = text;
  row.append(bubble);
  conversation.append(row);
}

function cardsMarkup(items) {
  if (!items.length) return '<div class="empty-state"><strong>I couldn’t find a close match in this demo.</strong>Try a product or service name, or add another detail like a neighborhood. The sample catalog is small, so some requests won’t have a match yet.</div>';
  return `<div class="match-list">${items.map((item) => `
    <article class="match-card">
      <a class="match-art ${item.kind}" href="./profile.html?id=${item.id}" aria-label="View ${item.name} profile">${art[item.kind]}</a>
      <div class="match-info"><div class="match-category">${item.category}</div><h3><a href="./profile.html?id=${item.id}">${item.name}</a>${item.verified ? '<span class="verified-pill"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 8 3 3 7-7"/></svg>Custo verified</span>' : ''}</h3><p>${item.description}</p><div class="match-meta"><span>${item.location} · ${item.distance}</span><span class="match-rating">★ ${item.rating}</span></div></div>
      <a class="match-contact" href="./profile.html?id=${item.id}" aria-label="View ${item.name} profile">View profile ↗</a>
    </article>`).join('')}</div>`;
}

function appendAssistantResponse(query) {
  const matches = rankListings(query);
  const row = document.createElement('div');
  row.className = 'assistant-message-row';
  const content = document.createElement('div');
  content.className = 'assistant-content';
  const intro = document.createElement('p');
  intro.textContent = matches.length
    ? `I found ${matches.length === 1 ? 'a local option' : `${matches.length} local options`} that may fit what you’re looking for. You can contact a seller directly to confirm details.`
    : 'I’m not seeing a close match in the small sample catalog yet. A broader product name or a different request may turn up a result.';
  content.append(intro);
  if (matches.length) {
    const head = document.createElement('div');
    head.className = 'match-head';
    head.innerHTML = `<b>Local matches</b><span>${matches.length} sample listing${matches.length === 1 ? '' : 's'}</span>`;
    content.append(head);
  }
  const cards = document.createElement('div');
  cards.innerHTML = cardsMarkup(matches);
  content.append(cards);
  if (matches.length) {
    const note = document.createElement('p');
    note.className = 'results-disclaimer';
    note.textContent = 'Sample listings for this demo. Availability, ratings, distance and verification are not live.';
    content.append(note);
  }
  row.innerHTML = assistantIcon();
  row.append(content);
  conversation.append(row);
}

function submitMessage(value) {
  const message = value.trim();
  if (!message) return;
  welcome.hidden = true;
  appendUserMessage(message);
  appendAssistantResponse(message);
  conversation.scrollTo({ top: conversation.scrollHeight, behavior: reducedMotion.matches ? 'instant' : 'smooth' });
  input.value = '';
  input.style.height = 'auto';
  input.focus();
}

form.addEventListener('submit', (event) => {
  event.preventDefault();
  submitMessage(input.value);
});
input.addEventListener('input', () => {
  input.style.height = 'auto';
  input.style.height = `${Math.min(input.scrollHeight, 130)}px`;
});
input.addEventListener('keydown', (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    form.requestSubmit();
  }
});
document.querySelectorAll('[data-query]').forEach((button) => button.addEventListener('click', () => {
  submitMessage(button.dataset.query);
}));
document.querySelectorAll('.history-item').forEach((button) => button.addEventListener('click', () => {
  submitMessage(button.dataset.query);
  document.querySelectorAll('.history-item').forEach((item) => item.classList.remove('active'));
  button.classList.add('active');
}));
document.querySelector('#new-chat').addEventListener('click', () => {
  conversation.querySelectorAll('.user-message-row,.assistant-message-row').forEach((node) => node.remove());
  welcome.hidden = false;
  input.value = '';
  input.focus();
  document.querySelectorAll('.history-item').forEach((item) => item.classList.remove('active'));
});

const sidebar = document.querySelector('#sidebar');
const scrim = document.querySelector('#mobile-scrim');
function closeSidebar() { sidebar.classList.remove('open'); scrim.classList.remove('visible'); }
document.querySelector('#mobile-menu').addEventListener('click', () => { sidebar.classList.add('open'); scrim.classList.add('visible'); });
scrim.addEventListener('click', closeSidebar);
sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
