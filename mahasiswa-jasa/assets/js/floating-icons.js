/**
 * Floating icons - efek "mengambang" + parallax ringan mengikuti kursor/jari.
 * Dipasang otomatis ke setiap elemen ber-class ".icon-float-zone"
 * (dipakai di halaman login/register maupun hero homepage).
 */
(function () {
    var zones = document.querySelectorAll('.icon-float-zone');
    if (!zones.length) return;

    zones.forEach(function (zone) {
        var icons = zone.querySelectorAll('.float-icon');

        function updateFromPoint(clientX, clientY) {
            var rect = zone.getBoundingClientRect();
            var x = (clientX - rect.left) / rect.width - 0.5;   // -0.5 .. 0.5
            var y = (clientY - rect.top) / rect.height - 0.5;

            icons.forEach(function (icon) {
                var depth = parseFloat(icon.getAttribute('data-depth')) || 15;
                icon.style.setProperty('--dx', (x * depth).toFixed(1) + 'px');
                icon.style.setProperty('--dy', (y * depth).toFixed(1) + 'px');
            });
        }

        function reset() {
            icons.forEach(function (icon) {
                icon.style.setProperty('--dx', '0px');
                icon.style.setProperty('--dy', '0px');
            });
        }

        zone.addEventListener('pointermove', function (e) {
            updateFromPoint(e.clientX, e.clientY);
        });
        zone.addEventListener('pointerleave', reset);

        zone.addEventListener('touchmove', function (e) {
            if (e.touches && e.touches.length > 0) {
                updateFromPoint(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: true });
        zone.addEventListener('touchend', reset);
    });
})();
