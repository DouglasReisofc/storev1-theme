(() => {
  const ready = () => {
    const drawer = document.querySelector('#sv1-support-drawer');
    const modal = document.querySelector('#sv1-support-modal');
    const open = document.querySelector('[data-sv1-support-open]');
    if (!drawer || !modal || !open) return;
    const emailButtons = document.querySelectorAll('[data-sv1-email-support]');
    const closeButtons = document.querySelectorAll('[data-sv1-support-close], [data-sv1-support-form-close]');
    const show = (el) => { el.hidden = false; document.documentElement.classList.add('sv1-support-open'); };
    const hide = (el) => { el.hidden = true; if (drawer.hidden && modal.hidden) { document.documentElement.classList.remove('sv1-support-open'); open.setAttribute('aria-expanded', 'false'); } };
    open.addEventListener('click', () => { open.setAttribute('aria-expanded', 'true'); show(drawer); });
    drawer.addEventListener('click', event => { if (event.target === drawer) hide(drawer); });
    emailButtons.forEach(button => button.addEventListener('click', () => { hide(drawer); show(modal); modal.querySelector('input,select,textarea')?.focus(); }));
    closeButtons.forEach(button => button.addEventListener('click', () => { hide(drawer); hide(modal); open.setAttribute('aria-expanded', 'false'); }));
    modal.addEventListener('click', event => { if (event.target === modal) hide(modal); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') { hide(drawer); hide(modal); open.setAttribute('aria-expanded', 'false'); } });
    const form = modal.querySelector('[data-sv1-support-form]');
    const feedback = modal.querySelector('[data-sv1-support-feedback]');
    form?.addEventListener('submit', async event => {
      event.preventDefault();
      const submit = form.querySelector('button[type="submit"]');
      submit.disabled = true; feedback.className = 'sv1-support-form-feedback is-loading'; feedback.textContent = 'Enviando solicitação…';
      try {
        const response = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body: new FormData(form) });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data?.data?.message || 'Não foi possível enviar agora.');
        feedback.className = 'sv1-support-form-feedback is-success'; feedback.textContent = data.data.message;
        form.reset();
      } catch (error) { feedback.className = 'sv1-support-form-feedback is-error'; feedback.textContent = error.message; }
      finally { submit.disabled = false; }
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
})();
