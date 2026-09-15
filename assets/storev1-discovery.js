(() => {
  document.querySelectorAll('[data-category-rail]').forEach(rail => {
    const track = rail.querySelector('.loja1-menu');
    if (!track) return;
    const prev = rail.querySelector('[data-category-prev]');
    const next = rail.querySelector('[data-category-next]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const desktop = matchMedia('(min-width:768px)');
    track.tabIndex = 0;
    track.setAttribute('aria-label', 'Categorias. Arraste para os lados ou use as setas.');
    let frame = 0, timer = 0, interacted = false, gesture = null, dragged = false;
    const maxScroll = () => Math.max(0, track.scrollWidth - track.clientWidth);
    const refresh = () => {
      const overflow = maxScroll() > 2;
      prev.hidden = next.hidden = !overflow;
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= maxScroll() - 2;
    };
    const stop = () => {
      interacted = true;
      cancelAnimationFrame(frame);
      clearTimeout(timer);
    };
    const move = direction => {
      stop();
      track.scrollBy({left: direction * track.clientWidth * .7, behavior: reduced.matches ? 'auto' : 'smooth'});
    };
    prev.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    track.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault(); move(event.key === 'ArrowLeft' ? -1 : 1);
    });
    ['pointerdown', 'wheel', 'focusin', 'keydown'].forEach(type => rail.addEventListener(type, stop, {passive:true}));
    reduced.addEventListener('change', stop);
    desktop.addEventListener('change', stop);
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });
    track.addEventListener('dragstart', event => event.preventDefault());
    track.addEventListener('pointerdown', event => {
      dragged = false;
      // Touch keeps native momentum scrolling; mouse gets the same drag affordance.
      if (event.pointerType !== 'mouse' || event.button !== 0 || !event.isPrimary) return;
      gesture = {id:event.pointerId, x:event.clientX, start:track.scrollLeft};
    });
    track.addEventListener('pointermove', event => {
      if (!gesture || event.pointerId !== gesture.id) return;
      const delta = gesture.x - event.clientX;
      if (Math.abs(delta) < 6 && !dragged) return;
      dragged = true;
      track.classList.add('is-dragging');
      track.setPointerCapture(event.pointerId);
      track.scrollLeft = gesture.start + delta;
    });
    const finish = event => {
      if (!gesture || event.pointerId !== gesture.id) return;
      if (track.hasPointerCapture(event.pointerId)) track.releasePointerCapture(event.pointerId);
      gesture = null;
      track.classList.remove('is-dragging');
      setTimeout(() => { dragged = false; }, 0);
    };
    track.addEventListener('pointerup', finish);
    track.addEventListener('pointercancel', finish);
    track.addEventListener('click', event => {
      if (dragged) { event.preventDefault(); event.stopPropagation(); }
    }, true);
    track.addEventListener('scroll', refresh, {passive:true});
    new ResizeObserver(refresh).observe(track);
    refresh();
    // One gentle end-and-back demonstration per page load, never against the user.
    const introduce = () => {
      if (interacted || reduced.matches || !desktop.matches || maxScroll() <= 2) return;
      const from = track.scrollLeft, end = maxScroll();
      let start;
      const tick = now => {
        if (interacted) return;
        if (start === undefined) start = now;
        const elapsed = now - start;
        const progress = elapsed < 1100 ? elapsed / 1100 : elapsed < 1350 ? 1 : Math.max(0, 1 - (elapsed - 1350) / 1100);
        const eased = .5 - Math.cos(Math.PI * progress) / 2;
        track.scrollLeft = from + (end - from) * eased;
        if (elapsed < 2450) frame = requestAnimationFrame(tick);
        else { track.scrollLeft = from; refresh(); }
      };
      frame = requestAnimationFrame(tick);
    };
    const schedule = () => { timer = setTimeout(introduce, 450); };
    if (document.readyState === 'complete') schedule();
    else window.addEventListener('load', schedule, {once:true});
  });
})();
