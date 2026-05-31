<?php // footer.php ?>
    <footer>
        <div class="logo" style="padding:2.5rem 1.5rem 0;font-size:1.6rem;">Aromea</div>
        <div class="footer-col">
            <h4>Pages</h4>
            <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
            <a href="<?php echo BASE_URL; ?>/collection.php">Collection</a>
            <a href="<?php echo BASE_URL; ?>/explore.php">Explore</a>
            <a href="<?php echo BASE_URL; ?>/about.php">About</a>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <a href="mailto:info@aromea.com">info@aromea.com</a>
        </div>
        <div class="footer-col" id="footer-col1">
            <h4>Follow</h4>
            <div id="footer-col-i">
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
            </div>
        </div>
    </footer>

    <?php include_once __DIR__ . '/fragrance-data.php'; ?>
    <script src="<?php echo BASE_URL; ?>/aromea.js"></script>
    <script src="aromea-chatbot.js"></script>
</body>
</html>
