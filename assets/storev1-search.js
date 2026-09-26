(() => {
  // Header search: show matching products while the customer types, without
  // navigating away from the current page. Enter still performs the normal
  // WooCommerce search for the complete result page.
  const headerForms = [...document.querySelectorAll('.sv1-search-form')];
  if (headerForms.length) {
    headerForms.forEach(form => {
      const input = form.querySelector('input[type="search"]');
      if (!input || form.dataset.sv1LiveSearch === '1') return;
      form.dataset.sv1LiveSearch = '1';
      form.classList.add('sv1-live-search-form');
      const panel = document.createElement('div');
      panel.className = 'sv1-live-search-results';
      panel.hidden = true;
      panel.setAttribute('role', 'listbox');
      form.appendChild(panel);
      let timer = 0, controller, sequence = 0;
      const close = () => { panel.hidden = true; panel.innerHTML = ''; };
      const search = async () => {
        const query = input.value.trim();
        window.clearTimeout(timer);
        controller?.abort();
        if (query.length < 2) { close(); return; }
        timer = window.setTimeout(async () => {
          const request = ++sequence;
          controller = new AbortController();
          panel.hidden = false;
          panel.setAttribute('aria-busy', 'true');
          panel.innerHTML = '<p class="sv1-live-search-loading">Buscando produtos…</p>';
          try {
            const url = new URL(window.location.origin + '/wp-admin/admin-ajax.php');
            url.searchParams.set('action', 'storev1_catalog_search');
            url.searchParams.set('q', query);
            const response = await fetch(url, {signal: controller.signal, credentials: 'same-origin'});
            const payload = await response.json();
            if (request !== sequence) return;
            panel.innerHTML = payload.success && payload.data.html
              ? payload.data.html
              : '<p class="sv1-live-search-empty">Nenhum produto encontrado.</p>';
          } catch (error) {
            if (error.name !== 'AbortError' && request === sequence) panel.innerHTML = '<p class="sv1-live-search-empty">Não foi possível buscar agora.</p>';
          } finally {
            if (request === sequence) panel.removeAttribute('aria-busy');
          }
        }, 180);
      };
      input.addEventListener('input', search);
      input.addEventListener('focus', () => { if (input.value.trim().length >= 2) search(); });
      document.addEventListener('click', event => { if (!form.contains(event.target)) close(); });
      input.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    });
  }
  const resultCount = document.querySelector('.woocommerce-result-count');
  if (!resultCount) return;
  const toolbar = resultCount.parentElement;
  const mobileViewport = matchMedia('(max-width:767px)');
  const toggle = document.createElement('button');
  toggle.type='button'; toggle.className='sv1-search-toggle'; toggle.setAttribute('aria-label','Buscar produtos'); toggle.setAttribute('aria-expanded','false'); toggle.setAttribute('aria-controls','sv1-inline-search');
  toggle.innerHTML='<svg class="sv1-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m16 16 5 5"></path></svg>';
  resultCount.remove();
  toolbar.prepend(toggle);
  const panel = document.createElement('section'); panel.id='sv1-inline-search'; panel.className='sv1-inline-search'; panel.hidden=true; panel.dataset.endpoint=window.location.origin+'/wp-admin/admin-ajax.php'; panel.setAttribute('aria-label','Busca de produtos');
  panel.innerHTML='<div class="sv1-inline-search-head"><label class="screen-reader-text" for="sv1-live-query">Pesquisar produtos</label><input id="sv1-live-query" type="search" placeholder="O que você procura?" autocomplete="off"><button type="button" data-search-close aria-label="Fechar busca">×</button></div><p data-search-status role="status" aria-live="polite"></p><div data-search-results class="woocommerce"></div>';
  const panelHost = document.querySelector('.sv1-discovery') || toolbar;
  panelHost.insertBefore(panel, panelHost.firstChild);
  const input = panel.querySelector('input');
  const results = panel.querySelector('[data-search-results]');
  const status = panel.querySelector('[data-search-status]');
  const banner = document.querySelector('[data-promo-carousel]');
  const catalog = [...document.querySelectorAll('#main-content ul.products, #main-content .woocommerce-pagination')].filter(el => !panel.contains(el));
  let controller, sequence = 0;
  const cache = new Map();
  function render(data) {
    results.innerHTML = data.html;
    status.textContent = data.total ? (data.total > data.shown ? `Mostrando ${data.shown} de ${data.total} produtos. Refine a pesquisa.` : `${data.total} produto${data.total === 1 ? '' : 's'} encontrado${data.total === 1 ? '' : 's'}.`) : 'Nenhum produto encontrado. Tente outro nome.';
    if (panel.getBoundingClientRect().bottom < 100) panel.scrollIntoView({block:'start',behavior:'instant'});
  }
  async function search() {
    const query = input.value.trim();
    if (banner) banner.hidden = !panel.hidden;
    controller?.abort();
    const request = ++sequence;
    if (cache.has(query)) { render(cache.get(query)); results.removeAttribute('aria-busy'); return; }
    controller = new AbortController();
    results.setAttribute('aria-busy','true');
    status.textContent = 'Buscando produtos…';
    try {
      const url = new URL(panel.dataset.endpoint, location.href);
      url.searchParams.set('action','storev1_catalog_search');
      url.searchParams.set('q',query);
      const response = await fetch(url,{signal:controller.signal,credentials:'same-origin'});
      if (!response.ok) throw new Error('search');
      const payload = await response.json();
      if (!payload.success) throw new Error('search');
      if (request !== sequence) return;
      cache.set(query,payload.data); render(payload.data);
    } catch (error) {
      if (error.name !== 'AbortError' && request === sequence) status.textContent = 'Não foi possível buscar agora. Digite novamente para tentar.';
    } finally { if (request === sequence) results.removeAttribute('aria-busy'); }
  }
  function setOpen(open) {
    if (open && !mobileViewport.matches) return;
    panel.hidden = !open;
    toggle.hidden = open;
    toggle.setAttribute('aria-expanded', String(open));
    catalog.forEach(el => el.classList.toggle('sv1-search-hidden',open));
    if (open) { if(banner) banner.hidden = true; search(); }
    else { controller?.abort(); sequence++; input.blur(); input.value = ''; if(banner) banner.hidden = false; if(mobileViewport.matches) toggle.focus({preventScroll:true}); }
  }
  toggle.addEventListener('click',()=>setOpen(panel.hidden));
  panel.querySelector('[data-search-close]').addEventListener('click',()=>setOpen(false));
  panel.addEventListener('keydown',event=>{if(event.key==='Escape') setOpen(false);});
  input.addEventListener('input', () => {
    // A shorter result set must not strand the input above the viewport.
    if (panel.getBoundingClientRect().top < 0) panel.scrollIntoView({block:'start',behavior:'instant'});
    search();
  });
  // Opening the canvas never summons the keyboard. Dismiss it only after an
  // intentional gesture over results, not on resize caused by the keyboard itself.
  const dismissKeyboard = () => { if (document.activeElement === input) input.blur(); };
  let touchStartY = null;
  document.addEventListener('touchstart', event => { touchStartY = !panel.hidden && !event.target.closest('.sv1-inline-search-head') ? event.touches[0]?.clientY ?? null : null; }, {passive:true});
  document.addEventListener('touchmove', event => {
    if (touchStartY !== null && Math.abs((event.touches[0]?.clientY ?? touchStartY) - touchStartY) > 10) {
      dismissKeyboard(); touchStartY = null;
    }
  }, {passive:true});
  document.addEventListener('touchend', () => { touchStartY = null; }, {passive:true});
  document.addEventListener('wheel', () => { if (!panel.hidden) dismissKeyboard(); }, {passive:true});
  input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); dismissKeyboard(); } });
  mobileViewport.addEventListener('change',event=>{if(!event.matches) setOpen(false);});
})();
