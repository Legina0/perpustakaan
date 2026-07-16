    </div><!-- /.content -->
</div><!-- /.main -->
</div><!-- /.app -->
<script src="<?= BASE_URL ?>/assets/js/live-search.js"></script>
<script>
// Ganti confirm() bawaan browser dengan popup SweetAlert2 untuk aksi CRUD
// (hapus, nonaktifkan, reset password, dsb). Cukup pakai atribut data-confirm
// pada <a>/<button> alih-alih onclick="return confirm(...)".
document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    Swal.fire({
        icon: 'warning',
        title: 'Konfirmasi',
        text: el.getAttribute('data-confirm'),
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#4f46e5',
        reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) return;
        if (el.tagName === 'A') {
            window.location.href = el.getAttribute('href');
        } else if (el.form) {
            el.form.submit();
        }
    });
});
</script>
<script>
document.addEventListener('click', function (e) {
    var menu = document.getElementById('userMenu');
    if (menu && !menu.contains(e.target)) {
        menu.classList.remove('open');
    }
});
// Toggle lihat/sembunyikan password (tombol mata) di semua form yang punya input password
var ICON_EYE = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
var ICON_EYE_OFF = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.toggle-password');
    if (!btn) return;
    var input = btn.parentElement.querySelector('input');
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = ICON_EYE_OFF;
        btn.setAttribute('aria-label', 'Sembunyikan password');
    } else {
        input.type = 'password';
        btn.innerHTML = ICON_EYE;
        btn.setAttribute('aria-label', 'Tampilkan password');
    }
});
</script>
</body>
</html>
