<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$my_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$target_id = mysqli_real_escape_string($conn, $_POST['target_id'] ?? '');

if ($action === 'add') {
    $sql = "INSERT INTO friendships (user_id1, user_id2, status) 
            VALUES ('$my_id', '$target_id', 'pending') 
            ON DUPLICATE KEY UPDATE status='pending'";
    mysqli_query($conn, $sql);
} elseif ($action === 'accept') {
    $sql = "UPDATE friendships SET status='accepted' 
            WHERE user_id1='$target_id' AND user_id2='$my_id'";
    mysqli_query($conn, $sql);
} elseif ($action === 'remove' || $action === 'reject') {
    $sql = "DELETE FROM friendships 
            WHERE (user_id1='$my_id' AND user_id2='$target_id') 
            OR (user_id1='$target_id' AND user_id2='$my_id')";
    mysqli_query($conn, $sql);
}

echo "Success";
?>