(() => {
  const boot = () => {
    document.querySelectorAll('form.variations_form').forEach(form => {
      const select = form.querySelector('select[name="attribute_pa_oferta"], select[name="attribute_pa_validade"]');
      if (!select || form.querySelector('.sv1-offer-picker')) return;
      let variations = [];
      try { variations = JSON.parse(form.getAttribute('data-product_variations') || '[]'); } catch (_) {}
      if (!Array.isArray(variations) || !variations.length) return;
      const attributeKey = `attribute_${select.name.replace(/^attribute_/, '')}`;
      const row = select.closest('tr, .value, .variations');
      const label = row?.querySelector('label');
      if (label) label.textContent = 'Escolha sua oferta';
      const picker = document.createElement('div');
      picker.className = 'sv1-offer-picker'; picker.setAttribute('role', 'radiogroup'); picker.setAttribute('aria-label', 'Escolha sua oferta');
      [...select.options].filter(option => option.value).forEach(option => {
        const variation = variations.find(item => item.attributes && item.attributes[attributeKey] === option.value);
        if (!variation) return;
        const button = document.createElement('button'); button.type = 'button'; button.className = 'sv1-offer-option'; button.dataset.value = option.value; button.setAttribute('role', 'radio'); button.setAttribute('aria-checked', 'false');
        const image = document.createElement('img'); image.className = 'sv1-offer-image'; image.alt = ''; image.loading = 'lazy'; image.src = variation.image?.src || ''; if (!image.src) image.hidden = true;
        const details = document.createElement('span'); details.className = 'sv1-offer-details'; const title = document.createElement('strong'); title.className = 'sv1-offer-title'; title.textContent = option.textContent.trim(); const hint = document.createElement('small'); hint.textContent = 'Oferta disponível'; details.append(title, hint);
        const price = document.createElement('span'); price.className = 'sv1-offer-price'; if (variation.price_html) price.innerHTML = variation.price_html; else if (variation.display_price !== undefined) price.textContent = String(variation.display_price);
        button.append(image, details, price);
        button.addEventListener('click', () => { if (select.value === option.value) return; select.value = option.value; select.dispatchEvent(new Event('change', {bubbles: true})); sync(); });
        picker.appendChild(button);
      });
      if (!picker.children.length) return;
      select.classList.add('sv1-native-variation-select'); select.parentElement?.appendChild(picker);
      const sync = () => picker.querySelectorAll('.sv1-offer-option').forEach(item => { const active = item.dataset.value === select.value; item.classList.toggle('is-selected', active); item.setAttribute('aria-checked', active ? 'true' : 'false'); });
      select.addEventListener('change', sync); sync();
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
