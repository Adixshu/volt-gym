(() => {
  // Add pointer and keyboard emphasis to the brand mark.
  const brand = document.querySelector(".logo");
  // Toggle the mobile navigation and expose its expanded state.
  const toggle = document.querySelector(".menu-toggle");
  const menu = document.querySelector(".nav-actions");
  const links = document.querySelectorAll(".nav-links a");
  const sections = document.querySelectorAll("main section[id]");
  const reduceMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  ).matches;

  if (brand) {
    brand.addEventListener("pointerenter", () =>
      brand.classList.add("is-hovered"),
    );
    brand.addEventListener("pointerleave", () =>
      brand.classList.remove("is-hovered"),
    );
    brand.addEventListener("focusin", () => brand.classList.add("is-hovered"));
    brand.addEventListener("focusout", () =>
      brand.classList.remove("is-hovered"),
    );
  }
  toggle?.addEventListener("click", () => {
    const isOpen = menu.classList.toggle("open");
    toggle.setAttribute("aria-expanded", String(isOpen));
    toggle.querySelector("i").className = isOpen ? "bi bi-x-lg" : "bi bi-list";
  });
  // Close the mobile menu after a section link is selected.
  links.forEach((link) =>
    link.addEventListener("click", () => {
      menu.classList.remove("open");
      toggle?.setAttribute("aria-expanded", "false");
      if (toggle) toggle.querySelector("i").className = "bi bi-list";
    }),
  );

  // Persist the selected color theme so it remains consistent across visits.
  const themeToggle = document.querySelector(".theme-toggle");
  const savedTheme = localStorage.getItem("volt-theme");
  const setTheme = (isLight) => {
    document.body.classList.toggle("light-theme", isLight);
    document.documentElement.classList.toggle("light-theme", isLight);
    themeToggle?.setAttribute("aria-pressed", String(isLight));
    themeToggle?.setAttribute(
      "aria-label",
      isLight ? "Switch to dark mode" : "Switch to light mode",
    );
    if (themeToggle)
      themeToggle.querySelector("i").className = isLight
        ? "bi bi-moon-fill"
        : "bi bi-sun-fill";
    localStorage.setItem("volt-theme", isLight ? "light" : "dark");
  };
  setTheme(savedTheme === "light");
  themeToggle?.addEventListener("click", () =>
    setTheme(!document.body.classList.contains("light-theme")),
  );

  // Update the active navigation link as sections enter or leave view.
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          document
            .querySelector(`.nav-links a[href="#${entry.target.id}"]`)
            ?.classList.add("active");
          // Reveal section content unless reduced motion is requested.
          if (!reduceMotion)
            entry.target
              .querySelectorAll(".reveal")
              .forEach((item) => item.classList.add("visible"));
        } else {
          document
            .querySelector(`.nav-links a[href="#${entry.target.id}"]`)
            ?.classList.remove("active");
        }
      });
    },
    { threshold: 0.18 },
  );
  sections.forEach((section) => observer.observe(section));
  // Make reveal content visible immediately for reduced-motion visitors.
  if (reduceMotion)
    document
      .querySelectorAll(".reveal")
      .forEach((item) => item.classList.add("visible"));

  // Switch the class schedule panel to match the selected day tab.
  document.querySelectorAll(".day-tab").forEach((tab) =>
    tab.addEventListener("click", () => {
      document
        .querySelectorAll(".day-tab")
        .forEach((item) => item.classList.remove("active"));
      document
        .querySelectorAll(".class-list")
        .forEach((item) => item.classList.add("hidden"));
      tab.classList.add("active");
      document
        .querySelector(`[data-schedule="${tab.dataset.day}"]`)
        ?.classList.remove("hidden");
    }),
  );

  // Send form submissions to the PHP API and report its actual response.
  document.querySelectorAll("[data-backend-form]").forEach((form) => {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const status = form.querySelector('[role="status"]');
      const submitButton = form.querySelector('button[type="submit"]');
      const formData = new FormData(form);
      const payload = Object.fromEntries(formData.entries());
      payload.type = form.dataset.backendForm;

      if (status) status.textContent = "Sending...";
      if (submitButton) submitButton.disabled = true;

      try {
        const response = await fetch("php/api.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);

        if (status) status.textContent = result.message;
        form.reset();
      } catch (error) {
        if (status)
          status.textContent =
            error.message || "Unable to send right now. Please try again.";
      } finally {
        if (submitButton) submitButton.disabled = false;
      }
    });
  });

  const slides = document.querySelectorAll(".hero-slide");
  const previousButton = document.querySelector(".hero-prev");
  const nextButton = document.querySelector(".hero-next");
  const pagination = document.querySelector(".hero-pagination");
  const hero = document.querySelector(".hero");
  let currentSlide = 0;
  let sliderTimer;
  let slideDots = [];

  // Show one hero image and synchronize its pagination dot.
  const showSlide = (slideIndex) => {
    currentSlide = (slideIndex + slides.length) % slides.length;
    slides.forEach((slide, index) =>
      slide.classList.toggle("active", index === currentSlide),
    );
    slideDots.forEach((dot, index) => {
      const isActive = index === currentSlide;
      dot.classList.toggle("active", isActive);
      dot.setAttribute("aria-pressed", String(isActive));
    });
  };
  // Advance the hero carousel automatically.
  const startSlider = () => {
    clearInterval(sliderTimer);
    sliderTimer = setInterval(() => showSlide(currentSlide + 1), 1500);
  };

  // Create a selectable pagination dot for each hero slide.
  if (pagination && slides.length > 1) {
    slideDots = [...slides].map((slide, index) => {
      const dot = document.createElement("button");
      dot.className = "hero-dot";
      dot.type = "button";
      dot.setAttribute(
        "aria-label",
        `Show image ${index + 1} of ${slides.length}`,
      );
      dot.addEventListener("click", () => {
        showSlide(index);
        startSlider();
      });
      pagination.append(dot);
      return dot;
    });
  }

  // Initialize the first hero slide.
  if (slides.length > 1) {
    showSlide(currentSlide);
    // Move to the previous hero slide.
    previousButton?.addEventListener("click", () => {
      showSlide(currentSlide - 1);
      startSlider();
    });
    // Move to the next hero slide.
    nextButton?.addEventListener("click", () => {
      showSlide(currentSlide + 1);
      startSlider();
    });
    // Pause or resume autoplay based on pointer position.
    hero?.addEventListener("mousemove", (event) => {
      if (event.clientX >= window.innerWidth / 2) clearInterval(sliderTimer);
      else if (!event.target.closest(".hero-control")) startSlider();
    });
    // Resume autoplay when the pointer leaves the hero.
    hero?.addEventListener("mouseleave", startSlider);
    // Pause autoplay while a hero control has keyboard focus.
    hero?.addEventListener("focusin", (event) => {
      if (event.target.closest(".hero-controls")) clearInterval(sliderTimer);
    });
    // Resume autoplay after keyboard focus leaves the hero.
    hero?.addEventListener("focusout", startSlider);
    startSlider();
  }

  const reviewCarousel = document.querySelector(".review-carousel");
  const reviewStage = reviewCarousel?.querySelector(".review-stage");
  const reviewCards = [
    ...(reviewStage?.querySelectorAll(".review-card") ?? []),
  ];
  const reviewPagination = reviewCarousel?.querySelector(".review-pagination");
  const reviewPrevious = reviewCarousel?.querySelector(".review-prev");
  const reviewNext = reviewCarousel?.querySelector(".review-next");
  const reviewDots = [];
  let currentReview = 0;
  let reviewTimer;

  // Set the active review and its neighboring cards.
  const showReview = (reviewIndex) => {
    if (!reviewCards.length) return;
    currentReview = (reviewIndex + reviewCards.length) % reviewCards.length;
    reviewCards.forEach((card, index) => {
      card.classList.remove("is-previous", "is-active", "is-next");
      card.setAttribute("aria-hidden", "true");
      if (index === currentReview) {
        card.classList.add("is-active");
        card.setAttribute("aria-hidden", "false");
      } else if (
        index ===
        (currentReview - 1 + reviewCards.length) % reviewCards.length
      ) {
        card.classList.add("is-previous");
      } else if (index === (currentReview + 1) % reviewCards.length) {
        card.classList.add("is-next");
      }
    });
    reviewDots.forEach((dot, index) => {
      const isActive = index === currentReview;
      dot.classList.toggle("active", isActive);
      dot.setAttribute("aria-pressed", String(isActive));
    });
  };
  // Control review autoplay while respecting reduced-motion preferences.
  const stopReviewAutoplay = () => clearInterval(reviewTimer);
  const startReviewAutoplay = () => {
    stopReviewAutoplay();
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    reviewTimer = setInterval(() => showReview(currentReview + 1), 4000);
  };

  // Create a pagination dot for each review.
  if (reviewCarousel && reviewCards.length > 1 && reviewPagination) {
    reviewDots.push(
      ...reviewCards.map((card, index) => {
        const dot = document.createElement("button");
        dot.className = "review-dot";
        dot.type = "button";
        dot.setAttribute(
          "aria-label",
          `Show review ${index + 1} of ${reviewCards.length}`,
        );
        dot.addEventListener("click", () => {
          showReview(index);
          startReviewAutoplay();
        });
        reviewPagination.append(dot);
        return dot;
      }),
    );
    // Navigate to the previous review.
    reviewPrevious?.addEventListener("click", () => {
      showReview(currentReview - 1);
      startReviewAutoplay();
    });
    // Show the next review.
    reviewNext?.addEventListener("click", () => {
      showReview(currentReview + 1);
      startReviewAutoplay();
    });
    // Pause autoplay while the pointer is over the carousel.
    reviewCarousel.addEventListener("mouseenter", stopReviewAutoplay);
    // Resume autoplay after the pointer leaves the carousel.
    reviewCarousel.addEventListener("mouseleave", startReviewAutoplay);
    // Pause autoplay while a carousel control has keyboard focus.
    reviewCarousel.addEventListener("focusin", stopReviewAutoplay);
    // Resume autoplay after keyboard focus leaves the carousel.
    reviewCarousel.addEventListener("focusout", (event) => {
      if (!reviewCarousel.contains(event.relatedTarget)) startReviewAutoplay();
    });
    showReview(currentReview);
    startReviewAutoplay();
  }

  const galleryItems = [...document.querySelectorAll(".gallery-item")];
  const lightbox = document.querySelector(".gallery-lightbox");
  const lightboxImage = document.querySelector(".lightbox-image");
  const lightboxCount = document.querySelector(".lightbox-count");
  let galleryIndex = 0;
  const galleryImages = [
    "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1400&q=85",
    "https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=1200&q=85",
    "https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=1200&q=85",
    "https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=1400&q=85",
    "https://images.unsplash.com/photo-1546483875-ad9014c88eba?auto=format&fit=crop&w=1200&q=85",
    "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1200&q=85",
  ];
  // Update the viewer image and its position counter.
  const showGalleryImage = (index) => {
    galleryIndex = (index + galleryImages.length) % galleryImages.length;
    if (lightboxImage)
      lightboxImage.style.backgroundImage = `url("${galleryImages[galleryIndex]}")`;
    if (lightboxCount)
      lightboxCount.textContent = `0${galleryIndex + 1} / 0${galleryImages.length}`;
  };
  // Close the image viewer and restore page scrolling.
  const closeGallery = () => {
    if (lightbox) lightbox.hidden = true;
    document.body.style.overflow = "";
  };
  galleryItems.forEach((item, index) =>
    item.addEventListener("click", (event) => {
      event.preventDefault();
      showGalleryImage(index);
      if (lightbox) lightbox.hidden = false;
      document.body.style.overflow = "hidden";
    }),
  );
  // Close the image viewer with its close button.
  document
    .querySelector(".lightbox-close")
    ?.addEventListener("click", closeGallery);
  // Show the previous gallery image.
  document
    .querySelector(".lightbox-prev")
    ?.addEventListener("click", () => showGalleryImage(galleryIndex - 1));
  // Show the next gallery image.
  document
    .querySelector(".lightbox-next")
    ?.addEventListener("click", () => showGalleryImage(galleryIndex + 1));
  lightbox?.addEventListener("click", (event) => {
    // Close the viewer when the backdrop itself is clicked.
    if (event.target === lightbox) closeGallery();
  });
  // Handle keyboard shortcuts while the image viewer is open.
  document.addEventListener("keydown", (event) => {
    if (!lightbox || lightbox.hidden) return;
    // Escape closes the image viewer.
    if (event.key === "Escape") closeGallery();
    // Arrow keys move between gallery images.
    if (event.key === "ArrowLeft") showGalleryImage(galleryIndex - 1);
    if (event.key === "ArrowRight") showGalleryImage(galleryIndex + 1);
  });
})();
