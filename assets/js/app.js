// assets/js/app.js — SIMBA JavaScript Helpers

/* ----------------------
   TOAST NOTIFICATION
   ---------------------- */
function showToast(message, type = 'success', duration = 3500) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = {
        success: '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>',
        error:   '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = (icons[type] || '') + `<span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/* ----------------------
   MODAL KONFIRMASI HAPUS/BATAL
   ---------------------- */
function showConfirmModal({ title, desc, btnLabel = 'Ya, Lanjutkan', btnClass = 'btn-danger', onConfirm }) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-desc').textContent  = desc;

    const confirmBtn = document.getElementById('modal-confirm-btn');
    confirmBtn.textContent = btnLabel;
    confirmBtn.className   = `btn ${btnClass}`;

    modal.classList.add('show');

    // Clone to remove old listeners
    const newBtn = confirmBtn.cloneNode(true);
    newBtn.textContent = btnLabel;
    newBtn.className   = `btn ${btnClass}`;
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener('click', () => {
        modal.classList.remove('show');
        onConfirm();
    });
}

function closeModal() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show'));
}

// Tutup modal klik overlay
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) closeModal();
});

/* ----------------------
   KONFIRMASI BATAL PENGAJUAN
   ---------------------- */
function konfirmasiBatal(id) {
    showConfirmModal({
        title: 'Batalkan Pengajuan?',
        desc: 'Pengajuan yang dibatalkan tidak dapat dikembalikan.',
        btnLabel: 'Ya, Batalkan',
        btnClass: 'btn-danger',
        onConfirm: () => {
            window.location.href = `batal.php?id=${id}`;
        }
    });
}

function konfirmasiSerahkan(id) {
    showConfirmModal({
        title: 'Serahkan Barang?',
        desc: 'Status peminjaman akan berubah menjadi aktif setelah barang diterima peminjam.',
        btnLabel: 'Ya, Serahkan',
        btnClass: 'btn-approve',
        onConfirm: () => {
            window.location.href = `serahkan.php?id=${id}`;
        }
    });
}

/* ----------------------
   UPLOAD FILE — DRAG & DROP
   ---------------------- */
function initUploadArea(areaId, inputId, infoId) {
    const area  = document.getElementById(areaId);
    const input = document.getElementById(inputId);
    const info  = document.getElementById(infoId);
    if (!area || !input) return;

    area.addEventListener('click', () => input.click());

    area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', () => area.classList.remove('dragover'));
    area.addEventListener('drop', e => {
        e.preventDefault();
        area.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) {
            input.files = files;
            updateFileInfo(files[0], info);
        }
    });

    input.addEventListener('change', () => {
        if (input.files[0]) updateFileInfo(input.files[0], info);
    });
}

function updateFileInfo(file, infoEl) {
    if (!infoEl) return;
    const size = (file.size / 1024 / 1024).toFixed(2);
    infoEl.innerHTML = `
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
        </svg>
        <span>${file.name} (${size} MB)</span>
    `;
    infoEl.style.display = 'flex';
}

/* ----------------------
   BARANG PICKER (Form Ajukan)
   ---------------------- */
let selectedBarang = []; // [{id, nama, kode, kategori, stok, wajib_surat}]

function initBarangPicker() {
    const addBtn = document.getElementById('btn-tambah-barang');
    if (!addBtn) return;

    addBtn.addEventListener('click', () => {
        document.getElementById('modal-barang').classList.add('show');
        loadBarangOptions();
    });
}

function loadBarangOptions() {
    // Barang list sudah ada di tabel — pakai data yang di-render PHP
    // Hanya fungsi filter/search
    const search = document.getElementById('search-barang');
    if (search) {
        search.addEventListener('input', filterBarangRows);
    }
}

function filterBarangRows() {
    const q = document.getElementById('search-barang').value.toLowerCase();
    document.querySelectorAll('.barang-pick-row').forEach(row => {
        const teks = row.textContent.toLowerCase();
        row.style.display = teks.includes(q) ? '' : 'none';
    });
}

