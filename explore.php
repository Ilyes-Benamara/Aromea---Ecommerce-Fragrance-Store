<?php
require_once 'config.php';
$page_title = 'Explore';
include 'header.php';

$search = sanitize($_GET['q'] ?? '');
$fragrances = [];

if (strlen($search) > 0) {
    $stmt = $conn->prepare("SELECT * FROM fragrances WHERE name LIKE CONCAT('%', ?, '%') OR brand LIKE CONCAT('%', ?, '%') ORDER BY rating DESC");
    $stmt->bind_param('ss', $search, $search);
} else {
    $stmt = $conn->prepare("SELECT * FROM fragrances ORDER BY rating DESC LIMIT 20");
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $fragrances[] = $row;
}
?>

<main>
    <div class="explore-hero">
        <img src="<?php echo BASE_URL; ?>/images/Explore-Page hero.png" alt="Explore Fragrances">
        <h2>Let the scent guide you</h2>
    </div>

    <div class="explore-search-bar">
        <form method="GET" action="<?php echo BASE_URL; ?>/explore.php" class="search-bar">
            <button id="filter-button" type="button" onclick="openSidebar()"><i class="fa-solid fa-sliders"></i></button>
            <input type="text" name="q" placeholder="Search fragrances, brands..." value="<?php echo htmlspecialchars($search); ?>">
            <button id="search-button" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <section class="explore-page">
        <?php if (count($fragrances) === 0): ?>
            <div id="no-results"><h3>No fragrances found</h3><p>Try adjusting your filters or search query.</p></div>
        <?php else: ?>
            <?php foreach ($fragrances as $f): ?>
                <div class="explore-offer" data-id="<?php echo $f['id']; ?>">
                    <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($f['image_url']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>">
                    <h3><b><?php echo htmlspecialchars($f['brand']); ?></b><?php echo ' - ' . htmlspecialchars($f['name']); ?>&mdash;<?php
  $gender_class = $f['gender'] === 'him' ? 'for-him' :
                 ($f['gender'] === 'her' ? 'for-her' : 'for-him-her');
  $gender_label = $f['gender'] === 'him' ? 'For Him' :
                 ($f['gender'] === 'her' ? 'For Her' : 'Unisex');
?>
<span class="<?php echo $gender_class; ?>"><?php echo $gender_label; ?></span>
                </h3>
                    <p><b>Price:</b> <span class="price"><?php echo number_format($f['price'], 2); ?> DZD</span></p>
                    <div class="explore-offer-buttons">
                        <button class="detailsBtn" onclick="window.location.href='<?php echo BASE_URL; ?>/fragrance-details.php?id=<?php echo $f['id']; ?>'">Details <i class="fa-regular fa-square-plus"></i></button>
                        <button class="addToCartBtn" type="button" onclick="addToCart(<?php echo $f['id']; ?>)">Add to cart <i class="fa-solid fa-cart-shopping"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>

<?php include 'footer.php'; ?>
