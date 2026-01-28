    </main>
    
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?= t('footer_storage_rent') ?></h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php?ort=Berlin" class="text-white-50">Berlin</a></li>
                        <li><a href="index.php?ort=Hamburg" class="text-white-50">Hamburg</a></li>
                        <li><a href="index.php?ort=München" class="text-white-50">München</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5><?= t('footer_service') ?></h5>
                    <ul class="list-unstyled">
                        <li><a href="angebot_erstellen.php" class="text-white-50"><?= t('nav_create_offer') ?></a></li>
                        <li><a href="nachfrage_erstellen.php" class="text-white-50"><?= t('nav_create_request') ?></a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5><?= t('footer_about') ?></h5>
                    <p class="text-white-50"><?= t('footer_about_text') ?></p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center text-white-50">
                <p>&copy; 2026 Lagerraumbörse | <?= t('footer_rights') ?></p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
