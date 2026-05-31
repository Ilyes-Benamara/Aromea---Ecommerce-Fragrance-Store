<?php
require_once 'config.php';
$page_title = 'Collection';
include 'header.php';

/* ── Fetch collections grouped by category ── */
$grouped = ['designer' => [], 'niche' => [], 'arabic' => [], 'other' => []];
$res = $conn->query("SELECT * FROM collections ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $cat = $row['category'] ?? 'other';
        if (!isset($grouped[$cat])) $cat = 'other';
        $grouped[$cat][] = $row;
    }
}

$section_labels = [
    'designer' => 'Designers Collection',
    'niche'    => 'Niche Collection',
    'arabic'   => 'Middle Eastern Collection',
    'other'    => 'Other Collections',
];

$gender_class = ['him' => 'for-him', 'her' => 'for-her', 'unisex' => 'for-him-her'];
$gender_label = ['him' => 'For Him', 'her' => 'For Her', 'unisex' => 'Unisex'];
?>

<main>

<?php
$any = false;
foreach ($grouped as $cat => $cols):
    if (empty($cols)) continue;
    $any = true;
    $section_class = $cat === 'designer' ? 'designer-collection'
                   : ($cat === 'niche'   ? 'niche-collection'
                   : ($cat === 'arabic'  ? 'arabic-collection'
                   : 'designer-collection'));
?>
  <section class="<?php echo $section_class; ?>">
    <h2><?php echo htmlspecialchars($section_labels[$cat]); ?></h2>
    <div class="collection-list">
      <?php foreach ($cols as $col):
        /* gender: try to infer from first fragrance in this collection */
        $gstmt = $conn->prepare("SELECT gender FROM fragrances WHERE collection_id = ? LIMIT 1");
        $gstmt->bind_param('i', $col['id']);
        $gstmt->execute();
        $grows = $gstmt->get_result()->fetch_assoc();
        $g = $grows['gender'] ?? 'unisex';
        $gc = $gender_class[$g] ?? 'for-him-her';
        $gl = $gender_label[$g] ?? 'Unisex';
      ?>
      <div class="collection-offer">
        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($col['image_url'] ?? 'images/logo-image.png'); ?>"
             alt="<?php echo htmlspecialchars($col['name']); ?>">
        <h3>
          <?php echo htmlspecialchars($col['name']); ?>
          &mdash; <span class="<?php echo $gc; ?>"><?php echo $gl; ?></span>
        </h3>
        <p><?php echo htmlspecialchars($col['description'] ?? ''); ?></p>
        <?php if (isset($col['sale_price']) && $col['sale_price'] !== null): ?>
          <p style="font-family:Montserrat;font-weight:700;color:var(--text-main);margin:0.4rem 0;">
            <?php echo number_format((float)$col['sale_price'], 2); ?> DZD
          </p>
        <?php endif; ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/collection-detail.php?id=<?php echo $col['id']; ?>'">
          Buy now
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php if (!$any): ?>
  <div style="padding:4rem 1rem;text-align:center;color:var(--text-muted);">
    <p>No collections yet. Add some from the <a href="<?php echo BASE_URL; ?>/admin/collections.php"
       style="color:var(--text-main);text-decoration:underline;">admin panel</a>.</p>
  </div>
<?php endif; ?>

</main>

<?php include 'footer.php'; ?>
