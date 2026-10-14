-- ========================================================
-- Database: layarliterasi
-- Website Perpustakaan Digital "Layar Literasi"
-- ========================================================

CREATE DATABASE IF NOT EXISTS `layarliterasi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `layarliterasi`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `bookmarks`;
DROP TABLE IF EXISTS `reading_progress`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `borrowings`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `memberships`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: memberships
-- --------------------------------------------------------
CREATE TABLE `memberships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `duration_days` INT NOT NULL DEFAULT 30,
  `max_borrow_days` INT NOT NULL DEFAULT 3,
  `max_borrow_count` INT NOT NULL DEFAULT 2,
  `can_read_premium` TINYINT(1) NOT NULL DEFAULT 0,
  `badge_color` VARCHAR(20) NOT NULL DEFAULT 'secondary',
  `description` TEXT,
  `features` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'member') NOT NULL DEFAULT 'member',
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
  `phone` VARCHAR(20) DEFAULT NULL,
  `membership_id` INT DEFAULT 1,
  `membership_expires_at` DATE DEFAULT NULL,
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT 'bi-book',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: books
-- --------------------------------------------------------
CREATE TABLE `books` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `author` VARCHAR(150) NOT NULL,
  `publisher` VARCHAR(150) NOT NULL,
  `publication_year` INT NOT NULL,
  `isbn` VARCHAR(30) DEFAULT NULL,
  `pages` INT NOT NULL DEFAULT 100,
  `description` TEXT,
  `stock` INT NOT NULL DEFAULT 5,
  `cover_image` VARCHAR(255) DEFAULT 'default-book.png',
  `pdf_file` VARCHAR(255) DEFAULT NULL,
  `membership_tier` ENUM('free', 'basic', 'premium') NOT NULL DEFAULT 'free',
  `views_count` INT NOT NULL DEFAULT 0,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 4.8,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: borrowings
-- --------------------------------------------------------
CREATE TABLE `borrowings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `borrow_code` VARCHAR(30) NOT NULL UNIQUE,
  `borrow_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `status` ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `membership_id` INT NOT NULL,
  `order_id` VARCHAR(50) NOT NULL UNIQUE,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `status` ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
  `proof_image` VARCHAR(255) DEFAULT NULL,
  `payment_date` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: favorites
