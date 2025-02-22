<?php
session_start();
require 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details from the session
$user_id = $_SESSION['user_id'];

// Fetch user's current data
$stmt = $pdo->prepare("SELECT user, email, contact_number, photo FROM admin1 WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $password = trim($_POST['password']);

    // Update photo if provided
    if (!empty($_FILES['photo']['name'])) {
        $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoPath = 'uploads/profile_' . $user_id . '_' . time() . '.' . $photo_ext;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
            // Delete old photo if it exists
            if (!empty($user['photo']) && file_exists($user['photo'])) {
                unlink($user['photo']);
            }
        }
    } else {
        $photoPath = $user['photo']; // Keep current photo
    }

    // Hash password if provided
    $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    // Build SQL query dynamically
    $query = "UPDATE admin1 SET user = ?, email = ?, contact_number = ?, photo = ?";
    $params = [$name, $email, $contact_number, $photoPath];

    if ($passwordHash) {
        $query .= ", password = ?";
        $params[] = $passwordHash;
    }

    $query .= " WHERE id = ?";
    $params[] = $user_id;

    // Execute update query
    $stmt = $pdo->prepare($query);
    if ($stmt->execute($params)) {
        $_SESSION['success'] = "Profile updated successfully.";
        header("Location: user_profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to right, #B8860B, #000);
            color: black;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-black shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Dropdown Menu -->
            <div class="relative">
                <button id="menuButton" class="inline-flex items-center justify-center text-gray-600 hover:text-black focus:outline-none">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div id="menuDropdown" class="absolute left-0 mt-2 w-48 bg-gray-700  text-white border rounded shadow-lg hidden">
                    <a href="camera.php" class="block px-4 py-2 text-white hover:bg-gray-400">Camera</a>
                    <a href="user_gallery.php" class="block px-4 py-2 text-white hover:bg-gray-400">Gallery</a>
                    <a href="user_profile.php" class="block px-4 py-2 text-white hover:bg-gray-400">Profile</a>
                    <a href="logout.php" class="block px-4 py-2 text-white hover:bg-gray-400">Logout</a>
                </div>
            </div>

            <!-- Logo in the Center -->
            <div>
                <a href="camera.php">
                    <img src="images/logo.jpg" alt="Logo" class="h-10">
                </a>
            </div>

            <!-- Placeholder for alignment -->
            <div class="hidden md:block w-6"></div>
        </div>
    </div>
</nav>

    <!-- Profile Content -->
    <div class="container">
        <h1 class="text-center text-2xl font-bold mb-4">Profile</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="text-red-500 mb-4"> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?> </div>
        <?php elseif (isset($_SESSION['success'])): ?>
            <div class="text-green-500 mb-4"> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?> </div>
        <?php endif; ?>

        <div class="text-center">
            <img src="<?= htmlspecialchars($user['photo'] ?: 'default-profile.png') ?>" alt="Profile Photo" class="profile-photo">
        </div>

        <div class="profile-detail">
            <h1><label>Name:</label></h1>
            <span><?= htmlspecialchars($user['user']); ?></span>
        </div>

        <div class="profile-detail">
            <h2><label>Email:</label></h2>
            <span><?= htmlspecialchars($user['email']); ?></span>
        </div>

        <div class="profile-detail">
            <h2><label>Contact No:</label></h2>
            <span><?= htmlspecialchars($user['contact_number']); ?></span>
        </div>

        <div class="edit-button mt-4 text-center">
            <button onclick="window.location.href='edit_profile.php'" style="background-color: #B8860B;" class="px-4 py-2 rounded text-white">Edit Profile</button>
        </div>
    </div>
    <script>
document.getElementById("menuButton").addEventListener("click", function () {
    var dropdown = document.getElementById("menuDropdown");
    dropdown.classList.toggle("hidden");
});

// Close dropdown when clicking outside
document.addEventListener("click", function (event) {
    var dropdown = document.getElementById("menuDropdown");
    var button = document.getElementById("menuButton");

    if (!dropdown.contains(event.target) && !button.contains(event.target)) {
        dropdown.classList.add("hidden");
    }
});
</script>
</body>
</html>
