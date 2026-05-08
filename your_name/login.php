<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Clean the input to prevent SQL Injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 2. Check if the email exists
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // 3. Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Success! Store user info in the Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['display_name'] = $user['display_name'];
            
            // REDIRECT TO DASHBOARD
            echo "<script>alert('Welcome back, " . $user['display_name'] . "!'); window.location.href='dashboard.php';</script>";
        } else {
            // Wrong password - Stay on index
            echo "<script>alert('Invalid password.'); window.location.href='index.php';</script>";
        }
    } else {
        // Email not found - Stay on index
        echo "<script>alert('No account found with that email.'); window.location.href='index.php';</script>";
    }
}
?>