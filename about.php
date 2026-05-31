<?php
require_once 'config.php';
$page_title = 'About';
include 'header.php';
?>

<main class="about-main">

  <!-- ── HERO ── -->
  <section class="about-hero">
    <div class="about-hero-inner">
      <span class="about-eyebrow">Our Story</span>
      <h1 class="about-hero-title">Smell like<br>you mean it</h1>
      <p class="about-hero-sub">An Algerian fragrance boutique bridging the gap between world-class perfumery and the local connoisseur. We believe everyone deserves to smell extraordinary.</p>
    </div>
    <div class="about-hero-line"></div>
  </section>

  <!-- ── MANIFESTO ── -->
  <section class="about-manifesto">
    <div class="about-manifesto-inner">
      <p class="about-manifesto-text">
        Aromea was built on one conviction — that fragrance is not a luxury, it's a language. Every bottle tells a story. Every accord leaves a memory. We bring that language to Algeria.
      </p>
    </div>
  </section>

  <!-- ── THREE PILLARS ── -->
  <section class="about-pillars">
    <div class="about-pillar">
      <div class="about-pillar-num">01</div>
      <h2 class="about-pillar-title">The Story</h2>
      <p class="about-pillar-body">Aromea was born out of a passion for perfumery and a frustration with limited access to premium scents in Algeria. We set out to build a curated destination where fragrance lovers can explore Designer, Niche, and Middle Eastern perfumery — with honest guidance and fair prices.</p>
    </div>
    <div class="about-pillar-divider"></div>
    <div class="about-pillar">
      <div class="about-pillar-num">02</div>
      <h2 class="about-pillar-title">The Mission</h2>
      <p class="about-pillar-body">We want to make luxury fragrance accessible, understandable, and personal. Through our AI fragrance advisor, curated collections, and detailed scent profiles, we help every customer find their signature scent — first-time buyer or seasoned collector.</p>
    </div>
    <div class="about-pillar-divider"></div>
    <div class="about-pillar">
      <div class="about-pillar-num">03</div>
      <h2 class="about-pillar-title">The Belief</h2>
      <p class="about-pillar-body">Integrity, quality, and community sit at the centre of everything we do. We partner with reputable suppliers, verify all products for authenticity, and offer honest recommendations for every budget — no upselling, no compromise.</p>
    </div>
  </section>

  <!-- ── DIVIDER ── -->
  <div class="about-full-divider"></div>

  <!-- ── CATEGORIES ── -->
  <section class="about-categories">
    <h2 class="about-section-label">What We Carry</h2>
    <div class="about-cat-grid">
      <div class="about-cat-card">
        <span class="about-cat-icon"><i class="fa-solid fa-gem"></i></span>
        <h3>Designer</h3>
        <p>Dior, Jean Paul Gaultier, Paco Rabanne, YSL, Viktor&Rolf — iconic houses made accessible.</p>
      </div>
      <div class="about-cat-card">
        <span class="about-cat-icon"><i class="fa-solid fa-flask"></i></span>
        <h3>Niche</h3>
        <p>Xerjoff, Maison Margiela, Louis Vuitton — avant-garde perfumery for the discerning nose.</p>
      </div>
      <div class="about-cat-card">
        <span class="about-cat-icon"><i class="fa-solid fa-moon"></i></span>
        <h3>Middle Eastern</h3>
        <p>Lattafa, Rasasi, French Avenue — bold oud, spice, and amber compositions rooted in tradition.</p>
      </div>
    </div>
  </section>

  <!-- ── FULL DIVIDER ── -->
  <div class="about-full-divider"></div>

  <!-- ── CTA ── -->
  <section class="about-cta-section">
    <span class="about-eyebrow">Ready?</span>
    <h2 class="about-cta-title">Find your signature scent</h2>
    <p class="about-cta-sub">Browse our collections or ask our AI for a personalised recommendation.</p>
    <div class="about-cta-btns">
      <a href="<?php echo BASE_URL; ?>/collection.php" class="about-btn-primary">View Collections</a>
      <a href="<?php echo BASE_URL; ?>/explore.php" class="about-btn-secondary">Explore All</a>
    </div>
  </section>

</main>

<?php include 'footer.php'; ?>
