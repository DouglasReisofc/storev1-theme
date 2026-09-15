(() => {
  const drawer = document.getElementById('sv1-drawer');
  const triggers = document.querySelectorAll('[data-sv1-open]');
  let previousFocus;
  function close() { if (drawer?.open) drawer.close(); }
  if (drawer) {
    triggers.forEach(button => button.addEventListener('click', () => {
      previousFocus = button;
      drawer.showModal();
      document.documentElement.classList.add('sv1-menu-open');
      triggers.forEach(t => t.setAttribute('aria-expanded', 'true'));
    }));
    drawer.querySelector('[data-sv1-close]').addEventListener('click', close);
    drawer.addEventListener('click', event => { if (event.target === drawer) {
      const r = drawer.getBoundingClientRect();
      if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) close();
    }});
    drawer.addEventListener('close', () => {
      document.documentElement.classList.remove('sv1-menu-open');
      triggers.forEach(t => t.setAttribute('aria-expanded', 'false'));
      previousFocus?.focus();
    });
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
    matchMedia('(min-width:768px)').addEventListener('change', event => { if(event.matches) close(); });
  }
  document.querySelectorAll('.sv1-categories, [data-category-picker]').forEach(details => {
    document.addEventListener('click', event => { if (!details.contains(event.target)) details.open = false; });
    details.addEventListener('keydown', event => { if(event.key === 'Escape') { details.open = false; details.querySelector('summary').focus(); } });
  });
  document.querySelectorAll('[data-category-picker]').forEach(picker => {
    const search = picker.querySelector('[data-category-search]');
    const options = [...picker.querySelectorAll('[data-category-option]')];
    const status = picker.querySelector('[data-category-status]');
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    search.addEventListener('input', () => {
      const query = normalize(search.value);
      let count = 0;
      options.forEach(option => {
        option.hidden = !normalize(option.querySelector('a').firstChild.textContent).includes(query);
        if (!option.hidden) count++;
      });
      status.textContent = count ? (query ? `${count} categoria${count === 1 ? '' : 's'} encontrada${count === 1 ? '' : 's'}` : '') : 'Nenhuma categoria encontrada.';
    });
    search.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        options.find(option => !option.hidden)?.querySelector('a').focus();
      }
    });
    picker.addEventListener('toggle', () => { if (picker.open) search.focus({preventScroll:true}); });
  });
  document.querySelectorAll('[data-sv1-carousel]').forEach(carousel => {
    const slides=[...carousel.querySelectorAll('.sv1-carousel-slide')], dots=[...carousel.querySelectorAll('[data-carousel-dot]')]; let current=0, timer;
    if(slides.length<2) return;
    const show=index=>{current=(index+slides.length)%slides.length;slides.forEach((s,i)=>{s.classList.toggle('is-active',i===current);s.tabIndex=i===current?0:-1});dots.forEach((d,i)=>d.setAttribute('aria-current',i===current?'true':'false'));};
    const restart=()=>{clearInterval(timer);timer=setInterval(()=>show(current+1),6000)};
    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click',()=>{show(current-1);restart()}); carousel.querySelector('[data-carousel-next]')?.addEventListener('click',()=>{show(current+1);restart()}); dots.forEach((d,i)=>d.addEventListener('click',()=>{show(i);restart()})); restart();
  });
  const portal=document.getElementById('sv1-notice-portal');
  const closeNotice=notice=>{notice.classList.add('sv1-notice-dismissed');setTimeout(()=>notice.remove(),180)};
  const decorateNotice=notice=>{if(!notice||notice.dataset.sv1Decorated) return; notice.dataset.sv1Decorated='1'; const closeButton=document.createElement('button'); closeButton.type='button'; closeButton.className='sv1-notice-close'; closeButton.setAttribute('aria-label','Fechar aviso'); closeButton.textContent='×'; closeButton.addEventListener('click',()=>closeNotice(notice)); notice.appendChild(closeButton); let startX=0; notice.addEventListener('pointerdown',e=>{startX=e.clientX;notice.setPointerCapture?.(e.pointerId)}); notice.addEventListener('pointerup',e=>{if(Math.abs(e.clientX-startX)>70) closeNotice(notice)}); };
  const moveNotices=()=>{ if(!portal) return; document.querySelectorAll('.woocommerce-message,.woocommerce-info,.woocommerce-error,.woocommerce-notices-wrapper').forEach(node=>{if(!node.closest('#sv1-notice-portal')) portal.appendChild(node); decorateNotice(node.matches('.woocommerce-notices-wrapper')?node.firstElementChild:node)}); portal.querySelectorAll('.woocommerce-message,.woocommerce-info,.woocommerce-error').forEach(decorateNotice); };
  moveNotices(); if(portal) new MutationObserver(moveNotices).observe(document.body,{childList:true,subtree:true});
  document.addEventListener('click',event=>{const button=event.target.closest('.add_to_cart_button,.ajax_add_to_cart,.single_add_to_cart_button'); if(!button||button.dataset.sv1Busy) return; button.dataset.sv1Busy='1'; button.classList.add('sv1-buying'); const original=button.textContent.trim(); button.textContent='Adicionando…'; setTimeout(()=>{button.classList.remove('sv1-buying');button.classList.add('sv1-added');button.textContent='Adicionado ✓';setTimeout(()=>{button.classList.remove('sv1-added');button.textContent=original;delete button.dataset.sv1Busy},1800)},650)});
  const markAdded=button=>{if(button.matches('.add_to_cart_button.added')&&!button.dataset.sv1Confirmed){button.dataset.sv1Confirmed='1';button.classList.add('sv1-added');button.textContent='Adicionado ✓';setTimeout(()=>{button.classList.remove('sv1-added');button.textContent='Comprar';delete button.dataset.sv1Confirmed},1800)}};
  new MutationObserver(records=>records.forEach(record=>{if(record.type==='attributes'&&record.target.matches?.('.add_to_cart_button')) markAdded(record.target)})).observe(document.body,{subtree:true,attributes:true,attributeFilter:['class']});
  const trashButton=remove=>{if(remove&&!remove.querySelector('.sv1-trash-icon')) {remove.textContent='';remove.innerHTML='<svg class="sv1-trash-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7h6v3l-1 10H10L9 10V7m2 0V4h2v3"/></svg><span class="screen-reader-text">Remover produto</span>';}};
  document.querySelectorAll('[aria-label*="Remover"],.remove,.remove_from_cart_button').forEach(trashButton);
  document.addEventListener('click',event=>trashButton(event.target.closest('[aria-label*="Remover"],.remove,.remove_from_cart_button')));
  new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1){if(node.matches?.('[aria-label*="Remover"],.remove,.remove_from_cart_button')) trashButton(node);node.querySelectorAll?.('[aria-label*="Remover"],.remove,.remove_from_cart_button').forEach(trashButton)}}))).observe(document.body,{childList:true,subtree:true});
})();
