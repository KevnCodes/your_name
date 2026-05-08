<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$display_name = $_POST['display_name'] ?? '';
$mood = $_POST['mood'] ?? '';
$bio = $_POST['bio'] ?? '';
$base64_image = $_POST['profile_picture_base64'] ?? '';

// Check if the compressed preview image string reached PHP
if (!empty($base64_image) && strpos($base64_image, 'data:image') === 0) {
    
    $stmt = $conn->prepare("UPDATE users SET display_name = ?, mood = ?, bio = ?, profile_picture = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $display_name, $mood, $bio, $base64_image, $user_id);
    $stmt->execute();
    $stmt->close();
    
} else {
    
    $stmt = $conn->prepare("UPDATE users SET display_name = ?, mood = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("sssi", $display_name, $mood, $bio, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php");
exit();
?>