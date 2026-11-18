const params = new URLSearchParams(window.location.search);
const business = window.CUSTO_DATA.find((item) => item.id === params.get('id')) || window.CUSTO_DATA[0];
const art = window.CUSTO_ART;

document.title = `${business.name} — Custo.AI`;
document.querySelector('meta[name="description"]').content = `${business.name} business profile on Custo.AI. Sample profile.`;
document.querySelector('#breadcrumb-name').textContent = business.name;
document.querySelector('#profile-category').textContent = business.category;
document.querySelector('#business-name').textContent = business.name;
document.querySelector('#profile-subtitle').innerHTML = `<span class="profile-location-icon">⌖</span>${business.location}<span class="subtitle-dot">·</span><span class="profile-rating-inline">★ ${business.rating}</span><span class="rating-count">Sample rating</span>`;
document.querySelector('#profile-rating').innerHTML = `<b>★ ${business.rating}</b><span>Sample rating</span>`;
document.querySelector('#business-about').textContent = business.about;
document.querySelector('#business-address').textContent = business.address;
document.querySelector('#business-hours').textContent = business.hours;
document.querySelector('#business-category-fact').textContent = business.category;
document.querySelector('#map-caption').textContent = business.location;
document.querySelector('#map-neighborhood').textContent = business.location.split(',')[0];
document.querySelector('#location-address').textContent = business.address;
document.querySelector('#profile-avatar').innerHTML = art[business.kind];
document.querySelector('#cover-main').innerHTML = art[business.kind];
document.querySelector('#cover-side-one').innerHTML = art[business.offerings[0]?.kind || business.kind];
document.querySelector('#cover-side-two').innerHTML = art[business.offerings[1]?.kind || business.kind];
document.querySelector('#profile-cover').classList.add(`cover-${business.kind}`);
document.querySelector('#verification-status').innerHTML = business.verified
  ? '<span class="verified-pill large"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 8 3 3 7-7"/></svg>Custo verified · sample status</span>'
  : '<span class="unverified-pill">Not verified · sample status</span>';
document.querySelector('#trust-copy').textContent = business.verified
  ? 'This sample business is shown with a verified badge for demonstration. In production, the badge will mean Custo has reviewed the business evidence.'
  : 'This sample business has not been marked verified. In production, sellers must pass a Custo review before a badge appears.';
document.querySelector('#catalog-count').textContent = `${business.offerings.length} sample items`;
document.querySelector('#catalog-grid').innerHTML = business.offerings.map((item) => `
  <article class="catalog-item">
    <div class="catalog-art ${item.kind}">${art[item.kind]}</div>
    <div class="catalog-item-body"><h3>${item.name}</h3><p>${item.detail}</p><button type="button" data-demo-contact="${item.name}">Ask about this <span aria-hidden="true">→</span></button></div>
  </article>`).join('');

document.querySelectorAll('[data-demo-contact]').forEach((button) => button.addEventListener('click', () => {
  const subject = button.dataset.demoContact;
  const status = document.querySelector('#contact-status');
  status.textContent = `${subject} contact isn’t connected yet. This sample profile has no live seller contact details.`;
  status.classList.add('visible');
}));
