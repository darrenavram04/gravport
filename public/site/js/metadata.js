(function () {
  const links = Array.from(document.querySelectorAll('[data-meta-nav]'));
  const sections = Array.from(document.querySelectorAll('.meta-section[id]'));

  if (!links.length || !sections.length || !('IntersectionObserver' in window)) {
    return;
  }

  const setActive = (id) => {
    links.forEach((link) => {
      link.classList.toggle('is-active', link.getAttribute('data-meta-nav') === id);
    });
  };

  const observer = new IntersectionObserver((entries) => {
    const visible = entries
      .filter((entry) => entry.isIntersecting)
      .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

    if (!visible) {
      return;
    }

    const id = visible.target.getAttribute('id');
    if (id) {
      setActive(id);
    }
  }, {
    rootMargin: '-18% 0px -52% 0px',
    threshold: [0.15, 0.4, 0.65],
  });

  sections.forEach((section) => observer.observe(section));
})();
