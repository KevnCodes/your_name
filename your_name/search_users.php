<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$my_id = $_SESSION['user_id'];
$q = mysqli_real_escape_string($conn, $_GET['q'] ?? '');

// We use LIKE '%%' when empty, which returns everyone. Limited to 50 for performance.
$sql = "SELECT id, display_name, theme_color, user_number FROM users 
        WHERE id != '$my_id' AND display_name LIKE '%$q%' LIMIT 50";
$res = mysqli_query($conn, $sql);
$results = [];

if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        $target_id = $row['id'];
        // Check friendship status
        $check = mysqli_query($conn, "SELECT status, user_id1 FROM friendships 
                                      WHERE (user_id1='$my_id' AND user_id2='$target_id') 
                                      OR (user_id1='$target_id' AND user_id2='$my_id')");
        if(mysqli_num_rows($check) > 0) {
            $f = mysqli_fetch_assoc($check);
            if($f['status'] == 'accepted') {
                $row['friend_status'] = 'friends';
            } else if($f['user_id1'] == $my_id) {
                $row['friend_status'] = 'pending_sent';
            } else {
                $row['friend_status'] = 'pending_received';
            }
        } else {
            $row['friend_status'] = 'none';
        }
        $results[] = $row;
    }
}

echo json_encode($results);
?>