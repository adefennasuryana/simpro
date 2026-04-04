<?php
/**
 * Penerimaan: get_items_po.php — AJAX endpoint
 * Returns JSON: items dari PO dengan status penerimaan
 */
session_start();
require_once '../config/koneksi.php';
cek_login();

header('Content-Type: application/json');

$id_po = (int)($_GET['id_po'] ?? 0);
if ($id_po === 0) { echo json_encode([]); exit; }

$result = mysqli_query($koneksi,
    "SELECT * FROM v_po_item_status WHERE id_po = $id_po ORDER BY id_detail");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
echo json_encode($data);
