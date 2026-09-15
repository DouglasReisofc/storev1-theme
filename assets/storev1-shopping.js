(() => {
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

  document.querySelectorAll('[data-promo-carousel]').forEach(carousel=>{
    const slides=[...carousel.querySelectorAll('.sv1-promo-slide')];if(slides.length<2)return;
    const pause=carousel.querySelector('[data-promo-pause]');let current=0,timer=null,paused=matchMedia('(prefers-reduced-motion: reduce)').matches;
    const show=index=>{current=(index+slides.length)%slides.length;slides.forEach((slide,i)=>slide.hidden=i!==current);carousel.querySelector('[data-promo-count]').textContent=`${current+1} / ${slides.length}`;};
    const stop=()=>{clearInterval(timer);timer=null;};
    const start=()=>{stop();if(!paused&&!document.hidden)timer=setInterval(()=>show(current+1),6000);};
    const label=()=>{pause.textContent=paused?'Reproduzir':'Pausar';pause.setAttribute('aria-label',paused?'Reproduzir carrossel':'Pausar carrossel');};
    carousel.querySelector('[data-promo-prev]').addEventListener('click',()=>{show(current-1);});
    carousel.querySelector('[data-promo-next]').addEventListener('click',()=>{show(current+1);});
    pause.addEventListener('click',()=>{paused=!paused;label();start();});
    carousel.addEventListener('pointerenter',stop);carousel.addEventListener('pointerleave',start);
    carousel.addEventListener('focusin',stop);carousel.addEventListener('focusout',e=>{if(!carousel.contains(e.relatedTarget))start();});
    document.addEventListener('visibilitychange',()=>document.hidden?stop():start());label();start();
  });
})();
