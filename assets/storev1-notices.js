(() => {
  const portal = document.getElementById('sv1-notice-portal');
  if (!portal) return;
  // Empty cart is a persistent state inside the cart, not an alert on every page load.
  const selector = '.woocommerce-message:not(.cart-empty),.woocommerce-info:not(.cart-empty),.woocommerce-error:not(.cart-empty)';
  const initialized = new WeakSet();
  function decorate(notice) {
    if (initialized.has(notice)) return;
    initialized.add(notice);
    notice.classList.add('sv1-toast');
    // A list must keep valid list-item children; controls live in their own item.
    const control = document.createElement(notice.matches('ul,ol') ? 'li' : 'span');
    control.className = 'sv1-toast-control';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'sv1-notice-close';
    button.setAttribute('aria-label', 'Fechar aviso');
    button.textContent = '×';
    control.appendChild(button);
    notice.appendChild(control);
    const dismiss = () => {
      if (notice._sv1Timer && typeof clearTimeout === 'function') clearTimeout(notice._sv1Timer);
      if (notice.contains(document.activeElement)) document.activeElement.blur();
      notice.remove();
    };
    button.addEventListener('click', event => { event.preventDefault(); event.stopPropagation(); dismiss(); });
    if (typeof setTimeout === 'function') notice._sv1Timer = setTimeout(dismiss, notice.matches('.woocommerce-error') ? 9000 : 6000);
    let gesture = null;
    notice.addEventListener('pointerdown', event => {
      if (!event.isPrimary || event.button !== 0 || event.target.closest('a,button,input,select,textarea,label')) return;
      gesture = {id:event.pointerId, x:event.clientX, y:event.clientY, dx:0};
      notice.setPointerCapture(event.pointerId);
    });
    notice.addEventListener('pointermove', event => {
      if (!gesture || event.pointerId !== gesture.id) return;
      gesture.dx = event.clientX - gesture.x;
      if (Math.abs(gesture.dx) > Math.abs(event.clientY - gesture.y)) {
        notice.style.transform = `translateX(${gesture.dx}px)`;
        notice.style.opacity = String(Math.max(.3, 1 - Math.abs(gesture.dx) / notice.offsetWidth));
      }
    });
    const reset = () => { gesture = null; notice.style.transform = ''; notice.style.opacity = ''; };
    notice.addEventListener('pointerup', event => {
      if (!gesture || event.pointerId !== gesture.id) return;
      const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
      if (Math.abs(dx) >= 60 && Math.abs(dx) > Math.abs(dy)) dismiss();
      reset();
    });
    notice.addEventListener('pointercancel', reset);
    notice.addEventListener('lostpointercapture', reset);
  }
  function isCartRemovalNotice(notice) {
    const text = (notice.textContent || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    return /removid|removed|excluid|exclu[ií]do/.test(text) && /carrinho|produto|item/.test(text);
  }
  function isCartSuccessNotice(notice) {
    const text = (notice.textContent || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const cartTerm = /carrinho|produto|item/.test(text);
    return cartTerm && /adicionad|incluid|removid|excluid|retirad/.test(text);
  }
  function sync() {
    // Keep AJAX notice containers in place; move only the individual messages.
    const dialogs = [...document.querySelectorAll('dialog[open]')];
    const host = dialogs[dialogs.length - 1] || document.body;
    if (portal.parentElement !== host) host.appendChild(portal);
    document.querySelectorAll(selector).forEach(notice => {
      // Product add/remove feedback is deliberately silent: the modal and
      // cart count are the visible confirmation, while errors remain visible.
      if (isCartRemovalNotice(notice) || (notice.matches('.woocommerce-message') && isCartSuccessNotice(notice))) { notice.remove(); return; }
      decorate(notice);
      if (!portal.contains(notice)) portal.appendChild(notice);
    });
  }
  sync();
  new MutationObserver(sync).observe(document.body, {childList:true, subtree:true, attributes:true, attributeFilter:['open']});
})();
