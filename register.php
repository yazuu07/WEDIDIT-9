<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $contact_number = $_POST['contact_number'];

    try {
        $stmt = $pdo->prepare("INSERT INTO admin1 (user, email, password, contact_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user, $email, $password, $contact_number]);
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <link rel="stylesheet" href="/itonatocuescano/CSS/d1login.css">
    <link rel="stylesheet" href="/itonatocuescano/CSS/fonts.css">
    <link rel="icon" type="image/png" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxRJkf3Hh_OcH9AJn4SVH_EXHje0n5lJFhNw&s">

</head>
<body>
    <div class="login-container">
        <form action="register.php" method="post">
            <h2>Create an account</h2>
            <?php if (isset($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
            <input type="text" name="user" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="contact_number" placeholder="Contact Number" required>
            <button type="submit">Create</button>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </form>
    </div>
</body>
</html>
