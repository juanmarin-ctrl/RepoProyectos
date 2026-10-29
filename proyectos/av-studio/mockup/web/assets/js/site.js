/* AV Studio Academy — salon atmosphere + motion */
(function () {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const path = (location.pathname.split("/").pop() || "index.html").toLowerCase();
  const isHome = path === "" || path === "index.html";

  /* ---------- Ambient world ---------- */
  if (!document.querySelector(".bg-world")) {
    const world = document.createElement("div");
    world.className = "bg-world";
    world.setAttribute("aria-hidden", "true");
    world.innerHTML = `
      <div class="bg-world__wash"></div>
      <div class="bg-world__blob bg-world__blob--a"></div>
      <div class="bg-world__blob bg-world__blob--b"></div>
      <div class="bg-world__blob bg-world__blob--c"></div>
      <div class="bg-world__dots"></div>
      <div class="bg-world__grain"></div>
    `;
    document.body.prepend(world);
  }

  /* ---------- Salon entrance veil ---------- */
  if (!document.body.classList.contains("av-no-veil") && !document.querySelector(".salon-veil") && !reduceMotion) {
    const veil = document.createElement("div");
    veil.className = "salon-veil";
    veil.setAttribute("aria-hidden", "true");
    veil.innerHTML = `
      <div class="salon-veil__panel salon-veil__panel--left"></div>
      <div class="salon-veil__panel salon-veil__panel--right"></div>
      <div class="salon-veil__center">
        <img src="assets/img/monogram-gold.png" alt="">
        <p>AV Studio Academy</p>
        <span>Bienvenida</span>
      </div>
    `;
    document.body.appendChild(veil);
    document.body.classList.add("is-entering");
    requestAnimationFrame(() => {
      veil.classList.add("is-open");
      document.body.classList.add("is-entered");
    });
    setTimeout(() => {
      veil.remove();
      document.body.classList.remove("is-entering");
    }, 2200);
  } else {
    document.body.classList.add("is-entered");
  }

  /* ---------- Soft salon light (cursor) ---------- */
  if (!reduceMotion && window.matchMedia("(pointer:fine)").matches) {
    const light = document.createElement("div");
    light.className = "salon-light";
    light.setAttribute("aria-hidden", "true");
    document.body.appendChild(light);
    let lx = window.innerWidth / 2;
    let ly = window.innerHeight / 2;
    let tx = lx;
    let ty = ly;
    window.addEventListener(
      "pointermove",
      (e) => {
        tx = e.clientX;
        ty = e.clientY;
      },
      { passive: true }
    );
    const tickLight = () => {
      lx += (tx - lx) * 0.08;
      ly += (ty - ly) * 0.08;
      light.style.transform = `translate(${lx}px, ${ly}px)`;
      requestAnimationFrame(tickLight);
    };
    tickLight();
  }

  /* ---------- Floating glitter particles ---------- */
  if (!reduceMotion && !document.querySelector(".salon-glitter")) {
    const glitter = document.createElement("div");
    glitter.className = "salon-glitter";
    glitter.setAttribute("aria-hidden", "true");
    for (let i = 0; i < 18; i++) {
      const p = document.createElement("span");
      p.style.left = Math.random() * 100 + "%";
      p.style.top = Math.random() * 100 + "%";
      p.style.animationDelay = Math.random() * 8 + "s";
      p.style.animationDuration = 7 + Math.random() * 8 + "s";
      glitter.appendChild(p);
    }
    document.body.appendChild(glitter);
  }

  /* ---------- Scroll progress ---------- */
  if (!document.querySelector(".scroll-progress")) {
    const bar = document.createElement("div");
    bar.className = "scroll-progress";
    bar.setAttribute("aria-hidden", "true");
    document.body.appendChild(bar);
  }

  /* ---------- Header / Footer ---------- */
  const navItems = [
    { href: "index.html", label: "Inicio" },
    { href: "cursos.html", label: "Cursos" },
    { href: "sobre-nosotros.html", label: "Sobre nosotros" },
    { href: "servicios.html", label: "Servicios" },
    { href: "blog.html", label: "Blog" },
    { href: "contacto.html", label: "Contacto" },
  ];

  function isActive(href) {
    if (href === "index.html") return isHome;
    if (href === "cursos.html" && path.indexOf("curso-") === 0) return true;
    return path === href;
  }

  function headerHTML(solid) {
    return `
<header class="site-header${solid ? " is-solid" : ""}" data-header>
  <div class="header-inner">
    <a class="brand" href="index.html" aria-label="AV Studio Academy">
      <img class="brand-mark" src="assets/img/monogram-gold.png" alt="AV Studio">
      <div class="brand-text">
        <strong>AV STUDIO</strong>
        <span>Academy</span>
      </div>
    </a>
    <nav class="nav" data-nav>
      ${navItems
        .map(
          (item) =>
            `<a href="${item.href}" class="${isActive(item.href) ? "is-active" : ""}">${item.label}</a>`
        )
        .join("")}
    </nav>
    <a class="btn btn--ink header-cta" href="cursos.html">Ver cursos</a>
    <button class="menu-toggle" type="button" aria-label="Abrir menú" data-menu-toggle>
      <span></span>
    </button>
  </div>
</header>`;
  }

  function footerHTML() {
    return `
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <img class="brand-mark brand-mark--light" src="assets/img/monogram-gold.png" alt="AV Studio">
      <p>Formación profesional en tricología cosmética, color y química capilar. Ciencia aplicada a la belleza.</p>
    </div>
    <div class="footer-col">
      <h4>Academia</h4>
      <a href="cursos.html">Cursos online</a>
      <a href="sobre-nosotros.html">Sobre nosotros</a>
      <a href="servicios.html">Servicios</a>
      <a href="blog.html">Blog</a>
    </div>
    <div class="footer-col">
      <h4>Contacto</h4>
      <a href="mailto:contacto@abstudioacademy.com">contacto@abstudioacademy.com</a>
      <a href="contacto.html">Formulario</a>
      <a href="https://abstudioacademy.com" target="_blank" rel="noopener">abstudioacademy.com</a>
      <span>Calbuco, Chile</span>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <a href="aviso-legal.html">Aviso legal</a>
      <a href="privacidad.html">Privacidad</a>
      <a href="cookies.html">Cookies</a>
      <a href="reembolsos.html">Reembolsos</a>
      <a href="terminos.html">Términos</a>
    </div>
  </div>
  <div class="container footer-bottom">
    <p>© ${new Date().getFullYear()} AV Studio Academy · América Villasmil. Todos los derechos reservados.</p>
    <div class="footer-legal">
      <a href="privacidad.html">Privacidad</a>
      <a href="cookies.html">Cookies</a>
      <a href="terminos.html">Términos</a>
    </div>
  </div>
</footer>`;
  }

  const headerMount = document.querySelector("[data-site-header]");
  const footerMount = document.querySelector("[data-site-footer]");
  if (headerMount) headerMount.outerHTML = headerHTML(headerMount.dataset.solid === "true");
  if (footerMount) footerMount.outerHTML = footerHTML();

  const header = document.querySelector("[data-header]");
  const toggle = document.querySelector("[data-menu-toggle]");
  const nav = document.querySelector("[data-nav]");
  if (toggle && nav) toggle.addEventListener("click", () => nav.classList.toggle("is-open"));

  /* ---------- Scroll choreography ---------- */
  const progress = document.querySelector(".scroll-progress");
  const parallaxNodes = [];

  document.querySelectorAll(".hero__media img, .bg-scene__photo, .hero__side-card, .bg-scene__arc, .bg-scene__mono").forEach((el, i) => {
    el.classList.add("parallax-item");
    parallaxNodes.push({
      el,
      speed: el.matches("img") ? 0.12 + (i % 3) * 0.04 : 0.05 + (i % 4) * 0.03,
    });
  });

  let ticking = false;
  function onScroll() {
    const y = window.scrollY || 0;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    if (progress && max > 0) {
      progress.style.transform = `scaleX(${Math.min(1, y / max)})`;
    }
    if (header && !header.classList.contains("is-solid")) {
      header.classList.toggle("is-scrolled", y > 24);
    }
    if (!reduceMotion) {
      parallaxNodes.forEach(({ el, speed }) => {
        const rect = el.getBoundingClientRect();
        const offset = (rect.top - window.innerHeight * 0.5) * speed;
        el.style.setProperty("--parallax-y", `${offset.toFixed(2)}px`);
      });
      document.body.style.setProperty("--scroll-y", `${y}px`);
    }
    ticking = false;
  }

  window.addEventListener(
    "scroll",
    () => {
      if (!ticking) {
        requestAnimationFrame(onScroll);
        ticking = true;
      }
    },
    { passive: true }
  );
  onScroll();

  /* ---------- Reveal with stagger ---------- */
  const reveals = document.querySelectorAll(".reveal");
  const showReveal = (el) => el.classList.add("is-in");

  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const parent = entry.target.parentElement;
          if (parent) {
            const siblings = [...parent.querySelectorAll(":scope > .reveal, :scope .reveal")].filter(
              (n) => n.parentElement === parent || n.closest(".grid-3, .grid-2, .stats-panel, .module-grid, .feature-list")
            );
            const group = siblings.length ? siblings : [entry.target];
            group.forEach((node, idx) => {
              setTimeout(() => showReveal(node), reduceMotion ? 0 : idx * 90);
            });
          } else {
            showReveal(entry.target);
          }
          io.unobserve(entry.target);
        });
      },
      { threshold: 0.1, rootMargin: "0px 0px -6% 0px" }
    );
    reveals.forEach((el) => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight * 0.9) showReveal(el);
      else io.observe(el);
    });
    setTimeout(() => reveals.forEach(showReveal), 2400);
  } else {
    reveals.forEach(showReveal);
  }

  /* ---------- Image shimmer / salon mirror feel ---------- */
  document.querySelectorAll(".course-card, .service-tile, .blog-card, .split-media, .floating-gallery figure, .visual-strip figure").forEach((card) => {
    card.classList.add("salon-surface");
  });

  /* ---------- Soft magnetic buttons ---------- */
  if (!reduceMotion && window.matchMedia("(pointer:fine)").matches) {
    document.querySelectorAll(".btn").forEach((btn) => {
      btn.addEventListener("pointermove", (e) => {
        const r = btn.getBoundingClientRect();
        const x = e.clientX - r.left - r.width / 2;
        const y = e.clientY - r.top - r.height / 2;
        btn.style.transform = `translate(${x * 0.12}px, ${y * 0.18}px)`;
      });
      btn.addEventListener("pointerleave", () => {
        btn.style.transform = "";
      });
    });
  }

  /* ---------- Forms ---------- */
  document.querySelectorAll("[data-contact-form]").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const note = form.querySelector("[data-form-note]");
      if (note) {
        note.textContent =
          "Mensaje listo para conectar en WordPress (Elementor Form / SMTP). En este mockup no se envía.";
      }
      form.reset();
    });
  });
})();
