/**
 * laporan.js
 * Menangani interaksi modal (Detail, Edit, Hapus) di halaman Laporan
 */

// Modal DETAIL — isi dari data-attribute tombol
document.getElementById('modalDetailLaporan').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('detail-judul').textContent    = btn.getAttribute('data-judul');
    document.getElementById('detail-periode').textContent  = btn.getAttribute('data-periode');
    document.getElementById('detail-kategori').textContent = btn.getAttribute('data-kategori');
    document.getElementById('detail-dibuat').textContent   = btn.getAttribute('data-dibuat');
    document.getElementById('detail-oleh').textContent     = btn.getAttribute('data-oleh');
    document.getElementById('detail-catatan').textContent  = btn.getAttribute('data-catatan') || '-';
});

// Modal EDIT — isi form dari data-attribute tombol
document.getElementById('modalEditLaporan').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('edit-id').value      = btn.getAttribute('data-id');
    document.getElementById('edit-judul').value   = btn.getAttribute('data-judul');
    document.getElementById('edit-mulai').value   = btn.getAttribute('data-mulai');
    document.getElementById('edit-akhir').value   = btn.getAttribute('data-akhir');
    document.getElementById('edit-catatan').value = btn.getAttribute('data-catatan') || '';

    var jenis = btn.getAttribute('data-jenis');
    var sel   = document.getElementById('edit-jenis');
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === jenis) { sel.selectedIndex = i; break; }
    }
});

// Modal HAPUS — konfirmasi penghapusan
document.getElementById('modalHapusLaporan').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('hapus-id').value          = btn.getAttribute('data-id');
    document.getElementById('hapus-judul').textContent = btn.getAttribute('data-judul');
});
