<?php
// handles sticker and theme saves via post requests
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit('unauthorized');
}

$user_id = $_SESSION['user_id'];

// FOR SAVING STICKERS
if (isset($_POST['action']) && $_POST['action'] === 'save_stickers' && isset($_POST['stickers'])) {
    $stickers_data = json_decode($_POST['stickers'], true);
    mysqli_query($conn, "DELETE FROM user_stickers WHERE user_id = '$user_id'");
    if(is_array($stickers_data)) {
        foreach($stickers_data as $st) {
            $url = mysqli_real_escape_string($conn, $st['url'] ?? '');
            $x = mysqli_real_escape_string($conn, $st['x'] ?? '');
            $y = mysqli_real_escape_string($conn, $st['y'] ?? '');
            $size = mysqli_real_escape_string($conn, $st['size'] ?? '');
            mysqli_query($conn, "INSERT INTO user_stickers (user_id, sticker_url, pos_x, pos_y, size) VALUES ('$user_id', '$url', '$x', '$y', '$size')");
        }
    }
    exit('saved');
}

// FOR SAVING THEME/TEXTURE
if (isset($_POST['action']) && $_POST['action'] === 'save_theme') {
    $color = mysqli_real_escape_string($conn, $_POST['theme_color']);
    $texture = mysqli_real_escape_string($conn, $_POST['bg_texture']);
    mysqli_query($conn, "UPDATE users SET theme_color = '$color', bg_texture = '$texture' WHERE id = '$user_id'");
    exit('saved');
}
?>