<?php
/**
 * Monitoring Operasional: functions.php
 * Berisi fungsi-fungsi spesifik untuk pelaporan operasional 
 */

/**
 * Mengambil data gabungan tracking dari Permintaan -> PO -> Penerimaan
 */
function get_monitoring_data($koneksi, $filter) {
    $where = ["pb.status_permintaan IN ('Selesai', 'Terpenuhi Sebagian', 'Diproses ke PO')"];
    
    // Filter Proyek
    if (!empty($filter['id_proyek'])) {
        $where[] = "pb.id_proyek = " . (int)$filter['id_proyek'];
    }

    // Filter Tanggal
    if ($filter['jenis_periode'] === 'harian' && !empty($filter['tanggal'])) {
        $date = mysqli_real_escape_string($koneksi, $filter['tanggal']);
        $where[] = "DATE(pb.tanggal_permintaan) = '$date'";
    } elseif ($filter['jenis_periode'] === 'bulanan' && !empty($filter['bulan']) && !empty($filter['tahun'])) {
        $bln = (int)$filter['bulan'];
        $thn = (int)$filter['tahun'];
        $where[] = "MONTH(pb.tanggal_permintaan) = $bln AND YEAR(pb.tanggal_permintaan) = $thn";
    }

    $where_sql = implode(' AND ', $where);

    // Kueri utama (Pelacakan menyeluruh per item proyek)
    $sql = "SELECT 
                pr.nama_proyek, pr.kode_proyek,
                pb.nomor_permintaan, pb.tanggal_permintaan,
                b.kode_bahan, b.nama_bahan, b.satuan,
                pbd.qty_diminta AS req_diminta,
                pbd.qty_alokasi_material AS req_alokasi,
                pbd.qty_po AS req_po,
                pbd.qty_terpenuhi AS req_terpenuhi,
                pbd.qty_sisa AS req_sisa,
                po.nomor_po, po.status_po
            FROM permintaan_bahan_detail pbd
            JOIN permintaan_bahan pb ON pb.id_permintaan = pbd.id_permintaan
            JOIN proyek pr ON pr.id_proyek = pb.id_proyek
            JOIN bahan_baku b ON b.id_bahan = pbd.id_bahan
            LEFT JOIN po ON po.id_po = pb.id_po
            WHERE $where_sql
            ORDER BY pr.nama_proyek ASC, pb.tanggal_permintaan DESC, b.nama_bahan ASC";

    $res = mysqli_query($koneksi, $sql);
    $data = [];
    if($res) {
        while($r = mysqli_fetch_assoc($res)) {
            $data[] = $r;
        }
    }
    return $data;
}

/**
 * Mengambil data Pembayaran Upah Harian Tenaga Kerja
 */
function get_monitoring_upah($koneksi, $filter) {
    $where = ["u.status_pembayaran IN ('Disetujui', 'Dibayar')"];
    
    if (!empty($filter['id_proyek'])) {
        $where[] = "u.id_proyek = " . (int)$filter['id_proyek'];
    }

    if ($filter['jenis_periode'] === 'harian' && !empty($filter['tanggal'])) {
        $date = mysqli_real_escape_string($koneksi, $filter['tanggal']);
        $where[] = "DATE(u.tanggal_pembayaran) = '$date'";
    } elseif ($filter['jenis_periode'] === 'bulanan' && !empty($filter['bulan']) && !empty($filter['tahun'])) {
        $bln = (int)$filter['bulan'];
        $thn = (int)$filter['tahun'];
        $where[] = "MONTH(u.tanggal_pembayaran) = $bln AND YEAR(u.tanggal_pembayaran) = $thn";
    }

    $where_sql = implode(' AND ', $where);

    $sql = "SELECT 
                p.kode_proyek, p.nama_proyek,
                u.nomor_pembayaran, u.tanggal_pembayaran, u.periode_dari, u.periode_sampai,
                u.total_pembayaran, u.status_pembayaran
            FROM pembayaran_upah u
            JOIN proyek p ON p.id_proyek = u.id_proyek
            WHERE $where_sql
            ORDER BY p.nama_proyek ASC, u.tanggal_pembayaran DESC";

    $res = mysqli_query($koneksi, $sql);
    $data = [];
    if($res) {
        while($r = mysqli_fetch_assoc($res)) {
            $data[] = $r;
        }
    }
    return $data;
}
