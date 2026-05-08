<?php
include 'db.php';
session_start();

// Set explicitly to JSON format so frontend parses it perfectly
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['friends' => [], 'pending' => []]);
    exit();
}

$user_id = $_SESSION['user_id'];
$friends = [];
$pending = [];

// Get Accepted Friends (Checking both sides of the friendship)
$sql_friends = "SELECT u.id, u.display_name, u.theme_color, u.user_number, u.profile_picture 
                FROM friendships f 
                JOIN users u ON (u.id = f.user_id1 OR u.id = f.user_id2) 
                WHERE (f.user_id1 = '$user_id' OR f.user_id2 = '$user_id') 
                AND f.status = 'accepted' AND u.id != '$user_id'";
$res = mysqli_query($conn, $sql_friends);

if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        // FALLBACKS: To prevent transparent icons or empty IDs in the UI
        $row['theme_color'] = !empty($row['theme_color']) ? $row['theme_color'] : "#006400";
        $row['user_number'] = !empty($row['user_number']) ? $row['user_number'] : "USR-0000";
        
        $friends[] = $row;
    }
}

// Get Pending Requests (Where YOU are the receiver / user_id2)
$sql_pending = "SELECT u.id, u.display_name, u.theme_color, u.user_number, u.profile_picture 
                FROM friendships f 
                JOIN users u ON u.id = f.user_id1 
                WHERE f.user_id2 = '$user_id' AND f.status = 'pending'";
$res2 = mysqli_query($conn, $sql_pending);

if ($res2) {
    while($row = mysqli_fetch_assoc($res2)) {
        // FALLBACKS: To prevent transparent icons or empty IDs in the UI
        $row['theme_color'] = !empty($row['theme_color']) ? $row['theme_color'] : "#006400";
        $row['user_number'] = !empty($row['user_number']) ? $row['user_number'] : "USR-0000";
        
        $pending[] = $row;
    }
}

echo json_encode(['friends' => $friends, 'pending' => $pending]);
?>