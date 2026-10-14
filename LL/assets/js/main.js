/**
 * Layar Literasi - Main Client JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // AJAX Favorite Toggle
    document.querySelectorAll('.btn-fav-toggle').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const bookId = button.getAttribute('data-book-id');
            if (!bookId) return;

            try {
                const response = await fetch(`${baseUrl}/api/toggle-favorite.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ book_id: bookId })
                });

                const data = await response.json();

                if (data.need_login) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Perlu Masuk Akun',
                        text: 'Silakan login terlebih dahulu untuk menyimpan buku ke favorit.',
                        confirmButtonText: 'Masuk Sekarang',
                        confirmButtonColor: '#f97316',
                        showCancelButton: true,
                        cancelButtonText: 'Nanti'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `${baseUrl}/login.php?redirect=${encodeURIComponent(window.location.href)}`;
                        }
                    });
                    return;
                }

                if (data.success) {
                    if (data.action === 'added') {
                        button.classList.add('active');
                        button.innerHTML = '<i class="bi bi-heart-fill"></i>';
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Buku ditambahkan ke Favorit!',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        button.classList.remove('active');
                        button.innerHTML = '<i class="bi bi-heart"></i>';
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Dihapus dari Favorit',
                            showConfirmButton: false,
                            timer: 2000
                        });

                        // If on favorites page, remove card smoothly
                        const favCardCol = button.closest('.favorite-card-col');
                        if (favCardCol) {
                            favCardCol.style.opacity = '0';
                            favCardCol.style.transform = 'scale(0.8)';
                            setTimeout(() => {
                                favCardCol.remove();
                                const remaining = document.querySelectorAll('.favorite-card-col');
                                if (remaining.length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        }
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Terjadi kesalahan sistem.'
                    });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Jaringan',
                    text: 'Tidak dapat menghubungi server.'
                });
            }
        });
    });

    // Delete confirmation helper
    document.querySelectorAll('.btn-confirm-delete').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const form = button.closest('form');
            const itemName = button.getAttribute('data-name') || 'data ini';

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `Hapus ${itemName}? Tindakan ini tidak dapat dibatalkan!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (form) form.submit();
                    else if (button.getAttribute('href')) window.location.href = button.getAttribute('href');
                }
            });
        });
    });
});
