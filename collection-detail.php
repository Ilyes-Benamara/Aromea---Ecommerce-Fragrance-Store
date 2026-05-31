<?php
require_once 'config.php';
$page_title = 'Collection Details';
include 'header.php';

$collection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$collection = null;
$fragrances = [];

if ($collection_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM collections WHERE id = ?");
    $stmt->bind_param('i', $collection_id);
    $stmt->execute();
    $collection = $stmt->get_result()->fetch_assoc();

    if ($collection) {
        $stmt2 = $conn->prepare("SELECT * FROM fragrances WHERE collection_id = ? ORDER BY name ASC");
        $stmt2->bind_param('i', $collection_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        while ($row = $result->fetch_assoc()) {
            /* fetch notes for each fragrance */
            $nstmt = $conn->prepare("
                SELECT n.name, n.image_url, fn.tier
                FROM fragrance_notes fn
                JOIN notes n ON fn.note_id = n.id
                WHERE fn.fragrance_id = ?
                ORDER BY n.name ASC
            ");
            $nstmt->bind_param('i', $row['id']);
            $nstmt->execute();
            $nres = $nstmt->get_result();
            $row['_top'] = $row['_heart'] = $row['_base'] = [];
            while ($nr = $nres->fetch_assoc()) {
                $note_entry = ['name' => $nr['name'], 'image_url' => $nr['image_url'] ?? ''];
                if ($nr['tier'] === 'top')   $row['_top'][]   = $note_entry;
                if ($nr['tier'] === 'heart') $row['_heart'][] = $note_entry;
                if ($nr['tier'] === 'base')  $row['_base'][]  = $note_entry;
            }
            /* fetch accords */
            $astmt = $conn->prepare("
                SELECT a.name, a.color, fa.strength
                FROM fragrance_accords fa
                JOIN accords a ON fa.accord_id = a.id
                WHERE fa.fragrance_id = ?
                ORDER BY fa.strength DESC
            ");
            $astmt->bind_param('i', $row['id']);
            $astmt->execute();
            $ares = $astmt->get_result();
            $row['_accords'] = [];
            while ($ar = $ares->fetch_assoc()) { $row['_accords'][] = $ar; }

            $fragrances[] = $row;
        }
    }
}

/* Pricing */
$individual_total = array_sum(array_column($fragrances, 'price'));
$sale_price       = (isset($collection['sale_price']) && $collection['sale_price'] !== null)
                    ? floatval($collection['sale_price']) : null;
$has_discount     = $sale_price !== null && $individual_total > 0 && $sale_price < $individual_total;
?>

<main class="cd-main details-main">

<?php if (!$collection): ?>
  <div style="padding:4rem 1rem;text-align:center;">
    <h2>Collection not found</h2>
    <p style="margin-top:1rem;">
      <a href="<?php echo BASE_URL; ?>/collection.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Collections
      </a>
    </p>
  </div>
<?php else: ?>

  <!-- Hero -->
  <div class="cd-hero">
    <a href="<?php echo BASE_URL; ?>/collection.php" class="back-link">
      <i class="fa-solid fa-arrow-left"></i> Back to Collections
    </a>
    <div class="cd-hero-inner">
      <img class="cd-hero-img"
           src="<?php echo BASE_URL . '/' . htmlspecialchars($collection['image_url'] ?? 'images/logo-image.png'); ?>"
           alt="<?php echo htmlspecialchars($collection['name']); ?>">
      <div class="cd-hero-text">
        <span class="cd-label" style="font-family:Montserrat;font-size:0.7rem;font-weight:700;
              text-transform:uppercase;letter-spacing:0.12em;color:var(--text-muted);">Collection</span>
        <h1 class="cd-title" style="font-size:clamp(1.2rem,5vw,2rem);text-transform:uppercase;line-height:1.2;">
          <?php echo htmlspecialchars($collection['name']); ?>
        </h1>
        <?php if (!empty($collection['description'])): ?>
          <p class="cd-subtitle" style="font-family:Montserrat;font-size:0.92rem;color:var(--text-muted);line-height:1.6;max-width:480px;">
            <?php echo htmlspecialchars($collection['description']); ?>
          </p>
        <?php endif; ?>
        <div class="cd-meta-row">
          <span class="cd-meta-chip"><?php echo count($fragrances); ?> fragrance<?php echo count($fragrances) !== 1 ? 's' : ''; ?></span>
        </div>

        <?php if ($has_discount): ?>
        <div style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;margin-top:0.25rem;">
          <span style="font-family:Montserrat;font-size:0.82rem;color:var(--text-muted);text-decoration:line-through;font-weight:600;">
            Individual total: <?php echo number_format($individual_total, 2); ?> DZD
          </span>
          <span style="font-family:Montserrat;font-size:1.5rem;font-weight:800;color:var(--green);">
            Pack price: <?php echo number_format($sale_price, 2); ?> DZD
          </span>
          <?php $savings = $individual_total - $sale_price; ?>
          <span style="font-family:Montserrat;font-size:0.75rem;font-weight:700;background:#e8f9ee;
                       color:#276749;border-radius:30px;padding:0.2em 0.75em;">
            You save <?php echo number_format($savings, 2); ?> DZD
          </span>
        </div>
        <?php elseif ($sale_price !== null): ?>
        <div style="font-family:Montserrat;font-size:1.5rem;font-weight:800;color:var(--green);text-align:center;margin-top:0.25rem;">
          <?php echo number_format($sale_price, 2); ?> DZD
        </div>
        <?php elseif ($individual_total > 0): ?>
        <div style="font-family:Montserrat;font-size:1rem;font-weight:600;color:var(--text-muted);text-align:center;margin-top:0.25rem;">
          Total: <?php echo number_format($individual_total, 2); ?> DZD
        </div>
        <?php endif; ?>
        <button class="cd-add-all-btn" id="cd-add-all">
          <i class="fa-solid fa-cart-shopping"></i> Add All to Cart
        </button>
      </div>
    </div>
  </div>

  <!-- Section title -->
  <div class="cd-section-title">
    <h2>Fragrances in This Collection</h2>
    <p class="cd-section-hint"><i class="fa-solid fa-arrow-right"></i> Tap a card to see details</p>
  </div>

  <?php if (empty($fragrances)): ?>
    <p style="color:var(--text-muted);font-style:italic;padding:1rem 0;">No fragrances in this collection yet.</p>
  <?php else: ?>

  <!-- Horizontal scroll strip -->
  <div class="cd-scroll-wrapper">
    <div class="cd-scroll-track" id="cd-scroll-track">
      <?php foreach ($fragrances as $i => $f): ?>
      <div class="cd-frag-card<?php echo $i === 0 ? ' active' : ''; ?>"
           data-id="<?php echo $i; ?>"
           onclick="cdSelectCard(this, <?php echo $i; ?>)">
        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($f['image_url'] ?? ''); ?>"
             alt="<?php echo htmlspecialchars($f['name']); ?>"
             onerror="this.style.display='none'">
        <span class="cd-card-brand"><?php echo htmlspecialchars($f['brand'] ?? ''); ?></span>
        <span class="cd-card-name"><?php echo htmlspecialchars($f['name']); ?></span>
        <span class="cd-card-price"><?php echo number_format($f['price'], 2); ?> DZD</span>
        <button class="cd-card-add-btn"
                onclick="event.stopPropagation();addToCart(<?php echo $f['id']; ?>)">
          <i class="fa-solid fa-cart-shopping"></i> Add
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Detail panel (updated by JS) -->
  <section class="cd-detail-panel visible" id="cd-detail-panel">
    <?php
    /* Render all panels, show first, hide rest via JS */
    foreach ($fragrances as $i => $f):
      $s_summer = !empty($f['season_summer']); $s_spring = !empty($f['season_spring']);
      $s_fall   = !empty($f['season_fall']);   $s_winter = !empty($f['season_winter']);
      $t_day    = !empty($f['time_day']);       $t_night  = !empty($f['time_night']);
    ?>
    <div class="cd-panel-item" id="cd-panel-<?php echo $i; ?>"
         style="<?php echo $i !== 0 ? 'display:none;' : ''; ?>">

      <div class="cd-dp-hero">
        <img class="cd-dp-hero-img"
             src="<?php echo BASE_URL . '/' . htmlspecialchars($f['image_url'] ?? ''); ?>"
             alt="<?php echo htmlspecialchars($f['name']); ?>">
        <div class="cd-dp-hero-text">
          <h2 class="cd-dp-title"><?php echo htmlspecialchars($f['name']); ?></h2>
          <div class="cd-dp-price"><?php echo number_format($f['price'], 2); ?> DZD</div>

          <div class="cd-dp-meta-grid">
            <?php if ($f['brand']): ?>
            <div class="details-meta-card">
              <div class="details-meta-label">Brand</div>
              <div class="details-meta-value"><?php echo htmlspecialchars($f['brand']); ?></div>
            </div>
            <?php endif; ?>
            <div class="details-meta-card">
              <div class="details-meta-label">Gender</div>
              <div class="details-meta-value"><?php echo ucfirst($f['gender'] ?? 'Unisex'); ?></div>
            </div>
            <div class="details-meta-card">
              <div class="details-meta-label">Stock</div>
              <div class="details-meta-value"><?php echo intval($f['stock'] ?? 0); ?></div>
            </div>
          </div>

          <div style="display:flex;gap:0.75rem;flex-wrap:wrap;justify-content:center;margin-top:0.5rem;">
            <button class="cd-add-all-btn" style="margin-top:0;"
                    onclick="addToCart(<?php echo $f['id']; ?>)">
              <i class="fa-solid fa-cart-shopping"></i> Add to Cart
            </button>
            <a href="<?php echo BASE_URL; ?>/fragrance-details.php?id=<?php echo $f['id']; ?>"
               style="display:inline-flex;align-items:center;gap:0.5em;
                      font-family:Montserrat;font-size:0.88rem;font-weight:700;
                      text-transform:uppercase;letter-spacing:0.05em;
                      border:1.5px solid var(--border-focus);border-radius:var(--radius-md);
                      padding:0.8em 1.5em;color:var(--text-main);transition:all 0.2s;">
              <i class="fa-solid fa-circle-info"></i> Full Details
            </a>
          </div>
        </div>
      </div>

      <!-- Notes pyramid mini -->
      <?php if (!empty($f['_top']) || !empty($f['_heart']) || !empty($f['_base'])): ?>
      <div class="notes-pyramid-section" style="margin-top:1.5rem;">
        <h3 class="notes-pyramid-title" style="font-size:0.9rem;">Fragrance Pyramid</h3>
        <div class="notes-pyramid">
          <?php
          $tiers = [
            ['top','Top Notes','tier-dot--top', $f['_top']],
            ['heart','Heart Notes','tier-dot--heart', $f['_heart']],
            ['base','Base Notes','tier-dot--base', $f['_base']],
          ];
          foreach ($tiers as [$tk, $tl, $td, $tn]):
          ?>
          <div class="notes-tier notes-tier--<?php echo $tk; ?>">
            <div class="notes-tier-label">
              <span class="tier-dot <?php echo $td; ?>"></span>
              <span><?php echo $tl; ?></span>
            </div>
            <div class="notes-tier-items">
              <?php if (empty($tn)): ?>
                <span style="color:var(--text-muted);font-size:0.8rem;font-style:italic;">None listed</span>
              <?php else: foreach ($tn as $note): ?>
                <div class="note-item">
                  <div class="note-img-wrap<?php echo empty($note['image_url']) ? ' no-img' : ''; ?>">
                    <img src="<?php echo $note['image_url'] ? BASE_URL . '/' . htmlspecialchars($note['image_url']) : ''; ?>"
                         alt="<?php echo htmlspecialchars($note['name']); ?>"
                         onerror="this.parentElement.classList.add('no-img');this.style.display='none'">
                  </div>
                  <span class="note-name"><?php echo htmlspecialchars($note['name']); ?></span>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Accords -->
      <?php if (!empty($f['_accords'])): ?>
      <div class="fragrance-accords-list" style="margin-top:1.5rem;">
        <h3 style="font-size:0.9rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.75rem;">Main Accords</h3>
        <div class="accords-bars">
          <?php foreach ($f['_accords'] as $acc): ?>
          <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
            <span style="font-family:Montserrat;font-size:0.78rem;font-weight:600;text-transform:uppercase;
                         letter-spacing:0.06em;min-width:120px;color:var(--text-main);">
              <?php echo htmlspecialchars($acc['name']); ?>
            </span>
            <div style="flex:1;height:7px;background:var(--border-light);border-radius:4px;overflow:hidden;">
              <div style="height:100%;width:<?php echo intval($acc['strength']); ?>%;
                           background:<?php echo htmlspecialchars($acc['color'] ?? '#c4a882'); ?>;
                           border-radius:4px;"></div>
            </div>
            <span style="font-size:0.7rem;color:var(--text-muted);width:30px;text-align:right;">
              <?php echo intval($acc['strength']); ?>%
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($f['description'])): ?>
      <p style="font-family:Montserrat;font-size:0.9rem;line-height:1.75;color:var(--text-muted);margin-top:1.25rem;">
        <?php echo nl2br(htmlspecialchars($f['description'])); ?>
      </p>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  </section>

  <script>
  function cdSelectCard(el, idx) {
    document.querySelectorAll('.cd-frag-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.cd-panel-item').forEach(p => p.style.display = 'none');
    const panel = document.getElementById('cd-panel-' + idx);
    if (panel) panel.style.display = '';
    panel && panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  // Wire Add All to Cart
  const _collFragIds = <?php echo json_encode(array_column($fragrances, 'id')); ?>;
  document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('cd-add-all');
    if (btn) btn.onclick = function() {
      _collFragIds.forEach(id => { if (typeof addToCart === 'function') addToCart(id); });
    };
  });
  </script>

  <?php endif; ?>
<?php endif; ?>

</main>

<?php include 'footer.php'; ?>
