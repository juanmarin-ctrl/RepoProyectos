/* AV Studio Academy — salon atmosphere + scroll motion (v2.6.6) */
(function () {
  if (!document.body) return;
  document.body.classList.add("av-studio-site");

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var cfg = window.avsiFrontend || {};

  /* Limpiar restos de velo de versiones anteriores */
  try {
    sessionStorage.removeItem("av_veil_nav");
    sessionStorage.removeItem("av_veil_done");
  } catch (e) {}
  document.documentElement.classList.remove("av-veil-pending");
  document.body.classList.remove("av-is-entering");
  document.body.classList.add("av-is-entered");
  document.querySelectorAll(".av-salon-veil, #avsi-veil-boot, #avsi-salon-veil").forEach(function (n) {
    n.remove();
  });

  /* ---------- Background world ---------- */
  if (!document.querySelector(".av-bg-world")) {
    var world = document.createElement("div");
    world.className = "av-bg-world";
    world.setAttribute("aria-hidden", "true");
    world.innerHTML =
      '<div class="av-bg-world__wash"></div>' +
      '<div class="av-bg-world__blob av-bg-world__blob--a"></div>' +
      '<div class="av-bg-world__blob av-bg-world__blob--b"></div>' +
      '<div class="av-bg-world__blob av-bg-world__blob--c"></div>' +
      '<div class="av-bg-world__dots"></div>' +
      '<div class="av-bg-world__grain"></div>';
    document.body.prepend(world);
  }

  /* ---------- Scroll progress ---------- */
  if (!document.querySelector(".av-scroll-progress")) {
    var bar = document.createElement("div");
    bar.className = "av-scroll-progress";
    bar.setAttribute("aria-hidden", "true");
    document.body.appendChild(bar);
  }
  var progress = document.querySelector(".av-scroll-progress");

  /* ---------- Soft light follow ---------- */
  if (!reduce && window.matchMedia("(pointer:fine)").matches) {
    var light = document.createElement("div");
    light.className = "av-salon-light";
    light.setAttribute("aria-hidden", "true");
    document.body.appendChild(light);
    var lx = window.innerWidth / 2;
    var ly = window.innerHeight / 2;
    var tx = lx;
    var ty = ly;
    window.addEventListener(
      "pointermove",
      function (e) {
        tx = e.clientX;
        ty = e.clientY;
      },
      { passive: true }
    );
    (function tick() {
      lx += (tx - lx) * 0.08;
      ly += (ty - ly) * 0.08;
      light.style.transform = "translate(" + lx + "px," + ly + "px)";
      requestAnimationFrame(tick);
    })();
  }

  /* ---------- Glitter ---------- */
  if (!reduce && !document.querySelector(".av-salon-glitter")) {
    var glitter = document.createElement("div");
    glitter.className = "av-salon-glitter";
    glitter.setAttribute("aria-hidden", "true");
    for (var g = 0; g < 18; g++) {
      var p = document.createElement("span");
      p.style.left = Math.random() * 100 + "%";
      p.style.top = Math.random() * 100 + "%";
      p.style.animationDelay = Math.random() * 8 + "s";
      p.style.animationDuration = 7 + Math.random() * 8 + "s";
      glitter.appendChild(p);
    }
    document.body.appendChild(glitter);
  }

  /* ---------- Header glass on scroll ---------- */
  var header =
    document.querySelector(".av-site-header") ||
    document.querySelector('[data-elementor-type="header"] .av-site-header');
  if (header) {
    var hTicking = false;
    var onHeaderScroll = function () {
      header.classList.toggle("is-scrolled", (window.scrollY || 0) > 24);
      hTicking = false;
    };
    window.addEventListener(
      "scroll",
      function () {
        if (!hTicking) {
          requestAnimationFrame(onHeaderScroll);
          hTicking = true;
        }
      },
      { passive: true }
    );
    onHeaderScroll();
  }

  /* ---------- Hero atmosphere (sparks + glow + zoom) ---------- */
  document.querySelectorAll(".av-hero").forEach(function (hero) {
    hero.classList.add("av-hero--zooming");
    if (reduce || hero.querySelector(".av-hero-atmosphere")) return;
    var atm = document.createElement("div");
    atm.className = "av-hero-atmosphere";
    atm.setAttribute("aria-hidden", "true");
    var spots = [
      { t: "22%", l: "12%" },
      { t: "38%", l: "28%", d: ".6s" },
      { t: "28%", r: "18%", d: "1.2s" },
      { t: "auto", b: "28%", r: "30%", d: "1.8s" },
      { t: "auto", b: "36%", l: "18%", d: "2.2s" },
    ];
    spots.forEach(function (s) {
      var spark = document.createElement("span");
      spark.className = "av-hero-spark";
      if (s.t) spark.style.top = s.t;
      if (s.b) spark.style.bottom = s.b;
      if (s.l) spark.style.left = s.l;
      if (s.r) spark.style.right = s.r;
      if (s.d) spark.style.animationDelay = s.d;
      atm.appendChild(spark);
    });
    var glow = document.createElement("div");
    glow.className = "av-hero-glow";
    atm.appendChild(glow);
    hero.appendChild(atm);
  });

  /* ---------- Scroll cue styling ---------- */
  document.querySelectorAll(".av-hero-copy a[href*='#']").forEach(function (a) {
    if (a.classList.contains("elementor-button")) return;
    a.classList.add("av-scroll-cue");
    if (!a.querySelector("i")) {
      var i = document.createElement("i");
      a.appendChild(i);
    }
  });

  /* ---------- Marquee track ---------- */
  document.querySelectorAll(".av-marquee").forEach(function (mq) {
    if (mq.querySelector(".av-marquee-track")) return;
    var textEl = mq.querySelector(".elementor-widget-text-editor, .elementor-widget-container, p");
    var raw = (textEl && textEl.textContent) || mq.textContent || "";
    raw = raw.replace(/\s+/g, " ").trim();
    if (!raw) return;
    var parts = raw.split(/[·•|✦★]| {2,}/).map(function (s) {
      return s.trim();
    }).filter(Boolean);
    if (parts.length < 2) {
      parts = raw.split(/[,;]/).map(function (s) {
        return s.trim();
      }).filter(Boolean);
    }
    if (!parts.length) parts = [raw];
    var track = document.createElement("div");
    track.className = "av-marquee-track";
    var doubled = parts.concat(parts);
    doubled.forEach(function (label) {
      var span = document.createElement("span");
      span.textContent = label;
      track.appendChild(span);
    });
    /* Hide original widgets, append track */
    Array.prototype.slice.call(mq.children).forEach(function (ch) {
      if (!ch.classList.contains("av-marquee-track")) {
        ch.style.display = "none";
      }
    });
    mq.appendChild(track);
  });

  /* ---------- Section decorative scenes (letras AV / Studio) ---------- */
  var sceneTargets = document.querySelectorAll(
    ".av-section--academy, .av-section--mist, .av-section.av-section--mist, .av-hero + .av-section"
  );
  if (!sceneTargets.length) {
    sceneTargets = document.querySelectorAll(".av-section");
  }
  Array.prototype.slice.call(sceneTargets).forEach(function (sec, idx) {
    if (sec.querySelector(".av-section-scene")) return;
    if (idx > 4) return;
    var scene = document.createElement("div");
    scene.className = "av-section-scene";
    scene.setAttribute("aria-hidden", "true");
    var mono =
      idx === 0
        ? '<div class="av-section-scene__mono">AV</div><div class="av-section-scene__grid"></div>'
        : idx === 1
          ? '<div class="av-section-scene__mono av-section-scene__mono--studio">Studio</div>'
          : idx === 2
            ? '<div class="av-section-scene__mono">AV</div>'
            : '<div class="av-section-scene__mono av-section-scene__mono--studio">Academy</div>';
    var arcClass = idx % 2 === 0 ? "av-section-scene__arc--tl" : "av-section-scene__arc--tr";
    scene.innerHTML =
      '<div class="av-section-scene__arc ' +
      arcClass +
      '"></div>' +
      '<div class="av-section-scene__arc av-section-scene__arc--br"></div>' +
      mono +
      '<span class="av-section-scene__petal" style="top:22%;left:18%;"></span>' +
      '<span class="av-section-scene__petal" style="top:55%;left:8%;animation-delay:1s;"></span>' +
      '<span class="av-section-scene__petal" style="top:30%;right:22%;animation-delay:2s;"></span>';
    var pos = window.getComputedStyle(sec).position;
    if (pos === "static" || !pos) sec.style.position = "relative";
    sec.prepend(scene);
  });

  /* ---------- Salon surface shimmer on cards ---------- */
  document
    .querySelectorAll(".av-course-card, .av-tile, .av-blog-card, .av-split-media, .av-card.av-quote, .av-gallery .av-img")
    .forEach(function (el) {
      el.classList.add("av-salon-surface");
    });

  /* ---------- Magnetic buttons ---------- */
  if (!reduce && window.matchMedia("(pointer:fine)").matches) {
    document.querySelectorAll(".av-btn .elementor-button").forEach(function (btn) {
      btn.addEventListener("pointermove", function (e) {
        var r = btn.getBoundingClientRect();
        var x = e.clientX - r.left - r.width / 2;
        var y = e.clientY - r.top - r.height / 2;
        btn.style.transform = "translate(" + x * 0.12 + "px," + y * 0.18 + "px)";
      });
      btn.addEventListener("pointerleave", function () {
        btn.style.transform = "";
      });
    });
  }

  /* ---------- Parallax targets ---------- */
  var parallaxNodes = [];
  document.querySelectorAll(".av-hero-side, .av-split-media img, .av-gallery img").forEach(function (el, i) {
    el.classList.add("av-parallax");
    parallaxNodes.push({
      el: el,
      speed: 0.05 + (i % 4) * 0.03,
    });
  });

  /* ---------- Scroll: progress + parallax ---------- */
  var ticking = false;
  function onScroll() {
    var y = window.scrollY || 0;
    var max = document.documentElement.scrollHeight - window.innerHeight;
    if (progress && max > 0) {
      progress.style.transform = "scaleX(" + Math.min(1, y / max) + ")";
    }
    if (!reduce) {
      parallaxNodes.forEach(function (node) {
        var rect = node.el.getBoundingClientRect();
        var offset = (rect.top - window.innerHeight * 0.5) * node.speed;
        node.el.style.setProperty("--av-parallax-y", offset.toFixed(2) + "px");
      });
    }
    ticking = false;
  }
  window.addEventListener(
    "scroll",
    function () {
      if (!ticking) {
        requestAnimationFrame(onScroll);
        ticking = true;
      }
    },
    { passive: true }
  );
  onScroll();

  /* ---------- Reveal with stagger ---------- */
  var candidates = document.querySelectorAll(
    ".av-hero-copy, .av-hero-side, .av-section > .e-con, .av-card, .av-course-card, .av-tile, .av-blog-card, .av-quote, .av-stat, .av-feature, .av-cta-panel, .elementor-widget-heading, .elementor-widget-text-editor, .elementor-widget-image, .elementor-widget-button"
  );
  var revealList = [];
  candidates.forEach(function (el, i) {
    if (
      el.closest(".av-salon-veil") ||
      el.closest(".av-site-header") ||
      el.closest('[data-elementor-type="header"]') ||
      el.closest(".av-marquee") ||
      el.closest(".av-bg-world")
    ) {
      return;
    }
    if (el.classList.contains("av-reveal")) return;
    /* Avoid nesting reveals too deep — skip if parent already revealed and is widget */
    if (el.classList.contains("elementor-widget") && el.closest(".av-card, .av-hero-copy, .av-hero-side")) {
      return;
    }
    el.classList.add("av-reveal");
    if (i % 3 === 1) el.classList.add("av-reveal--delay-1");
    if (i % 3 === 2) el.classList.add("av-reveal--delay-2");
    revealList.push(el);
  });

  function showReveal(el) {
    el.classList.add("is-in");
  }

  if (reduce) {
    revealList.forEach(showReveal);
    return;
  }

  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          var parent = entry.target.parentElement;
          if (parent) {
            var siblings = Array.prototype.slice
              .call(parent.children)
              .filter(function (n) {
                return n.classList && n.classList.contains("av-reveal");
              });
            var group = siblings.length > 1 ? siblings : [entry.target];
            group.forEach(function (node, idx) {
              setTimeout(function () {
                showReveal(node);
              }, idx * 90);
            });
          } else {
            showReveal(entry.target);
          }
          io.unobserve(entry.target);
        });
      },
      { threshold: 0.1, rootMargin: "0px 0px -6% 0px" }
    );
    revealList.forEach(function (el) {
      var rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight * 0.88) showReveal(el);
      else io.observe(el);
    });
    /* Safety: never leave content hidden */
    setTimeout(function () {
      revealList.forEach(showReveal);
    }, 2800);
  } else {
    revealList.forEach(showReveal);
  }

  /* Tutor: sustituye placeholder SVG por imagen stock AV */
  var courseFallback = cfg.courseFallbackUrl;
  if (courseFallback && document.body.classList.contains("single-courses")) {
    document
      .querySelectorAll(
        '.tutor-course-thumbnail img[src*="placeholder"], .tutor-card-image img[src*="placeholder"]'
      )
      .forEach(function (img) {
        img.src = courseFallback;
        img.removeAttribute("srcset");
        img.style.opacity = "1";
      });
  }
})();
