<?php
require_once 'config.php';
$page_title = 'Fragrance Details';
include 'header.php';

$fragrance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fragrance = null;

if ($fragrance_id > 0) {
    $stmt = $conn->prepare("
        SELECT f.*, c.name AS collection_name
        FROM fragrances f
        LEFT JOIN collections c ON f.collection_id = c.id
        WHERE f.id = ?
    ");
    $stmt->bind_param('i', $fragrance_id);
    $stmt->execute();
    $fragrance = $stmt->get_result()->fetch_assoc();
}

if (!$fragrance) {
    echo '<main><div style="padding:4rem 2rem;text-align:center;">
            <h2>Fragrance not found</h2>
            <p style="margin-top:1rem;">
                <a href="' . BASE_URL . '/explore.php" style="color:var(--text-main);text-decoration:underline;">
                    &larr; Back to Explore
                </a>
            </p>
          </div></main>';
    include 'footer.php';
    exit;
}

/* ── Notes from fragrance_notes JOIN table ── */
$top_notes = $middle_notes = $base_notes = [];
$nstmt = $conn->prepare("
    SELECT n.name, n.image_url, fn.tier
    FROM fragrance_notes fn
    JOIN notes n ON fn.note_id = n.id
    WHERE fn.fragrance_id = ?
    ORDER BY n.name ASC
");
$nstmt->bind_param('i', $fragrance_id);
$nstmt->execute();
$nres = $nstmt->get_result();
while ($row = $nres->fetch_assoc()) {
    if ($row['tier'] === 'top')   $top_notes[]    = $row;
    if ($row['tier'] === 'heart') $middle_notes[] = $row;
    if ($row['tier'] === 'base')  $base_notes[]   = $row;
}

/* ── Accords ── */
$accords = [];
$astmt = $conn->prepare("
    SELECT a.name, a.color, fa.strength
    FROM fragrance_accords fa
    JOIN accords a ON fa.accord_id = a.id
    WHERE fa.fragrance_id = ?
    ORDER BY fa.strength DESC
");
$astmt->bind_param('i', $fragrance_id);
$astmt->execute();
$ares = $astmt->get_result();
while ($row = $ares->fetch_assoc()) { $accords[] = $row; }

/* ── Season / time ── */
$seasons = [];
if (!empty($fragrance['season_summer'])) $seasons[] = ['Summer', 'images/summer logo.png'];
if (!empty($fragrance['season_spring'])) $seasons[] = ['Spring', 'images/spring logo.png'];
if (!empty($fragrance['season_fall']))   $seasons[] = ['Fall',   'images/fall logo.png'];
if (!empty($fragrance['season_winter'])) $seasons[] = ['Winter', 'images/winter logo.png'];
if (empty($seasons))                     $seasons[] = ['All Seasons', ''];

$times = [];
if (!empty($fragrance['time_day']))   $times[] = ['Day',   'images/morning icon.png'];
if (!empty($fragrance['time_night'])) $times[] = ['Night', 'images/night icon.png'];
if (empty($times))                    $times[] = ['Any Time', ''];

$season_label = implode(' · ', array_column($seasons, 0));
$time_label   = implode(' · ', array_column($times,   0));

$classification = $fragrance['classification'] ?? ($fragrance['concentration'] ?? '');
?>

<main class="details-main">

  <!-- Search bar -->
  <div class="explore-search-bar">
    <div class="search-bar">
      <button id="filter-button"><i class="fa-solid fa-sliders"></i></button>
      <input type="text" placeholder="Search fragrances..."
             onkeydown="if(event.key==='Enter'){window.location='<?php echo BASE_URL; ?>/explore.php?q='+encodeURIComponent(this.value);}">
      <button id="search-button" type="button"
              onclick="window.location='<?php echo BASE_URL; ?>/explore.php?q='+encodeURIComponent(document.querySelector('.explore-search-bar input').value)">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
  </div>

  <!-- Hero -->
  <div class="details-hero">
    <div class="details-hero-text">
      <a href="<?php echo BASE_URL; ?>/explore.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Explore
      </a>
      <h1 class="fragrance-title-name"><?php echo htmlspecialchars($fragrance['name']); ?>
  <?php
    $g = $fragrance['gender'] ?? '';
    $gender_class = $g === 'him' ? 'for-him' : ($g === 'her' ? 'for-her' : 'for-him-her');
    $gender_label = $g === 'him' ? 'For Him' : ($g === 'her' ? 'For Her' : 'Unisex');
  ?>
  <span class="<?php echo $gender_class; ?>"><?php echo $gender_label; ?></span></h1>
      <div class="details-price-tag"><?php echo number_format($fragrance['price'], 2); ?> DZD</div>

      <div class="details-meta-grid">
        <div class="details-meta-card">
          <div class="details-meta-label">Season</div>
          <div class="details-meta-value"><?php echo htmlspecialchars($season_label); ?></div>
        </div>
        <div class="details-meta-card">
          <div class="details-meta-label">Time of Day</div>
          <div class="details-meta-value"><?php echo htmlspecialchars($time_label); ?></div>
        </div>
        <div class="details-meta-card">
          <div class="details-meta-label">Classification</div>
          <div class="details-meta-value"><?php echo htmlspecialchars($classification ?: 'N/A'); ?></div>
        </div>
      </div>

      <button class="addToCartBtn" id="purchaseAddToCart">
        Add to cart &nbsp;<i class="fa-solid fa-cart-shopping"></i>
      </button>
    </div>

    <div class="details-hero-img">
      <img id="details-img"
           src="<?php echo BASE_URL . '/' . htmlspecialchars($fragrance['image_url'] ?? ''); ?>"
           alt="<?php echo htmlspecialchars($fragrance['name']); ?>">
    </div>
  </div>

  <!-- Notes Pyramid -->
  <section class="notes-pyramid-section">
    <h2 class="notes-pyramid-title">Fragrance Pyramid</h2>
    <div class="notes-pyramid">

      <?php
      $tiers = [
        ['top',   'Top Notes',   'First impression · 0–30 min',      'tier-dot--top',   $top_notes],
        ['heart', 'Heart Notes', 'The character · 30 min–4 hrs',     'tier-dot--heart', $middle_notes],
        ['base',  'Base Notes',  'The lasting memory · 4+ hrs',      'tier-dot--base',  $base_notes],
      ];
      foreach ($tiers as [$key, $label, $sublabel, $dotClass, $notes]):
      ?>
      <div class="notes-tier notes-tier--<?php echo $key; ?>">
        <div class="notes-tier-label">
          <span class="tier-dot <?php echo $dotClass; ?>"></span>
          <span><?php echo $label; ?></span>
          <span class="tier-sublabel"><?php echo $sublabel; ?></span>
        </div>
        <div class="notes-tier-items">
          <?php if (empty($notes)): ?>
            <span style="color:var(--text-muted);font-size:0.82rem;font-style:italic;">None listed</span>
          <?php else: foreach ($notes as $note): ?>
            <div class="note-item">
              <div class="note-img-wrap<?php echo empty($note['image_url']) ? ' no-img' : ''; ?>">
                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($note['image_url'] ?? ''); ?>"
                     alt="<?php echo htmlspecialchars($note['name']); ?>">
              </div>
              <span class="note-name"><?php echo htmlspecialchars($note['name']); ?></span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </section>

  <!-- Season/Time + Accords + Description -->
  <section class="fragrance-attributes">

    <div class="fragrance-time-season">
      <h2>Season &amp; Time</h2>
      <div class="season-time-illustration">
        <?php foreach ($seasons as [$slabel, $sicon]): ?>
          <div style="display:flex;flex-direction:column;align-items:center;gap:0.4rem;">
            <?php if ($sicon): ?>
              <img src="<?php echo BASE_URL . '/' . htmlspecialchars($sicon); ?>"
                   alt="<?php echo htmlspecialchars($slabel); ?>"
                   class="notes-summer-logo">
            <?php endif; ?>
            <span style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);">
              <?php echo htmlspecialchars($slabel); ?>
            </span>
          </div>
        <?php endforeach; ?>
        <?php foreach ($times as [$tlabel, $ticon]): ?>
          <div style="display:flex;flex-direction:column;align-items:center;gap:0.4rem;">
            <?php if ($ticon): ?>
              <img src="<?php echo BASE_URL . '/' . htmlspecialchars($ticon); ?>"
                   alt="<?php echo htmlspecialchars($tlabel); ?>"
                   class="notes-morning-icon">
            <?php endif; ?>
            <span style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);">
              <?php echo htmlspecialchars($tlabel); ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fragrance-accords-list">
      <h2>Main Accords</h2>
      <div class="accords-bars">
        <?php if (empty($accords)): ?>
          <p style="color:var(--text-muted);font-size:0.85rem;font-style:italic;">No accords listed yet.</p>
        <?php else: ?>
          <?php foreach ($accords as $acc): ?>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.6rem;">
              <span style="font-family:Montserrat;font-size:0.78rem;font-weight:600;text-transform:uppercase;
                           letter-spacing:0.06em;min-width:130px;color:var(--text-main);">
                <?php echo htmlspecialchars($acc['name']); ?>
              </span>
              <div style="flex:1;height:8px;background:var(--border-light);border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:<?php echo intval($acc['strength']); ?>%;
                             background:<?php echo htmlspecialchars($acc['color'] ?? '#c4a882'); ?>;
                             border-radius:4px;"></div>
              </div>
              <span style="font-size:0.72rem;color:var(--text-muted);width:32px;text-align:right;">
                <?php echo intval($acc['strength']); ?>%
              </span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if (!empty($fragrance['description'])): ?>
        <div class="fragrance-description" style="margin-top:1.5rem;">
          <p style="font-family:Montserrat;font-size:0.92rem;line-height:1.75;color:var(--text-muted);">
            <?php echo nl2br(htmlspecialchars($fragrance['description'])); ?>
          </p>
        </div>
      <?php endif; ?>
    </div>

  </section>

</main>

<script>
// Register this DB fragrance so addToCart() can find it
(function(){
  const fId = <?php echo $fragrance_id; ?>;
  if (typeof FRAGRANCES !== "undefined" && !FRAGRANCES.find(f=>f.id===fId)) {
    FRAGRANCES.push({id:fId, name:<?php echo json_encode($fragrance["name"]); ?>, brand:<?php echo json_encode($fragrance["brand"] ?? ""); ?>, price:<?php echo floatval($fragrance["price"]); ?>, image:<?php echo json_encode($fragrance["image_url"] ?? ""); ?>});
  }
  const btn = document.getElementById("purchaseAddToCart");
  if (btn) btn.onclick = function(){ addToCart(fId); };
})();
</script>
<?php include 'footer.php'; ?>
