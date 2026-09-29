// Tutup dropdown menu user saat klik di luar menu
document.addEventListener('click', function (e) {
    document.querySelectorAll('details.user-menu[open]').forEach(function (d) {
        if (!d.contains(e.target)) d.removeAttribute('open');
    });
});

// Konfirmasi untuk form/tombol yang punya atribut data-confirm
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
});
