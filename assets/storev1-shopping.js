(() => {
  document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('[data-sv1-cart-added]')) return;
    document.querySelector('.loja1-cart-link')?.click();
  });
  function quantities() {
    document.querySelectorAll('.quantity input.qty[type="number"]').forEach(input => {
      const box = input.closest('.quantity');
      if (!box.querySelector('[data-qty-step]') && !input.readOnly) {
        box.classList.add('sv1-quantity');
        [-1,1].forEach(direction => {
          const button = document.createElement('button');
          button.type = 'button'; button.dataset.qtyStep = direction;
          button.textContent = direction < 0 ? '−' : '+';
          button.setAttribute('aria-label', direction < 0 ? 'Diminuir quantidade' : 'Aumentar quantidade');
          button.addEventListener('click', () => {
            if (input.disabled || input.readOnly) return;
            if (!input.value) input.value = input.min || '1';
            if (direction < 0) input.stepDown(); else input.stepUp();
            input.dispatchEvent(new Event('input', {bubbles:true}));
            input.dispatchEvent(new Event('change', {bubbles:true}));
            quantities();
          });
          if(direction < 0) box.insertBefore(button,input); else input.after(button);
        });
      }
      box.querySelectorAll('[data-qty-step]').forEach(button => {
        const value = Number(input.value), min = input.min === '' ? 0 : Number(input.min), max = input.max === '' ? Infinity : Number(input.max);
        const disabled = input.disabled || input.readOnly || (Number(button.dataset.qtyStep)<0 ? value<=min : value>=max);
        if(button.disabled!==disabled) button.disabled=disabled;
      });
    });
  }
  quantities();
  new MutationObserver(quantities).observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['disabled','min','max','readonly']});
  document.addEventListener('input',e=>{if(e.target.matches('.quantity input.qty')) quantities();});

  // Remove cart items in place so the cart stays open for consecutive deletions.
  document.addEventListener('click', event => {
    const link = event.target.closest('a.remove, a.remove_from_cart_button');
    if (!link || !document.querySelector('.woocommerce-cart-form') || !link.href) return;
    event.preventDefault();
    event.stopPropagation();
    if (link.dataset.sv1Removing) return;
    link.dataset.sv1Removing = '1';
    link.setAttribute('aria-busy', 'true');
    const item = link.closest('.cart_item, .mini_cart_item');
    // Remove the visible row immediately. The request still confirms the
    // change server-side; a failure falls back to the canonical cart URL.
    if (item) {
      item.classList.add('sv1-cart-removing');
      requestAnimationFrame(() => item.remove());
    }
    fetch(link.href, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(response => response.ok ? response.text() : Promise.reject(new Error('cart update failed')))
      .then(html => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const currentForm = document.querySelector('.woocommerce-cart-form');
        const nextForm = parsed.querySelector('.woocommerce-cart-form');
        if (currentForm && nextForm) currentForm.replaceWith(nextForm);
        const currentTotals = document.querySelector('.cart_totals');
        const nextTotals = parsed.querySelector('.cart_totals');
        if (currentTotals && nextTotals) currentTotals.replaceWith(nextTotals);
        document.body.dispatchEvent(new CustomEvent('updated_wc_div'));
        if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
      })
      .catch(() => { window.location.assign(link.href); });
  }, true);

  // Prime the cart document on intent, not only on click. This makes the
  // header cart feel instantaneous on desktop and touch devices.
  const cartLink = document.querySelector('.loja1-cart-link');
  if (cartLink && cartLink.href) {
    let prefetched = false;
    const prefetchCart = () => {
      if (prefetched) return;
      prefetched = true;
      const link = document.createElement('link');
      link.rel = 'prefetch'; link.href = cartLink.href;
      document.head.appendChild(link);
    };
    cartLink.addEventListener('pointerenter', prefetchCart, {once:true, passive:true});
    cartLink.addEventListener('touchstart', prefetchCart, {once:true, passive:true});
    cartLink.addEventListener('focus', prefetchCart, {once:true});
  }

  document.querySelectorAll('.related.products').forEach(section => {
    const track = section.querySelector('ul.products'); if(!track) return;
    section.classList.add('sv1-related'); track.tabIndex=0; track.setAttribute('aria-label','Produtos relacionados — role para ver mais');
    const controls=document.createElement('div'); controls.className='sv1-related-controls';
    [-1,1].forEach(direction=>{
      const button=document.createElement('button');button.type='button';button.textContent=direction<0?'‹':'›';
      button.setAttribute('aria-label',direction<0?'Produtos anteriores':'Próximos produtos');
      button.addEventListener('click',()=>track.scrollBy({left:direction*(track.firstElementChild.getBoundingClientRect().width+20),behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'}));
      controls.appendChild(button);
    });
    section.insertBefore(controls,track);
    const refresh = () => {
      const overflow = track.scrollWidth > track.clientWidth + 2;
      controls.hidden = !overflow;
      track.classList.toggle('sv1-related-static',!overflow);
      controls.children[0].disabled = track.scrollLeft <= 2;
      controls.children[1].disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
    };
    track.addEventListener('scroll',refresh,{passive:true});
    new ResizeObserver(refresh).observe(track); refresh();
  });

  document.querySelectorAll('[data-promo-carousel]').forEach(carousel => {
    const slides = [...carousel.querySelectorAll('.sv1-promo-slide')];
    const track = carousel.querySelector('.sv1-promo-track');
    let current = 0, gesture = null, dragged = false, progressTimer, progressAnimation;
    const progress = carousel.querySelector('.sv1-promo-progress span');
    const duration = 6500;
    const show = index => {
      clearTimeout(progressTimer);
      current = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        slide.hidden = i !== current;
        slide.style.transform = '';
        const video = slide.querySelector('video');
        if (video) {
          if (i === current && !document.hidden) video.play().catch(() => {});
          else video.pause();
        }
      });
      progressAnimation?.cancel();
      if (slides.length > 1 && !document.hidden) {
        if (progress) progressAnimation = progress.animate([{transform:'scaleX(0)'},{transform:'scaleX(1)'}], {duration, fill:'forwards'});
        progressTimer = setTimeout(() => show(current + 1), duration);
      }
    };
    carousel.querySelector('[data-promo-prev]')?.addEventListener('click', () => show(current - 1));
    carousel.querySelector('[data-promo-next]')?.addEventListener('click', () => show(current + 1));
    track.addEventListener('keydown', event => {
      if (!['ArrowLeft','ArrowRight'].includes(event.key)) return;
      event.preventDefault(); show(current + (event.key === 'ArrowLeft' ? -1 : 1));
    });
    track.addEventListener('dragstart', event => event.preventDefault());
    track.addEventListener('pointerdown', event => {
      if (!event.isPrimary || event.button !== 0 || slides.length < 2) return;
      gesture = {id:event.pointerId, x:event.clientX, y:event.clientY}; dragged = false;
    });
    track.addEventListener('pointermove', event => {
      if (!gesture || event.pointerId !== gesture.id) return;
      const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
      if (Math.abs(dx) > 10 && Math.abs(dx) > Math.abs(dy)) {
        dragged = true; track.setPointerCapture(event.pointerId);
        slides[current].style.transform = `translateX(${dx * .35}px)`;
      }
    });
    track.addEventListener('pointerup', event => {
      if (!gesture || event.pointerId !== gesture.id) return;
      const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
      if (Math.abs(dx) >= 45 && Math.abs(dx) > Math.abs(dy)) {
        dragged = true; show(current + (dx < 0 ? 1 : -1));
      } else slides[current].style.transform = '';
      gesture = null;
      setTimeout(() => { dragged = false; }, 250);
    });
    track.addEventListener('pointercancel', () => {
      gesture = null; dragged = false; slides[current].style.transform = '';
    });
    track.addEventListener('click', event => {
      if (dragged) { event.preventDefault(); event.stopPropagation(); }
    }, true);
    document.addEventListener('visibilitychange', () => show(current));
    show(0);
  });
})();
