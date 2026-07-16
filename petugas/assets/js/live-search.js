/**
 * Live Search generic handler.
 *
 * Dipakai di form pencarian mana pun dengan menambahkan atribut:
 *   <form data-live-search data-target="#idKontainerHasil">...</form>
 *
 * Cara kerja:
 * - Setiap input/select di dalam form akan memicu pencarian otomatis (dengan debounce)
 *   setiap kali user mengetik / mengubah pilihan, tanpa perlu klik tombol.
 * - Request dikirim via fetch() ke URL halaman saat ini + parameter form + "ajax=1".
 * - Server (file PHP terkait) mendeteksi parameter "ajax=1" dan hanya me-render
 *   potongan HTML hasil (baris tabel / daftar), bukan seluruh halaman.
 * - Hasil tsb dipakai untuk mengganti isi elemen target, jadi halaman tidak reload.
 * - Tombol submit tetap berfungsi (fallback) dan submit form asli tetap dicegah
 *   supaya tidak reload halaman.
 */
(function () {
    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function initLiveSearchForm(form) {
        var targetSelector = form.getAttribute('data-target');
        var target = targetSelector ? document.querySelector(targetSelector) : null;
        if (!target) return;

        function runSearch() {
            var params = new URLSearchParams(new FormData(form));
            params.set('ajax', '1');

            var url = window.location.pathname + '?' + params.toString();

            target.classList.add('is-loading');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('Gagal mengambil data pencarian');
                    return res.text();
                })
                .then(function (html) {
                    target.innerHTML = html;
                    target.classList.remove('is-loading');
                    // Update URL di address bar (tanpa "ajax=1") supaya bisa di-bookmark/refresh
                    params.delete('ajax');
                    var bookmarkUrl = window.location.pathname +
                        (params.toString() ? '?' + params.toString() : '');
                    window.history.replaceState({}, '', bookmarkUrl);
                })
                .catch(function () {
                    target.classList.remove('is-loading');
                });
        }

        var debouncedSearch = debounce(runSearch, 350);

        var fields = form.querySelectorAll('input[type="text"], input[type="search"], select');
        fields.forEach(function (field) {
            var eventName = field.tagName === 'SELECT' ? 'change' : 'input';
            field.addEventListener(eventName, debouncedSearch);
        });

        // Tetap dukung submit (misal user pencet Enter atau klik tombol Cari),
        // tapi cegah reload halaman dan pakai jalur AJAX yang sama.
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            runSearch();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-live-search]').forEach(initLiveSearchForm);
    });
})();