function pilihBarang(id, nama, kode, kategori, stok, wajib) {
    // Cek duplikat
    if (selectedBarang.find(b => b.id === id)) {
        showToast('Barang sudah ditambahkan.', 'warning');
        return;
    }
    selectedBarang.push({ id, nama, kode, kategori, stok, wajib });
    renderSelectedBarang();
    closeModal();
    checkWajibSurat();
}

function renderSelectedBarang() {
    const tbody = document.getElementById('selected-barang-tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (selectedBarang.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:20px;">Belum ada barang dipilih</td></tr>`;
        return;
    }

    selectedBarang.forEach((b, i) => {
        const badge = b.wajib
            ? '<span class="badge badge-surat surat-wajib">Wajib Surat</span>'
            : '<span class="badge badge-surat surat-bebas">Bebas</span>';

        tbody.innerHTML += `
            <tr>
                <td>
                    <div class="item-nama">${escHtml(b.nama)}</div>
                    <div class="item-kode">${escHtml(b.kode)}</div>
                </td>
                <td>${escHtml(b.kategori)}</td>
                <td><span class="stok-badge">${b.stok}</span></td>
                <td>${badge}</td>
                <td>
                    <input type="number" name="jumlah[${b.id}]" value="1"
                           min="1" max="${b.stok}" class="qty-input">
                    <input type="hidden" name="barang_id[]" value="${b.id}">
                </td>
                <td>
                    <button type="button" class="btn-hapus-row" onclick="hapusBarang(${i})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14H6L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    });
}

function hapusBarang(index) {
    selectedBarang.splice(index, 1);
    renderSelectedBarang();
    checkWajibSurat();
}

function checkWajibSurat() {
    const butuhSurat = selectedBarang.some(b => b.wajib) ||
        Array.from(document.querySelectorAll('input[name="id_barang[]"]:checked')).some(cb => cb.dataset.wajibSurat === '1');
    const uploadSection = document.getElementById('upload-surat-section');
    const alertSurat    = document.getElementById('alert-wajib-surat');
    const namaWajib     = selectedBarang.filter(b => b.wajib).map(b => b.nama)
        .concat(Array.from(document.querySelectorAll('input[name="id_barang[]"]:checked'))
            .filter(cb => cb.dataset.wajibSurat === '1')
            .map(cb => cb.dataset.nama || 'barang ini'))
        .join(', ');

    if (uploadSection) uploadSection.style.display = butuhSurat ? '' : 'none';
    if (alertSurat) alertSurat.style.display = butuhSurat ? '' : 'none';
    if (alertSurat && butuhSurat) {
        alertSurat.innerHTML = `
            ⚠️ Peminjaman <strong>${escHtml(namaWajib)}</strong> memerlukan surat pengantar resmi.
            Surat wajib diupload sebelum submit.
        `;
    }
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function checkWajibSurat() {
    const checkedBarang = Array.from(document.querySelectorAll('input[name="id_barang[]"]:checked'));
    const wajibChecked = checkedBarang.filter(cb => cb.dataset.wajibSurat === '1');
    const butuhSurat = selectedBarang.some(b => b.wajib) || wajibChecked.length > 0;
    const uploadSection = document.getElementById('upload-surat-section');
    const alertSurat = document.getElementById('alert-wajib-surat');
    const uploadArea = document.getElementById('upload-area-surat');
    const inputSurat = document.getElementById('file-surat');
    const fileInfo = document.getElementById('file-surat-info');
    const namaWajib = selectedBarang.filter(b => b.wajib).map(b => b.nama)
        .concat(wajibChecked.map(cb => cb.dataset.nama || 'barang ini'))
        .join(', ');

    if (uploadSection) uploadSection.style.display = '';
    if (uploadArea) uploadArea.classList.toggle('is-disabled', !butuhSurat);
    if (inputSurat) {
        inputSurat.disabled = !butuhSurat;
        if (!butuhSurat) inputSurat.value = '';
    }
    if (alertSurat) {
        alertSurat.innerHTML = butuhSurat
            ? `Peminjaman <strong>${escHtml(namaWajib)}</strong> memerlukan surat pengantar resmi. Surat wajib diupload sebelum submit.`
            : 'Pilih barang berlabel wajib surat untuk mengaktifkan upload PDF.';
    }
    if (!butuhSurat && fileInfo) {
        fileInfo.style.display = 'none';
        fileInfo.innerHTML = '';
    }
}

function toggleJumlah(checkbox) {
    const id = checkbox.dataset.index || checkbox.value;
    const jumlahInput = document.getElementById(`jumlah-${id}`);

    if (!jumlahInput) return;

    jumlahInput.disabled = !checkbox.checked;
    if (checkbox.checked) {
        jumlahInput.focus();
        if (!jumlahInput.value || Number(jumlahInput.value) < 1) {
            jumlahInput.value = 1;
        }
    }
    checkWajibSurat();
}

/* ----------------------
   VALIDASI FORM AJUKAN
   ---------------------- */
function validasiFormAjukan() {
    const keperluan = document.querySelector('[name=keperluan]');
    const tgl_pinjam  = document.querySelector('[name=tanggal_pinjam]');
    const tgl_kembali = document.querySelector('[name=tanggal_kembali]');
    const barangChecked = document.querySelectorAll('input[name="id_barang[]"]:checked');
    const fileSurat = document.querySelector('input[name="file_surat"]');

    if (keperluan && !keperluan.value.trim()) {
        showToast('Keperluan tidak boleh kosong.', 'error');
        keperluan.focus();
        return false;
    }
    if (tgl_pinjam && !tgl_pinjam.value) {
        showToast('Tanggal pinjam harus diisi.', 'error');
        return false;
    }
    if (tgl_kembali && !tgl_kembali.value) {
        showToast('Tanggal kembali harus diisi.', 'error');
        return false;
    }
    if (barangChecked.length === 0 && selectedBarang.length === 0) {
        showToast('Pilih minimal 1 barang.', 'error');
        return false;
    }

    for (const checkbox of barangChecked) {
        const id = checkbox.dataset.index || checkbox.value;
        const jumlahInput = document.getElementById(`jumlah-${id}`);
        if (!jumlahInput || Number(jumlahInput.value) < 1) {
            showToast('Jumlah barang yang dipilih minimal 1.', 'error');
            if (jumlahInput) jumlahInput.focus();
            return false;
        }
    }

    const butuhSurat = Array.from(barangChecked).some(cb => cb.dataset.wajibSurat === '1') ||
        selectedBarang.some(b => b.wajib);

    if (butuhSurat && fileSurat && !fileSurat.files.length) {
        showToast('Upload surat permohonan PDF terlebih dahulu.', 'warning');
        return false;
    }

    return true;
}

/* ----------------------
   INIT SEMUA
   ---------------------- */
document.addEventListener('DOMContentLoaded', () => {
    initBarangPicker();
    initUploadArea('upload-area-surat', 'file-surat', 'file-surat-info');
    initUploadArea('upload-area-identitas', 'file-identitas', 'file-identitas-info');

    // Auto-hide alerts
    document.querySelectorAll('.alert-auto-hide').forEach(el => {
        setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; }, 3500);
    });

    // Flash message sebagai toast
    const flashSuccess = document.getElementById('flash-success-data');
    const flashError   = document.getElementById('flash-error-data');
    if (flashSuccess) showToast(flashSuccess.textContent, 'success');
    if (flashError)   showToast(flashError.textContent, 'error');

    const formAjukan = document.getElementById('form-ajukan');
    if (formAjukan) {
        formAjukan.addEventListener('submit', (e) => {
            if (!validasiFormAjukan()) {
                e.preventDefault();
            }
        });
    }
});
