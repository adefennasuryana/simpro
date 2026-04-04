<?php
/**
 * PO: detail.php — Detail & Manajemen Status PO
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

$id  = (int)($_GET['id'] ?? 0);
$po  = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT p.*, pr.nama_proyek, pr.kode_proyek, pr.lokasi,
            s.nama_supplier, s.alamat as alamat_supplier, s.no_telp, s.email,
            u.nama as nama_user
     FROM po p
     LEFT JOIN proyek pr ON pr.id_proyek = p.id_proyek
     LEFT JOIN supplier s ON s.id_supplier = p.id_supplier
     LEFT JOIN users u ON u.id_user = p.id_user
     WHERE p.id_po = $id LIMIT 1"));

if (!$po) { set_flash('danger','PO tidak ditemukan.'); redirect(BASE_URL.'/po/index.php'); }

$pageTitle = 'Detail PO: ' . $po['nomor_po'];

// Ubah status
if (isset($_GET['ubah_status']) && in_array($_SESSION['role'], ['admin','manajer','purchasing'])) {
    $status_baru = $_GET['ubah_status'];
    $allowed     = ['draft','diajukan','disetujui','ditolak','dikirim_sebagian','selesai','direvisi','final','diproses'];
    if (in_array($status_baru, $allowed)) {
        $st = mysqli_prepare($koneksi,"UPDATE po SET status_po=? WHERE id_po=?");
        mysqli_stmt_bind_param($st,'si',$status_baru,$id);
        mysqli_stmt_execute($st);
        
        // ---- START SINKRONISASI MR -----
        if ($status_baru === 'selesai') {
            $mr_q = mysqli_query($koneksi, "SELECT id_permintaan FROM permintaan_bahan WHERE id_po = $id LIMIT 1");
            if ($mr_q && $mr_row = mysqli_fetch_assoc($mr_q)) {
                $id_mr = (int)$mr_row['id_permintaan'];
                
                $items_mr = mysqli_query($koneksi, "SELECT * FROM permintaan_bahan_detail WHERE id_permintaan = $id_mr");
                $semua_selesai = true;
                $ada_terpenuhi_sebagian = false;
                
                while ($item = mysqli_fetch_assoc($items_mr)) {
                    $id_bahan = (int)$item['id_bahan'];
                    $qty_diminta = (float)$item['qty_diminta'];
                    
                    // Ambil detail PO dan total realisasi penerimaan
                    $qpo = mysqli_fetch_assoc(mysqli_query($koneksi, "
                        SELECT d.id_detail, d.qty_pesan, COALESCE(SUM(pd.qty_diterima),0) as total_terima
                        FROM po_detail d
                        LEFT JOIN penerimaan_detail pd ON pd.id_detail = d.id_detail
                        WHERE d.id_po = $id AND d.id_bahan = $id_bahan
                        GROUP BY d.id_detail
                    "));
                    $qty_realisasi_po = $qpo ? (float)$qpo['total_terima'] : 0;
                    $qty_pesan_po     = $qpo ? (float)$qpo['qty_pesan'] : $qty_diminta;
                    $id_detail_po     = $qpo ? (int)$qpo['id_detail'] : 0;
                    
                    // Ambil qty_alokasi_material dari log revisi terbaru
                    $qrev = mysqli_fetch_assoc(mysqli_query($koneksi, "
                        SELECT qty_alokasi_material FROM po_revisi_log 
                        WHERE id_po = $id AND id_detail_po = $id_detail_po 
                        ORDER BY id_revisi DESC LIMIT 1
                    "));
                    $qty_alokasi_material = $qrev ? (float)$qrev['qty_alokasi_material'] : 0;
                    
                    // Jika tidak ada di log revisi, coba hitung jika pesan_po lebih kecil dari qty_diminta
                    if ($qty_alokasi_material == 0 && $qty_pesan_po < $qty_diminta) {
                        $qty_alokasi_material = $qty_diminta - $qty_pesan_po;
                    }
                    
                    // Rumus sesuai instruksi
                    $qty_po = $qty_diminta - $qty_alokasi_material;
                    $qty_terpenuhi = $qty_alokasi_material + $qty_realisasi_po;
                    $qty_sisa = $qty_diminta - $qty_terpenuhi;
                    if ($qty_sisa < 0) $qty_sisa = 0;
                    
                    $status_item = 'Menunggu';
                    if ($qty_terpenuhi >= $qty_diminta) {
                        $status_item = 'Selesai';
                    } elseif ($qty_terpenuhi > 0) {
                        $status_item = 'Terpenuhi Sebagian';
                        $semua_selesai = false;
                        $ada_terpenuhi_sebagian = true;
                    } else {
                        $semua_selesai = false;
                    }
                    
                    mysqli_query($koneksi, "UPDATE permintaan_bahan_detail 
                        SET qty_alokasi_material = $qty_alokasi_material, qty_po = $qty_po, 
                            qty_terpenuhi = $qty_terpenuhi, qty_sisa = $qty_sisa, status_item = '$status_item' 
                        WHERE id_detail_permintaan = {$item['id_detail_permintaan']}");
                }
                
                $status_mr_baru = 'Diproses ke PO';
                if ($semua_selesai) {
                    $status_mr_baru = 'Selesai';
                } elseif ($ada_terpenuhi_sebagian) {
                    $status_mr_baru = 'Terpenuhi Sebagian';
                }
                
                if ($status_mr_baru !== 'Diproses ke PO') {
                    mysqli_query($koneksi, "UPDATE permintaan_bahan SET status_permintaan = '$status_mr_baru' WHERE id_permintaan = $id_mr");
                }
            }
        }
        // ---- END SINKRONISASI MR -----

        set_flash('success','Status PO berhasil diubah ke: '.ucfirst(str_replace('_',' ',$status_baru)));
    }
    redirect(BASE_URL . "/po/detail.php?id=$id");
}

// Ambil item PO dengan status penerimaan (menggunakan VIEW)
$items = mysqli_query($koneksi,
    "SELECT * FROM v_po_item_status WHERE id_po = $id ORDER BY id_detail");

// Status penerimaan PO otomatis
$po_rcv = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT * FROM v_po_status_penerimaan WHERE id_po = $id"));

require_once '../template/header.php';

function badge_si($st) {
    $m=['Belum diterima'=>['secondary','Belum diterima'],'Diterima sebagian'=>['warning','Diterima sebagian'],'Selesai'=>['success','Selesai']];
    $d=$m[$st]??['secondary',$st];
    return "<span class='badge bg-{$d[0]}'>{$d[1]}</span>";
}
function badge_po($s) {
    $m=[
        'draft'=>['secondary','Draft'],'diajukan'=>['warning','Diajukan'],'disetujui'=>['info','Disetujui'],
        'ditolak'=>['danger','Ditolak'],'dikirim_sebagian'=>['primary','Dikirim Sebagian'],'selesai'=>['success','Selesai'],
        'direvisi'=>['danger','Direvisi'],'final'=>['success','Final'],'diproses'=>['primary','Diproses']
    ];
    $d=$m[$s]??['secondary',ucfirst($s)];
    return "<span class='badge bg-{$d[0]} fs-6'>{$d[1]}</span>";
}
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4><i class="fas fa-file-invoice me-2 text-primary"></i><?= e($po['nomor_po']) ?></h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">PO</a></li>
            <li class="breadcrumb-item active"><?= e($po['nomor_po']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="cetak.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank">
            <i class="fas fa-print me-1"></i>Cetak PO
        </a>
        
        <a href="revisi_po.php?id=<?= $id ?>" class="btn btn-warning">
            <i class="fas fa-sliders-h me-1"></i>Revisi PO
        </a>
        
        <?php if (in_array($po['status_po'], ['disetujui', 'final', 'diproses', 'dikirim_sebagian']) || $po['status_po']==='dikirim_sebagian'): ?>
        <a href="../penerimaan/tambah.php?id_po=<?= $id ?>" class="btn btn-success">
            <i class="fas fa-boxes me-1"></i>Input Penerimaan
        </a>
        <?php endif; ?>
    </div>
</div>

<?php show_flash(); ?>

<div class="row g-3">
    <!-- Info PO -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Informasi PO</span>
                <?= badge_po($po['status_po']) ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="140">Nomor PO</td><td class="fw-600"><?= e($po['nomor_po']) ?></td></tr>
                            <tr><td class="text-muted">Tanggal PO</td><td><?= tgl_indo($po['tanggal_po']) ?></td></tr>
                            <tr><td class="text-muted">Dibuat Oleh</td><td><?= e($po['nama_user']) ?></td></tr>
                            <tr><td class="text-muted">Total</td><td class="fw-bold text-primary fs-6"><?= rupiah($po['total']) ?></td></tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="100">Proyek</td><td class="fw-600">[<?= e($po['kode_proyek']) ?>] <?= e($po['nama_proyek']) ?></td></tr>
                            <tr><td class="text-muted">Lokasi</td><td><?= e($po['lokasi']) ?></td></tr>
                            <tr><td class="text-muted">Supplier</td><td class="fw-600"><?= e($po['nama_supplier']) ?></td></tr>
                            <tr><td class="text-muted">Telp</td><td><?= e($po['no_telp']) ?></td></tr>
                        </table>
                    </div>
                    <?php if ($po['keterangan']): ?>
                    <div class="col-12 mt-2">
                        <div class="alert alert-light mb-0 py-2"><small><strong>Keterangan:</strong> <?= e($po['keterangan']) ?></small></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabel Item -->
        <div class="card">
            <div class="card-header">Item Bahan Baku</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Bahan Baku</th>
                        <th>Satuan</th>
                        <th class="text-end">Qty Pesan</th>
                        <th class="text-end">Total Diterima</th>
                        <th class="text-end">Sisa</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Subtotal</th>
                        <th>Status Item</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while($item=mysqli_fetch_assoc($items)): ?>
                    <tr class="po-item-row">
                        <td><?= $no++ ?></td>
                        <td>
                            <div class="fw-600"><?= e($item['nama_bahan']) ?></div>
                            <small class="text-muted"><?= e($item['kode_bahan']) ?></small>
                        </td>
                        <td><?= e($item['satuan']) ?></td>
                        <td class="text-end"><?= number_format($item['qty_pesan'],2,',','.') ?></td>
                        <td class="text-end<?= $item['total_qty_diterima']>0?' text-success fw-600':'' ?>"><?= number_format($item['total_qty_diterima'],2,',','.') ?></td>
                        <td class="text-end<?= $item['qty_sisa']>0?' text-danger':'' ?>"><?= number_format($item['qty_sisa'],2,',','.') ?></td>
                        <td class="text-end"><?= rupiah($item['harga']) ?></td>
                        <td class="text-end fw-600"><?= rupiah($item['subtotal']) ?></td>
                        <td><?= badge_si($item['status_item']) ?></td>
                    </tr>
                    <?php if ($item['ket_item']): ?>
                    <tr><td colspan="9" class="py-0"><small class="text-muted ps-2">↳ <?= e($item['ket_item']) ?></small></td></tr>
                    <?php endif; ?>
                    <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-light">
                    <tr>
                        <td colspan="7" class="text-end fw-bold">Total PO:</td>
                        <td class="text-end fw-bold text-primary"><?= rupiah($po['total']) ?></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel Status & Aksi -->
    <div class="col-lg-4">
        <!-- Status Penerimaan -->
        <div class="card mb-3">
            <div class="card-header">Status Penerimaan Barang</div>
            <div class="card-body">
                <?php if ($po_rcv): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Status:</span>
                    <?php
                    $sri=['belum_diterima'=>['secondary','Belum Diterima'],'dikirim_sebagian'=>['warning','Dikirim Sebagian'],'selesai'=>['success','Selesai']];
                    $srd=$sri[$po_rcv['status_penerimaan']]??['secondary','—'];
                    echo "<span class='badge bg-{$srd[0]}'>{$srd[1]}</span>";
                    ?>
                </div>
                <div class="d-flex justify-content-between text-sm">
                    <span class="text-muted">Total Item:</span><strong><?= $po_rcv['total_item'] ?></strong>
                </div>
                <div class="d-flex justify-content-between text-sm mt-1">
                    <span class="text-muted">Belum diterima:</span><strong class="text-secondary"><?= $po_rcv['item_belum'] ?></strong>
                </div>
                <div class="d-flex justify-content-between text-sm mt-1">
                    <span class="text-muted">Diterima sebagian:</span><strong class="text-warning"><?= $po_rcv['item_sebagian'] ?></strong>
                </div>
                <div class="d-flex justify-content-between text-sm mt-1">
                    <span class="text-muted">Selesai:</span><strong class="text-success"><?= $po_rcv['item_selesai'] ?></strong>
                </div>
                <?php else: ?>
                <p class="text-muted small mb-0">Belum ada data penerimaan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ubah Status PO -->
        <?php if (in_array($_SESSION['role'],['admin','manajer','purchasing'])): ?>
        <div class="card mb-3">
            <div class="card-header">Ubah Status PO</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                <?php
                $flow = [
                    'draft'         => [['diajukan','Ajukan PO','warning','paper-plane'],['ditolak','Tolak','danger','times-circle']],
                    'direvisi'      => [['diajukan','Ajukan Hasil Revisi','warning','paper-plane']],
                    'diajukan'      => [['disetujui','Setujui','success','check-circle'],['ditolak','Tolak','danger','times-circle'],['draft','Kembalikan Draft','secondary','undo']],
                    'disetujui'     => [['final','Tandai Final','success','check-double'], ['ditolak','Tolak','danger','times-circle']],
                    'ditolak'       => [['draft','Kembalikan Draft','secondary','undo']],
                    'final'         => [['diproses','PO Diproses/Dikirim','primary','truck']],
                    'diproses'      => [['dikirim_sebagian','Dikirim Sebagian','warning','box-open'],['selesai','Tandai Selesai','success','check-double']],
                    'dikirim_sebagian'=>[['selesai','Tandai Selesai','success','check-double']],
                    'selesai'       => [],
                ];
                $btns = $flow[$po['status_po']] ?? [];
                if (empty($btns)) echo "<p class='text-muted small mb-0'>Tidak ada aksi yang tersedia.</p>";
                foreach ($btns as $btn):
                    [$target,$label,$cls,$icon] = $btn;
                ?>
                <a href="?id=<?= $id ?>&ubah_status=<?= $target ?>"
                   class="btn btn-<?= $cls ?> btn-status-confirm"
                   data-msg="Yakin mengubah status ke '<?= $label ?>'?">
                    <i class="fas fa-<?= $icon ?> me-2"></i><?= $label ?>
                </a>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Riwayat Penerimaan -->
        <?php
        $rcv_list = mysqli_query($koneksi,
            "SELECT pn.nomor_penerimaan, pn.tanggal_terima, u.nama as nama_user, pn.keterangan
             FROM penerimaan pn
             LEFT JOIN users u ON u.id_user = pn.id_user
             WHERE pn.id_po = $id
             ORDER BY pn.tanggal_terima DESC");
        ?>
        <div class="card">
            <div class="card-header">Riwayat Penerimaan</div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($rcv_list) === 0): ?>
                <p class="text-muted small p-3 mb-0">Belum ada penerimaan.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php while($rcv=mysqli_fetch_assoc($rcv_list)): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="fw-600 small"><?= e($rcv['nomor_penerimaan']) ?></span>
                        <small class="text-muted"><?= tgl_indo($rcv['tanggal_terima']) ?></small>
                    </div>
                    <small class="text-muted">Oleh: <?= e($rcv['nama_user']) ?></small>
                </li>
                <?php endwhile; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat Revisi -->
        <?php
        $rev_list = mysqli_query($koneksi,
            "SELECT p.qty_diminta, p.qty_alokasi_material, p.tanggal_revisi, p.alasan_revisi, u.nama as nama_user, b.nama_bahan 
             FROM po_revisi_log p
             LEFT JOIN po_detail d ON d.id_detail = p.id_detail_po
             LEFT JOIN bahan_baku b ON b.id_bahan = d.id_bahan
             LEFT JOIN users u ON u.id_user = p.id_user_revisi
             WHERE p.id_po = $id AND p.qty_alokasi_material > 0
             ORDER BY p.id_revisi DESC LIMIT 5");
        ?>
        <?php if (mysqli_num_rows($rev_list) > 0): ?>
        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning text-dark"><i class="fas fa-history me-2"></i>Log Revisi & Alokasi</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php while($rv=mysqli_fetch_assoc($rev_list)): ?>
                <li class="list-group-item bg-light">
                    <div class="d-flex justify-content-between mb-1">
                        <strong class="text-danger small"><?= e($rv['nama_bahan']) ?></strong>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($rv['tanggal_revisi'])) ?></small>
                    </div>
                    <div class="small">
                        <span class="text-muted">Dialokasikan: </span> <span class="fw-bold"><?= floatval($rv['qty_alokasi_material']) ?></span> dari <?= floatval($rv['qty_diminta']) ?>
                    </div>
                    <div class="small text-muted mt-1 fst-italic">“<?= e($rv['alasan_revisi']) ?>”</div>
                    <div class="text-end mt-1"><small class="text-muted">- <?= e($rv['nama_user']) ?></small></div>
                </li>
                <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../template/footer.php'; ?>
