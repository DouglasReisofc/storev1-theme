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
  document.querySelectorAll('.sv1-categories').forEach(details => {
    document.addEventListener('click', event => { if (!details.contains(event.target)) details.open = false; });
    details.addEventListener('keydown', event => { if(event.key === 'Escape') { details.open = false; details.querySelector('summary').focus(); } });
  });
})();
