// SIMPRO - Main JavaScript
// ============================================

// Auto dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function () {
    // Auto close alerts
    setTimeout(function () {
        document.querySelectorAll('.alert.auto-close').forEach(function (el) {
            let bsAlert = new bootstrap.Alert(el);
            bsAlert.close();
        });
    }, 5000);

    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn  = document.getElementById('sidebarClose');
    const overlay   = document.getElementById('sidebarOverlay');
    const sidebar   = document.getElementById('sidebar');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        if (sidebar.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // Auto close on menu click
    sidebar?.querySelectorAll('.nav-item a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) toggleSidebar();
        });
    });

    // Confirm delete
    document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm status change
    document.querySelectorAll('.btn-status-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const msg = this.dataset.msg || 'Yakin mengubah status ini?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});

// Format angka ke Rupiah
function formatRupiah(angka) {
    return 'Rp ' + parseFloat(angka || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

// Hitung subtotal & total PO
function hitungTotal() {
    let total = 0;
    document.querySelectorAll('.row-item').forEach(function (row) {
        const qty    = parseFloat(row.querySelector('.inp-qty')?.value || 0);
        const harga  = parseFloat(row.querySelector('.inp-harga')?.value || 0);
        const sub    = qty * harga;
        const elSub  = row.querySelector('.inp-subtotal');
        if (elSub) {
            elSub.value = sub;
        }
        const elSubTxt = row.querySelector('.txt-subtotal');
        if (elSubTxt) {
            elSubTxt.textContent = formatRupiah(sub);
        }
        total += sub;
    });
    const elTotal = document.getElementById('txt-total');
    if (elTotal) elTotal.textContent = formatRupiah(total);
    const elTotalInput = document.getElementById('inp-total');
    if (elTotalInput) elTotalInput.value = total;
}

// Tambah baris item PO
let rowIndex = 0;
function tambahBaris() {
    rowIndex++;
    const container = document.getElementById('tabel-items').querySelector('tbody');
    const bahanOptions = window.bahanOptions || '';
    const html = `<tr class="row-item">
        <td>${container.querySelectorAll('tr').length + 1}</td>
        <td>
            <select name="id_bahan[]" class="form-select form-select-sm sel-bahan" required onchange="isiHarga(this)">
                <option value="">-- Pilih --</option>
                ${bahanOptions}
            </select>
        </td>
        <td><input type="text" name="satuan[]" class="form-control form-control-sm inp-satuan" readonly></td>
        <td><input type="number" name="qty_pesan[]" class="form-control form-control-sm inp-qty" min="0.01" step="0.01" required oninput="hitungTotal()"></td>
        <td><input type="number" name="harga[]" class="form-control form-control-sm inp-harga" min="0" step="1" required oninput="hitungTotal()"></td>
        <td><span class="txt-subtotal">Rp 0</span><input type="hidden" name="subtotal[]" class="inp-subtotal"></td>
        <td><input type="text" name="keterangan_item[]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBaris(this)"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    container.insertAdjacentHTML('beforeend', html);
}

function hapusBaris(btn) {
    const row = btn.closest('tr');
    if (document.querySelectorAll('.row-item').length <= 1) {
        alert('Minimal harus ada 1 item.');
        return;
    }
    row.remove();
    hitungTotal();
    renumberRows();
}

function renumberRows() {
    document.querySelectorAll('.row-item').forEach(function (row, i) {
        row.cells[0].textContent = i + 1;
    });
}

// Isi harga & satuan otomatis dari pilihan bahan
function isiHarga(sel) {
    const opt    = sel.options[sel.selectedIndex];
    const row    = sel.closest('tr');
    const satuan = opt.dataset.satuan || '';
    const harga  = opt.dataset.harga  || 0;
    if (row.querySelector('.inp-satuan')) row.querySelector('.inp-satuan').value = satuan;
    if (row.querySelector('.inp-harga'))  row.querySelector('.inp-harga').value  = harga;
    hitungTotal();
}
