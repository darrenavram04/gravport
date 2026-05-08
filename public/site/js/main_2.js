document.addEventListener("DOMContentLoaded", function () {
  const sliderEl = document.querySelector(".dataset-swiper");

  if (typeof Swiper !== "undefined" && sliderEl) {
    new Swiper(sliderEl, {
      loop: true,
      speed: 800,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
      centeredSlides: true,
      slidesPerView: "auto",
      spaceBetween: 40,
      navigation: {
        nextEl: ".dataset-swiper-next",
        prevEl: ".dataset-swiper-prev",
      },
      pagination: {
        el: ".dataset-swiper-pagination",
        clickable: true,
      },
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const words = [
    "Geospatialists.",
    "Map Makers.",
    "Data Storytellers.",
    "Gravity Explorers."
  ];

  const scene = document.getElementById("sceneHome");
  if (!scene) return;

  let index = 0;
  const switchDuration = 3200;

  function applyWord(i) {
    const sharp = scene.querySelector("#heroDynamic");
    const blur = scene.querySelector("#heroDynamic_blur");

    if (!sharp) return;

    sharp.classList.add("is-switching");
    if (blur) blur.classList.add("is-switching");

    setTimeout(() => {
      sharp.textContent = words[i];
      if (blur) blur.textContent = words[i];

      sharp.classList.remove("is-switching");
      if (blur) blur.classList.remove("is-switching");
    }, 260);
  }

  applyWord(index);

  setInterval(() => {
    index = (index + 1) % words.length;
    applyWord(index);
  }, switchDuration);
});

(function () {
  const hero = document.querySelector(".hero");
  const cursor = document.getElementById("homeCursor");

  if (!hero || !cursor) return;

  hero.addEventListener("mouseenter", () => {
    cursor.style.opacity = "1";
  });

  hero.addEventListener("mouseleave", () => {
    cursor.style.opacity = "0";
  });

  hero.addEventListener("mousemove", (event) => {
    cursor.style.transform = `translate(${event.clientX}px, ${event.clientY}px) translate(-50%, -50%)`;
  });

  const interactive = hero.querySelectorAll("a, button, .btn");
  interactive.forEach((element) => {
    element.addEventListener("mouseenter", () => cursor.classList.add("--active"));
    element.addEventListener("mouseleave", () => cursor.classList.remove("--active"));
  });
})();

document.addEventListener("DOMContentLoaded", function () {
  document.body.classList.add("motion-ready");

  const shareButton = document.getElementById("siteShareBtn");
  const toast = document.getElementById("siteToast");
  const navLinks = Array.from(document.querySelectorAll(".site-nav__link[data-nav-section]"));
  const revealTargets = document.querySelectorAll(
    ".section-heading, .media-panel, .content-panel, .tool-card, .tool-visual, .team-member, .contact-form-shell"
  );

  function showToast(message) {
    if (!toast) return;

    toast.textContent = message;
    toast.classList.add("is-visible");

    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => {
      toast.classList.remove("is-visible");
    }, 2400);
  }

  function setActiveNav(sectionId) {
    navLinks.forEach((link) => {
      link.classList.toggle("is-active", link.dataset.navSection === sectionId);
    });
  }

  if (navLinks.length) {
    setActiveNav("home");

    const homeSection = document.getElementById("home");
    const observedSections = Array.from(
      new Set(
        [
          homeSection,
          ...navLinks
            .map((link) => document.getElementById(link.dataset.navSection))
            .filter(Boolean),
        ].filter(Boolean)
      )
    );

    if ("IntersectionObserver" in window && observedSections.length) {
      const navObserver = new IntersectionObserver(
        (entries) => {
          const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

          if (!visible) return;

          const sectionId = visible.target.id === "home" ? "home" : visible.target.id;
          setActiveNav(sectionId);
        },
        {
          rootMargin: "-24% 0px -48% 0px",
          threshold: [0.18, 0.4, 0.65],
        }
      );

      observedSections.forEach((section) => navObserver.observe(section));
    } else {
      setActiveNav("home");
    }
  }

  if ("IntersectionObserver" in window && revealTargets.length) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          entry.target.classList.add("is-inview");
          revealObserver.unobserve(entry.target);
        });
      },
      {
        rootMargin: "0px 0px -12% 0px",
        threshold: 0.12,
      }
    );

    revealTargets.forEach((target) => revealObserver.observe(target));
  } else {
    revealTargets.forEach((target) => target.classList.add("is-inview"));
  }

  if (shareButton) {
    shareButton.addEventListener("click", async function () {
      const shareData = {
        title: document.title,
        text: "Explore GravPort Jawa-Bali gravity anomaly portal",
        url: window.location.href,
      };

      try {
        if (navigator.share) {
          await navigator.share(shareData);
          showToast("Link halaman siap dibagikan.");
          return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(window.location.href);
          showToast("Link halaman berhasil disalin.");
          return;
        }
      } catch (error) {
        showToast("Aksi berbagi dibatalkan.");
        return;
      }

      const helper = document.createElement("input");
      helper.value = window.location.href;
      document.body.appendChild(helper);
      helper.select();
      document.execCommand("copy");
      helper.remove();
      showToast("Link halaman berhasil disalin.");
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion) return;

  const hero = document.getElementById("sceneHome");
  const datasetStage = document.querySelector(".dataset-constellation__visual-shell, .dataset-signal__visual-shell");

  if (hero) {
    let rafId = null;

    const resetHero = () => {
      hero.style.setProperty("--hero-shift-x", "0px");
      hero.style.setProperty("--hero-shift-y", "0px");
      hero.style.setProperty("--hero-tilt-x", "0deg");
      hero.style.setProperty("--hero-tilt-y", "0deg");
    };

    hero.addEventListener("mousemove", (event) => {
      const rect = hero.getBoundingClientRect();
      const px = (event.clientX - rect.left) / rect.width - 0.5;
      const py = (event.clientY - rect.top) / rect.height - 0.5;

      if (rafId) cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(() => {
        hero.style.setProperty("--hero-shift-x", `${px * 36}px`);
        hero.style.setProperty("--hero-shift-y", `${py * 24}px`);
        hero.style.setProperty("--hero-tilt-x", `${py * -3.8}deg`);
        hero.style.setProperty("--hero-tilt-y", `${px * 4.8}deg`);
      });
    });

    hero.addEventListener("mouseleave", resetHero);
    resetHero();
  }

  if (datasetStage) {
    const resetStage = () => {
      datasetStage.style.setProperty("--stage-glow-x", "50%");
      datasetStage.style.setProperty("--stage-glow-y", "50%");
      datasetStage.style.setProperty("--stage-shift-x", "0px");
      datasetStage.style.setProperty("--stage-shift-y", "0px");
    };

    datasetStage.addEventListener("mousemove", (event) => {
      const rect = datasetStage.getBoundingClientRect();
      const x = ((event.clientX - rect.left) / rect.width) * 100;
      const y = ((event.clientY - rect.top) / rect.height) * 100;
      const px = (event.clientX - rect.left) / rect.width - 0.5;
      const py = (event.clientY - rect.top) / rect.height - 0.5;

      datasetStage.style.setProperty("--stage-glow-x", `${x}%`);
      datasetStage.style.setProperty("--stage-glow-y", `${y}%`);
      datasetStage.style.setProperty("--stage-shift-x", `${px * 18}px`);
      datasetStage.style.setProperty("--stage-shift-y", `${py * 14}px`);
    });

    datasetStage.addEventListener("mouseleave", resetStage);
    resetStage();
  }
});
