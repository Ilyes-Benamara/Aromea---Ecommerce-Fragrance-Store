<?php
// fragrance-data.php — outputs a <script> block with DB fragrances for JS cart/filter
// Include AFTER config.php is loaded. Called via include in header or footer.
if (!isset($conn)) return;

$_frag_data = [];
$_fres = $conn->query("
    SELECT f.id, f.name, f.brand, f.price, f.image_url, f.gender,
           f.season_summer, f.season_spring, f.season_fall, f.season_winter,
           f.time_day, f.time_night, f.classification, f.fragrance_type,
           f.longevity, f.sillage, f.rating, f.stock
    FROM fragrances f
    ORDER BY f.name ASC
");
if ($_fres) {
    while ($_fr = $_fres->fetch_assoc()) {
        $seasons = [];
        if ($_fr['season_summer']) $seasons[] = 'summer';
        if ($_fr['season_spring']) $seasons[] = 'spring';
        if ($_fr['season_fall'])   $seasons[] = 'fall';
        if ($_fr['season_winter']) $seasons[] = 'winter';
        $tod = '';
        if ($_fr['time_day'] && $_fr['time_night']) $tod = 'day/night';
        elseif ($_fr['time_day'])   $tod = 'day';
        elseif ($_fr['time_night']) $tod = 'night';

        // fetch notes
        $top = []; $heart = []; $base = [];
        $ns = $conn->prepare("SELECT n.name, fn.tier FROM fragrance_notes fn JOIN notes n ON fn.note_id=n.id WHERE fn.fragrance_id=?");
        $ns->bind_param('i', $_fr['id']); $ns->execute();
        $nr = $ns->get_result();
        while ($nn = $nr->fetch_assoc()) {
            if ($nn['tier']==='top')   $top[]   = $nn['name'];
            if ($nn['tier']==='heart') $heart[] = $nn['name'];
            if ($nn['tier']==='base')  $base[]  = $nn['name'];
        }

        $_frag_data[] = [
            'id'             => (int)$_fr['id'],
            'name'           => $_fr['name'],
            'brand'          => $_fr['brand'] ?? '',
            'price'          => (float)$_fr['price'],
            'image'          => $_fr['image_url'] ?? '',
            'gender'         => $_fr['gender'] ?? 'unisex',
            'seasons'        => $seasons,
            'timeOfDay'      => $tod,
            'classification' => $_fr['classification'] ?? '',
            'fragranceType'  => $_fr['fragrance_type'] ?? '',
            'topNotes'       => $top,
            'heartNotes'     => $heart,
            'baseNotes'      => $base,
        ];
    }
}

// Current PHP user for JS auth
$_js_user = null;
if (isLoggedIn()) {
    $_u = getCurrentUser($conn);
    if ($_u) {
        $_js_user = [
            'name'     => $_u['fullname'] ?? $_u['username'],
            'email'    => $_u['email'],
            'initials' => strtoupper(substr($_u['fullname'] ?? $_u['username'], 0, 2)),
        ];
    }
}
?>
<script>
// DB-sourced fragrance data — overrides the static FRAGRANCES array
window.DB_FRAGRANCES = <?php echo json_encode($_frag_data, JSON_UNESCAPED_UNICODE); ?>;
// PHP session user — lets JS skip re-auth if already logged in via PHP
window.PHP_USER = <?php echo json_encode($_js_user); ?>;
</script>
