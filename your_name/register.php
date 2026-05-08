<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $display_name = mysqli_real_escape_string($conn, $_POST['display_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side validation
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[^a-zA-Z0-9]/", $password)) {
        echo "<script>alert('Password does not meet requirements!'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (display_name, email, password) VALUES ('$display_name', '$email', '$hashed_password')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
    } else {
        // Check if email already exists
        if (mysqli_errno($conn) == 1062) {
            echo "<script>alert('Email already registered.'); window.history.back();</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>