<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action == 'save') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $entry_id = $_POST['entry_id'] ?? null;

    if ($entry_id) {
        $sql = "UPDATE journal_entries SET title='$title', content='$content' WHERE id='$entry_id' AND user_id='$user_id'";
    } else {
        $sql = "INSERT INTO journal_entries (user_id, title, content) VALUES ('$user_id', '$title', '$content')";
    }
    mysqli_query($conn, $sql);
} 

if ($action == 'delete') {
    $entry_id = $_POST['entry_id'];
    $sql = "DELETE FROM journal_entries WHERE id='$entry_id' AND user_id='$user_id'";
    mysqli_query($conn, $sql);
}

header("Location: dashboard.php"); // Refresh pagtapos
?>