-- --------------------------------------------------------
CREATE TABLE `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_book_fav` (`user_id`, `book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reading_progress
-- --------------------------------------------------------
CREATE TABLE `reading_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `current_page` INT NOT NULL DEFAULT 1,
  `total_pages` INT NOT NULL DEFAULT 1,
  `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `last_read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_book_progress` (`user_id`, `book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: bookmarks
-- --------------------------------------------------------
CREATE TABLE `bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `page_number` INT NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DATA DUMMY
-- ========================================================

-- Memberships
INSERT INTO `memberships` (`id`, `name`, `slug`, `price`, `duration_days`, `max_borrow_days`, `max_borrow_count`, `can_read_premium`, `badge_color`, `description`, `features`) VALUES
(1, 'Free', 'free', 0.00, 365, 3, 2, 0, 'secondary', 'Paket dasar gratis untuk membaca buku-buku katalog terbuka.', '["Akses buku kategori Free","Maksimal pinjam 2 buku","Durasi pinjam 3 hari","Baca online terbatas"]'),
(2, 'Basic', 'basic', 29000.00, 30, 7, 5, 0, 'info', 'Akses lebih luas dengan kuota peminjaman yang lebih banyak.', '["Akses buku Free & Basic","Maksimal pinjam 5 buku","Durasi pinjam 7 hari","Simpan bookmark & catatan","Dukungan prioritas"]'),
(3, 'Premium', 'premium', 49000.00, 30, 14, 10, 1, 'warning', 'Akses penuh tanpa batas ke seluruh koleksi eksklusif perpustakaan.', '["Akses SEMUA buku termasuk Premium","Maksimal pinjam 10 buku","Durasi pinjam 14 hari","Baca online tanpa batas","Fitur resume membaca otomatis","Prioritas peminjaman buku baru"]');

-- Users
-- Password for admin: admin123
-- Password for members: member123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `phone`, `membership_id`, `membership_expires_at`, `status`) VALUES
(1, 'Administrator Perpustakaan', 'admin@layarliterasi.id', '$2y$10$YIZCfv/odUrepmfam/Mysewt2hrM3Nd0XRCx8eERSXdkxQfbokVgS', 'admin', 'admin-avatar.png', '081234567890', 3, '2030-12-31', 'active'),
(2, 'Ikhsan Gusti', 'member@layarliterasi.id', '$2y$10$xX1b3tchMwoktSYrRTLkReIBqjrFGrxy6UyfDhljw2r6gR1xArzPe', 'member', 'member-avatar.png', '082198765432', 3, '2027-10-31', 'active'),
(3, 'Budi Santoso', 'budi@gmail.com', '$2y$10$xX1b3tchMwoktSYrRTLkReIBqjrFGrxy6UyfDhljw2r6gR1xArzPe', 'member', 'default-avatar.png', '085712345678', 2, '2026-12-31', 'active'),
(4, 'Siti Nurhaliza', 'siti@gmail.com', '$2y$10$xX1b3tchMwoktSYrRTLkReIBqjrFGrxy6UyfDhljw2r6gR1xArzPe', 'member', 'default-avatar.png', '087812349999', 1, '2027-01-01', 'active');

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'Teknologi & Coding', 'teknologi-coding', 'bi-laptop', 'Buku pemrograman, rekayasa perangkat lunak, AI, dan sistem informasi.'),
(2, 'Bisnis & Manajemen', 'bisnis-manajemen', 'bi-graph-up-arrow', 'Strategi bisnis, keuangan, manajemen tim, startup, dan investasi.'),
(3, 'Fiksi & Sastra', 'fiksi-sastra', 'bi-book-half', 'Novel, antologi puisi, cerita pendek, dan karya sastra klasik maupun modern.'),
(4, 'Pengembangan Diri', 'pengembangan-diri', 'bi-lightbulb', 'Motivasi, produktivitas, psikologi praktis, dan kebiasaan positif.'),
(5, 'Sains & Alam', 'sains-alam', 'bi-compass', 'Fisika, biologi, astronomi, dan wawasan penjelajahan alam semesta.'),
(6, 'Sejarah & Budaya', 'sejarah-budaya', 'bi-bank', 'Peradaban dunia, sejarah nusantara, antropologi, dan warisan budaya.');

-- Books
INSERT INTO `books` (`id`, `category_id`, `title`, `slug`, `author`, `publisher`, `publication_year`, `isbn`, `pages`, `description`, `stock`, `cover_image`, `pdf_file`, `membership_tier`, `views_count`, `rating`, `is_featured`) VALUES
(1, 4, 'Filosofi Teras: Menemukan Kedamaian Mental', 'filosofi-teras', 'A. Henry', 'Penerbit Kompas', 2019, '978-602-412-518-9', 346, 'Penerapan filsafat Stoa kuno untuk mengatasi emosi negatif, kekhawatiran berlebih, dan membentuk ketangguhan mental dalam menghadapi era modern.', 8, 'cover_filosofi_teras.jpg', 'sample_book_1.pdf', 'free', 1420, 4.9, 1),
(2, 1, 'Pemrograman Web Modern dengan PHP 8 & MySQL', 'pemrograman-web-modern-php-8', 'Pratama Kusuma, M.Kom', 'Informatika Press', 2023, '978-623-010-881-2', 280, 'Panduan komprehensif membangun aplikasi web aman, cepat, dan scalable menggunakan fitur modern PHP 8, PDO, dan arsitektur MVC.', 5, 'cover_php_mysql.jpg', 'sample_book_2.pdf', 'basic', 980, 4.8, 1),
(3, 1, 'Clean Code: Panduan Menulis Kode Berkualitas', 'clean-code-indonesia', 'Robert C. Martin (Terj.)', 'Prentice Hall Tech', 2022, '978-013-235-088-4', 464, 'Prinsip, pola desain, dan praktik terbaik bagi para insinyur perangkat lunak untuk menulis kode yang mudah dibaca, diuji, dan dipelihara.', 4, 'cover_clean_code.jpg', 'sample_book_1.pdf', 'premium', 2150, 4.9, 1),
(4, 4, 'Atomic Habits: Perubahan Kecil Berdampak Besar', 'atomic-habits', 'James Clear', 'Gramedia Pustaka Utama', 2020, '978-602-063-317-6', 352, 'Metode praktis berbasis bukti ilmiah untuk membentuk kebiasaan baik, membuang kebiasaan buruk, dan menguasai perilaku terarah setiap hari.', 6, 'cover_atomic_habits.jpg', 'sample_book_2.pdf', 'basic', 1830, 4.9, 1),
(5, 3, 'Laskar Pelangi', 'laskar-pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, '978-979-306-279-2', 529, 'Kisah inspiratif perjuangan sepuluh anak di Belitung dalam menempuh pendidikan di tengah keterbatasan fasilitas dan kehidupan sosial.', 7, 'cover_laskar_pelangi.jpg', 'sample_book_1.pdf', 'free', 3120, 4.8, 1),
(6, 6, 'Sapiens: Riwayat Singkat Umat Manusia', 'sapiens-riwayat-singkat', 'Yuval Noah Harari', 'KPG (Kepustakaan Populer Gramedia)', 2017, '978-602-424-416-3', 512, 'Eksplorasi memukau tentang bagaimana Homo sapiens yang rapuh berhasil menaklukkan bumi melalui tiga revolusi: Kognitif, Pertanian, dan Sains.', 3, 'cover_sapiens.jpg', 'sample_book_2.pdf', 'premium', 1650, 4.9, 1),
(7, 3, 'Bumi Manusia', 'bumi-manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 2005, '978-979-973-123-4', 535, 'Mahakarya roman sejarah tentang pergulatan batin Minke, pemuda pribumi terpelajar di era kolonial Hindia Belanda bersama Annelies dan Nyai Ontosoroh.', 5, 'cover_bumi_manusia.jpg', 'sample_book_1.pdf', 'basic', 2410, 4.9, 0),
(8, 2, 'The Psychology of Money: Rahasia Keuangan Abadi', 'the-psychology-of-money', 'Morgan Housel', 'Baca Pustaka', 2021, '978-602-648-657-8', 268, '19 cerita pendek tentang cara manusia memandang uang, keserakahan, ego, dan keputusan finansial yang rasional vs emosional.', 4, 'cover_psychology_money.jpg', 'sample_book_2.pdf', 'premium', 1290, 4.8, 0),
(9, 5, 'Kosmos: Menjelajahi Keagungan Alam Semesta', 'kosmos-carl-sagan', 'Carl Sagan', 'Gramedia Sains', 2018, '978-602-033-112-6', 420, 'Perjalanan ilmiah mendalam yang menghubungkan sains, sejarah peradaban, dan tempat manusia di antara milyaran galaksi di antariksa.', 6, 'cover_kosmos.jpg', 'sample_book_1.pdf', 'free', 840, 4.7, 0),
(10, 2, 'The Lean Startup: Inovasi Berkelanjutan', 'the-lean-startup', 'Eric Ries', 'Elex Media Komputindo', 2018, '978-602-047-920-0', 320, 'Pendekatan revolusioner dalam membangun dan mengelola perusahaan rintisan melalui siklus Build-Measure-Learn yang lincah.', 5, 'cover_lean_startup.jpg', 'sample_book_2.pdf', 'basic', 760, 4.7, 0);

-- Borrowings
INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `borrow_code`, `borrow_date`, `due_date`, `return_date`, `status`, `fine_amount`, `notes`) VALUES
(1, 2, 1, 'PJ-20261001-001', '2026-10-01', '2026-10-15', NULL, 'borrowed', 0.00, 'Peminjaman aktif oleh Ikhsan Gusti'),
(2, 2, 3, 'PJ-20260920-002', '2026-09-20', '2026-10-04', '2026-10-03', 'returned', 0.00, 'Buku dikembalikan tepat waktu dalam kondisi sangat baik'),
(3, 3, 2, 'PJ-20260915-003', '2026-09-15', '2026-09-22', '2026-09-24', 'returned', 2000.00, 'Terlambat 2 hari (Denda Rp 2.000 sudah dilunasi)'),
(4, 4, 5, 'PJ-20260928-004', '2026-09-28', '2026-10-01', NULL, 'overdue', 3000.00, 'Peminjaman melewati jatuh tempo');

-- Payments
INSERT INTO `payments` (`id`, `user_id`, `membership_id`, `order_id`, `amount`, `payment_method`, `status`, `proof_image`, `payment_date`) VALUES
(1, 2, 3, 'INV-LL-20261001-001', 49000.00, 'QRIS Instant', 'success', 'qris_proof.png', '2026-10-01 10:15:00'),
(2, 3, 2, 'INV-LL-20260910-002', 29000.00, 'Transfer Bank BCA', 'success', 'bca_proof.png', '2026-09-10 14:30:00');

-- Favorites
INSERT INTO `favorites` (`id`, `user_id`, `book_id`, `created_at`) VALUES
(1, 2, 1, '2026-10-01 10:30:00'),
(2, 2, 3, '2026-10-01 10:32:00'),
(3, 2, 4, '2026-10-02 08:15:00'),
(4, 3, 2, '2026-09-12 11:00:00');

-- Reading Progress
INSERT INTO `reading_progress` (`id`, `user_id`, `book_id`, `current_page`, `total_pages`, `percentage`, `last_read_at`) VALUES
(1, 2, 1, 142, 210, 67.62, '2026-10-04 14:15:00'),
(2, 2, 3, 56, 464, 12.07, '2026-10-03 19:40:00'),
(3, 3, 2, 85, 280, 30.36, '2026-10-02 21:00:00');

-- Bookmarks
INSERT INTO `bookmarks` (`id`, `user_id`, `book_id`, `page_number`, `note`, `created_at`) VALUES
(1, 2, 1, 45, 'Bab 2: Mengendalikan Hal yang Bisa Dikendalikan', '2026-10-02 11:20:00'),
(2, 2, 1, 142, 'Prinsip Dikotomi Kendali Epiktetos', '2026-10-04 14:15:00'),
(3, 2, 3, 24, 'Aturan Penamaan Variabel yang Bermakna', '2026-10-03 19:45:00');
