</main>

<footer class="site-footer" id="tentang">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/index.php" class="footer-brand">
                    <i class="bi bi-book-half text-white me-2"></i>Layar<span>Literasi</span>
                </a>
                <p class="footer-desc">
                    Membuka cakrawala pengetahuan melalui perpustakaan digital modern. Akses ribuan buku terkurasi, baca langsung online, dan nikmati kemudahan literasi di era digital.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white-50 fs-5"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white-50 fs-5"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="text-white-50 fs-5"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-white-50 fs-5"><i class="bi bi-facebook"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Navigasi</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog.php">Katalog Buku</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog.php#kategori">Kategori Buku</a></li>
                    <li><a href="<?= BASE_URL ?>/membership.php">Paket Membership</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php">Registrasi Anggota</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Kategori Populer</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/catalog.php?category=teknologi-coding">Teknologi & Coding</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog.php?category=bisnis-manajemen">Bisnis & Manajemen</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog.php?category=pengembangan-diri">Pengembangan Diri</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog.php?category=fiksi-sastra">Fiksi & Sastra</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog.php?category=sains-alam">Sains & Pengetahuan</a></li>
                </ul>
            </div>

            <!-- Library Info & Hours -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Pusat Layanan</h5>
                <ul class="footer-links text-white-50" style="font-size: 0.9rem;">
                    <li class="d-flex gap-2">
                        <i class="bi bi-geo-alt text-warning"></i>
                        <span>Jl. Pemuda No. 45, Gedung Literasi Lt. 3, Jakarta Pusat</span>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="bi bi-envelope text-warning"></i>
                        <span>support@layarliterasi.id</span>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="bi bi-telephone text-warning"></i>
                        <span>+62 21 8899 7700</span>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="bi bi-clock text-warning"></i>
                        <span>Layanan Online 24 Jam / 7 Hari</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong>Layar Literasi</strong>. Seluruh Hak Cipta Dilindungi.
            </div>
            <div class="d-flex gap-3">
                <a href="#" class="text-decoration-none text-muted">Syarat & Ketentuan</a>
                <a href="#" class="text-decoration-none text-muted">Kebijakan Privasi</a>
                <a href="#" class="text-decoration-none text-muted">Bantuan</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom Main JS -->
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<?php if (isset($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>
</body>
</html>
