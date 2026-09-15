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
})();
