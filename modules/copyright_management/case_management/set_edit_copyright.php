<?php
session_start();
include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['copyright_id'])) {
    $copyright_id = intval($_POST['copyright_id']);
    if ($copyright_id > 0) {
        $_SESSION['edit_copyright_id'] = $copyright_id;
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
