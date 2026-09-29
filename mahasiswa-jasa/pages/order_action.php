<?php
/** Handler POST untuk mengubah status pesanan (penyedia: terima/selesai/tolak, pembeli: batal). */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/order_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/dashboard.php');
}
verifyCsrf();

$orderId = (int) ($_POST['order_id'] ?? 0);
$action  = $_POST['action'] ?? '';
$back    = ($_POST['back'] ?? '') === 'my_orders' ? 'pages/my_orders.php' : 'pages/orders_incoming.php';

if ($action === 'batal') {
    $ok = buyerCancelOrder($orderId, currentUserId());
    $msg = 'Pesanan dibatalkan.';
    $back = 'pages/my_orders.php';
} else {
    $ok = providerUpdateOrder($orderId, currentUserId(), $action);
    $msg = ['terima' => 'Pesanan diterima dan sedang diproses.', 'selesai' => 'Pesanan ditandai selesai.', 'tolak' => 'Pesanan ditolak.'][$action] ?? '';
}

if ($ok) {
    setFlash('success', $msg);
} else {
    setFlash('danger', 'Aksi tidak dapat dilakukan. Status pesanan mungkin sudah berubah.');
}
redirect($back);
