(() => {
  const resultCount = document.querySelector('.woocommerce-result-count');
  if (!resultCount) return;
  const toolbar = resultCount.parentElement;
  const toggle = document.createElement('button');
  toggle.type='button'; toggle.className='sv1-search-toggle'; toggle.setAttribute('aria-label','Buscar produtos'); toggle.setAttribute('aria-expanded','false'); toggle.setAttribute('aria-controls','sv1-inline-search');
  toggle.innerHTML='<svg class="sv1-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m16 16 5 5"></path></svg>';
  resultCount.remove();
  toolbar.prepend(toggle);
  const panel = document.createElement('section'); panel.id='sv1-inline-search'; panel.className='sv1-inline-search'; panel.hidden=true; panel.dataset.endpoint=window.location.origin+'/wp-admin/admin-ajax.php'; panel.setAttribute('aria-label','Busca de produtos');
  panel.innerHTML='<div class="sv1-inline-search-head"><label class="screen-reader-text" for="sv1-live-query">Pesquisar produtos</label><input id="sv1-live-query" type="search" placeholder="O que você procura?" autocomplete="off"><button type="button" data-search-close aria-label="Fechar busca">×</button></div><p data-search-status role="status" aria-live="polite"></p><div data-search-results class="woocommerce"></div>';
  const firstProducts = toolbar.querySelector('ul.products');
  toolbar.insertBefore(panel, firstProducts || null);
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
  }
  async function search() {
    const query = input.value.trim();
    if (banner) banner.hidden = !!query;
    controller?.abort();
    const request = ++sequence;
    if (cache.has(query)) { render(cache.get(query)); results.removeAttribute('aria-busy'); return; }
    controller = new AbortController();
    results.innerHTML = '';
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
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    catalog.forEach(el => el.classList.toggle('sv1-search-hidden',open));
    if (open) { if(banner) banner.hidden = true; input.focus({preventScroll:true}); search(); }
    else { controller?.abort(); sequence++; input.value = ''; if(banner) banner.hidden = false; toggle.focus({preventScroll:true}); }
  }
  toggle.addEventListener('click',()=>setOpen(panel.hidden));
  panel.querySelector('[data-search-close]').addEventListener('click',()=>setOpen(false));
  panel.addEventListener('keydown',event=>{if(event.key==='Escape') setOpen(false);});
  input.addEventListener('input',search);
  document.querySelectorAll('.loja1-search .sv1-search-form').forEach(form=>{
    const field=form.querySelector('input[type=search]');
    field.addEventListener('input',()=>{input.value=field.value;if(panel.hidden)setOpen(true);else search();field.focus({preventScroll:true});});
    form.addEventListener('submit',event=>{event.preventDefault();input.value=field.value;setOpen(true);});
  });
})();
