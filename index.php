<?php
require_once 'config.php';
$page_title = 'Home';
include 'header.php';

/* ── Collections to show on home (show_on_home=1, ordered) ── */
$home_collections = [];
$res = $conn->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM fragrances WHERE collection_id = c.id) AS frag_count,
           (SELECT SUM(price) FROM fragrances WHERE collection_id = c.id) AS individual_total
    FROM collections c
    WHERE c.show_on_home = 1
    ORDER BY c.home_order ASC, c.created_at DESC
    LIMIT 6
");
if ($res) while ($r = $res->fetch_assoc()) $home_collections[] = $r;

/* ── Fragrances to show on home (show_on_home=1, fallback to is_featured) ── */
$home_fragrances = [];
$res2 = $conn->query("
    SELECT * FROM fragrances
    WHERE show_on_home = 1
    ORDER BY created_at DESC
    LIMIT 6
");
if ($res2 && $res2->num_rows > 0) {
    while ($r = $res2->fetch_assoc()) $home_fragrances[] = $r;
} else {
    /* fallback: is_featured */
    $res3 = $conn->query("SELECT * FROM fragrances WHERE is_featured=1 ORDER BY rating DESC LIMIT 6");
    if ($res3) while ($r = $res3->fetch_assoc()) $home_fragrances[] = $r;
}
?>

<main>
    <!-- Hero -->
    <section class="hero-section">
        <img src="<?php echo BASE_URL; ?>/images/hero.png" alt="Aromea Hero">
        <div class="hero-div-box">
            <h1>Smell like you mean it</h1>
            <div class="hero-buttons">
                <div class="hero-cta-row">
                    <a href="<?php echo BASE_URL; ?>/collection.php">
                        <button type="button"><i class="fa-solid fa-store"></i> Collection</button>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/explore.php">
                        <button type="button"><i class="fa-solid fa-compass"></i> Explore</button>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Search -->
    <div class="search-bar-wrapper">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Search fragrances..."
                   onkeydown="if(event.key==='Enter'){window.location='<?php echo BASE_URL; ?>/explore.php?q='+encodeURIComponent(this.value);}">
            <button id="search-button" type="button"
                    onclick="window.location='<?php echo BASE_URL; ?>/explore.php?q='+encodeURIComponent(document.getElementById('search-input').value)">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <button id="filter-button" type="button" onclick="openSidebar()">
                <i class="fa-solid fa-sliders"></i>
            </button>
        </div>
    </div>

    <!-- Popular Collections -->
    <section class="collection-section">
        <h2>Popular Collections</h2>
        <?php if (empty($home_collections)): ?>
            <p style="text-align:center;color:var(--text-muted);padding:2rem 1rem;font-family:Montserrat;font-size:0.88rem;">
                No collections featured yet. Still work to do by the
                <a href="<?php echo BASE_URL; ?>/admin/collections.php" style="color:var(--text-main);font-weight:700;">admin</a>.
            </p>
        <?php else: ?>
        <div class="collection-container">
            <?php foreach ($home_collections as $col):
                $individual_total = floatval($col['individual_total'] ?? 0);
                $sale_price       = isset($col['sale_price']) && $col['sale_price'] !== null
                                    ? floatval($col['sale_price']) : null;
                $has_discount     = $sale_price !== null && $individual_total > 0 && $sale_price < $individual_total;
            ?>
            <div class="collection-card">
                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($col['image_url'] ?? 'images/logo-image.png'); ?>"
                     alt="<?php echo htmlspecialchars($col['name']); ?>"
                     onerror="this.src='<?php echo BASE_URL; ?>/images/logo-image.png'">
                <h3><?php echo htmlspecialchars($col['name']); ?></h3>
                <p><?php echo htmlspecialchars(substr($col['description'] ?? '', 0, 80)); ?></p>

                <?php if ($has_discount): ?>
                <div class="home-pack-price">
                    <span class="home-pack-original"><?php echo number_format($individual_total, 2); ?> DZD</span>
                    <span class="home-pack-sale"><?php echo number_format($sale_price, 2); ?> DZD</span>
                </div>
                <?php elseif ($sale_price !== null): ?>
                <div class="home-pack-price">
                    <span class="home-pack-sale"><?php echo number_format($sale_price, 2); ?> DZD</span>
                </div>
                <?php endif; ?>

                <button onclick="window.location.href='<?php echo BASE_URL; ?>/collection-detail.php?id=<?php echo $col['id']; ?>'">
                    Visit now
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Explore Our Magical Scents -->
    <section class="explore-section">
        <h2>Explore Our Magical Scents</h2>
        <?php if (empty($home_fragrances)): ?>
            <p style="text-align:center;color:var(--text-muted);padding:2rem 1rem;font-family:Montserrat;font-size:0.88rem;">
                No fragrances featured yet. Mark them in the
                <a href="<?php echo BASE_URL; ?>/admin/fragrances.php" style="color:var(--text-main);font-weight:700;">admin panel</a>.
            </p>
        <?php else: ?>
        <div class="explore-container">
            <?php foreach ($home_fragrances as $f): ?>
            <div class="explore-card">
                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($f['image_url'] ?? ''); ?>"
                     alt="<?php echo htmlspecialchars($f['name']); ?>"
                     onerror="this.style.display='none'">
                <h3><?php echo htmlspecialchars(($f['brand'] ? $f['brand'] . ' — ' : '') . $f['name']); ?></h3>
                <p><?php echo htmlspecialchars(substr($f['description'] ?? '', 0, 90)); ?></p>
                <button onclick="window.location.href='<?php echo BASE_URL; ?>/fragrance-details.php?id=<?php echo $f['id']; ?>'">
                    Check Now
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php include 'footer.php'; ?>
