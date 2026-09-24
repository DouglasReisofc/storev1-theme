(() => {
  const moneyFallback = variation => variation.display_price === undefined ? '' : Number(variation.display_price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

  const boot = () => {
    document.querySelectorAll('form.variations_form').forEach((form, formIndex) => {
      const select = form.querySelector('select[name="attribute_pa_oferta"], select[name="attribute_pa_validade"]');
      if (!select || form.dataset.sv1OfferModal === 'ready') return;

      let variations = [];
      try { variations = JSON.parse(form.getAttribute('data-product_variations') || '[]'); } catch (_) {}
      if ((!Array.isArray(variations) || !variations.length) && window.jQuery) variations = window.jQuery(form).data('product_variations') || [];
      if (!Array.isArray(variations) || !variations.length) return;

      const attributeKey = `attribute_${select.name.replace(/^attribute_/, '')}`;
      const optionData = [...select.options].filter(option => option.value).map(option => ({
        option,
        variation: variations.find(item => item.attributes && item.attributes[attributeKey] === option.value)
      })).filter(item => item.variation);
      if (!optionData.length) return;

      const sourceRow = select.closest('tr') || select.closest('.value');
      const dialogId = `sv1-offer-dialog-${formIndex + 1}`;
      form.dataset.sv1OfferModal = 'ready';
      form.classList.add('sv1-has-offer-modal');
      select.classList.add('sv1-native-variation-select');
      if (sourceRow) sourceRow.classList.add('sv1-variation-source');
      // Give the shopper a usable state immediately. WooCommerce still owns
      // the select and receives the normal change event, so stock/price/add
      // to cart validation remain native.
      if (!select.value) {
        const firstAvailable = optionData.find(item => item.variation.is_in_stock !== false);
        if (firstAvailable) {
          select.value = firstAvailable.option.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'sv1-offer-trigger';
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.setAttribute('aria-controls', dialogId);
      trigger.innerHTML = '<span class="sv1-offer-trigger__image" aria-hidden="true"></span><span class="sv1-offer-trigger__copy"><small>Escolha sua oferta</small><strong>Ver opções disponíveis</strong></span><span class="sv1-offer-trigger__price"></span><span class="sv1-offer-trigger__chevron" aria-hidden="true">⌄</span>';

      const dialog = document.createElement('dialog');
      dialog.id = dialogId;
      dialog.className = 'sv1-offer-dialog';
      dialog.setAttribute('aria-labelledby', `${dialogId}-title`);
      dialog.innerHTML = `<div class="sv1-offer-dialog__panel"><header class="sv1-offer-dialog__header"><div><small>Opções disponíveis</small><h2 id="${dialogId}-title">Escolha sua oferta</h2></div><button type="button" class="sv1-offer-dialog__close" aria-label="Fechar seletor">×</button></header><div class="sv1-offer-dialog__list" role="radiogroup" aria-label="Escolha sua oferta"></div></div>`;

      const list = dialog.querySelector('.sv1-offer-dialog__list');
      const closeDialog = () => {
        trigger.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('sv1-offer-modal-open');
        if (typeof dialog.close === 'function' && dialog.open) dialog.close();
        else dialog.removeAttribute('open');
      };
      const openDialog = () => {
        trigger.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('sv1-offer-modal-open');
        if (typeof dialog.showModal === 'function') dialog.showModal();
        else dialog.setAttribute('open', '');
        dialog.querySelector('.sv1-offer-option.is-selected, .sv1-offer-option:not(:disabled)')?.focus();
      };
      const sync = () => {
        const selected = optionData.find(item => item.option.value === select.value);
        list.querySelectorAll('.sv1-offer-option').forEach(item => {
          const active = item.dataset.value === select.value;
          item.classList.toggle('is-selected', active);
          item.setAttribute('aria-checked', active ? 'true' : 'false');
        });
        const imageSlot = trigger.querySelector('.sv1-offer-trigger__image');
        const title = trigger.querySelector('.sv1-offer-trigger__copy strong');
        const price = trigger.querySelector('.sv1-offer-trigger__price');
        imageSlot.replaceChildren();
        price.replaceChildren();
        if (!selected) { title.textContent = 'Ver opções disponíveis'; return; }
        title.textContent = selected.option.textContent.trim();
        const imageUrl = selected.variation.image?.src || '';
        if (imageUrl) {
          const image = document.createElement('img');
          image.src = imageUrl; image.alt = '';
          imageSlot.appendChild(image);
        }
        if (selected.variation.price_html) price.innerHTML = selected.variation.price_html;
        else price.textContent = moneyFallback(selected.variation);
      };

      optionData.forEach(({ option, variation }) => {
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'sv1-offer-option'; button.dataset.value = option.value;
        button.setAttribute('role', 'radio'); button.setAttribute('aria-checked', 'false');
        const image = document.createElement('img');
        image.className = 'sv1-offer-image'; image.alt = ''; image.loading = 'lazy'; image.src = variation.image?.src || ''; if (!image.src) image.hidden = true;
        const details = document.createElement('span'); details.className = 'sv1-offer-details';
        const title = document.createElement('strong'); title.className = 'sv1-offer-title'; title.textContent = option.textContent.trim();
        const hint = document.createElement('small'); hint.textContent = variation.is_in_stock === false ? 'Indisponível' : 'Disponível para compra'; details.append(title, hint);
        const price = document.createElement('span'); price.className = 'sv1-offer-price';
        if (variation.price_html) price.innerHTML = variation.price_html; else price.textContent = moneyFallback(variation);
        const check = document.createElement('span'); check.className = 'sv1-offer-option__check'; check.setAttribute('aria-hidden', 'true'); check.textContent = '✓';
        button.append(image, details, price, check);
        if (variation.is_in_stock === false) button.disabled = true;
        button.addEventListener('click', () => {
          select.value = option.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          sync(); closeDialog();
        });
        list.appendChild(button);
      });

      trigger.addEventListener('click', openDialog);
      dialog.querySelector('.sv1-offer-dialog__close').addEventListener('click', closeDialog);
      dialog.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });
      dialog.addEventListener('cancel', event => { event.preventDefault(); closeDialog(); });
      dialog.addEventListener('close', () => { trigger.setAttribute('aria-expanded', 'false'); document.documentElement.classList.remove('sv1-offer-modal-open'); });
      select.addEventListener('change', sync);

      const mount = form.querySelector('.variations') || form.firstElementChild;
      if (mount) mount.insertAdjacentElement('beforebegin', trigger); else form.prepend(trigger);
      document.body.appendChild(dialog);
      sync();
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
