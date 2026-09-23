(() => {
  const start = () => {
    if (!window.lottie) return;
    document.querySelectorAll('[data-sv1-checkout-lottie]').forEach((holder) => {
      if (holder.dataset.loaded || !holder.dataset.lottieUrl) return;
      holder.dataset.loaded = '1';
      window.lottie.loadAnimation({
        container: holder,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: holder.dataset.lottieUrl,
      });
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
  else start();
})();
