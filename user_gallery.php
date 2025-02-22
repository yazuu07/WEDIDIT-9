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

// Fetch user's username for display
$stmt = $pdo->prepare("SELECT user FROM admin1 WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit();
}

$username = $user['user'];

// Fetch user's images with location, uploaded_at fields
$stmt = $pdo->prepare("SELECT image_path, location, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?>'s Gallery</title>
    <!-- Include Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" type="image/png" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxRJkf3Hh_OcH9AJn4SVH_EXHje0n5lJFhNw&s">

    <!-- Include Flatpickr JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/itonatocuescano/CSS/fonts.css">
    <style>
        body {
            background: linear-gradient(to right, #B8860B, #000);
            color: white;
        }
        .dropdown {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        .dropdown button {
            background-color: #333;
            color: white;
            padding: 8px 12px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .dropdown button:hover {
            background-color: #555;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #333;
            min-width: 160px;
            border-radius: 5px;
            z-index: 1;
        }
        .dropdown-content a {
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #444;
        }
        .dropdown-content a:last-child {
            border-bottom: none;
        }
        .dropdown-content a:hover {
            background-color: #555;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body class="flex">
    <!-- Dropdown Menu -->
   

    <!-- Fixed Logo -->
    <nav class="bg-stone-950 text-white fixed top-0 w-full shadow-lg z-50">
    <div class="dropdown">
        <button>Menu</button>
        <div class="dropdown-content">
            <a href="camera.php">Camera</a>
            <a href="user_profile.php">Profile</a>
            <a href="user_gallery.php">Gallery</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
        <div class="container mx-auto px-4 py-4 flex justify-center items-center">
            <div class="flex-shrink-0">
                <a href="user_gallery.php"><img src="images/logo.jpg" alt="Company Logo" class="mx-auto" style="width:200px;"></a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 p-8 mt-20">

        <!-- Calendar -->
        <div class="calendar mt-20 text-black">
            <h2>Select a Date</h2>
            <input type="date" id="date-picker">
        </div>

        <!-- Gallery Section -->
        <div class="bg-stone-200 p-6 rounded shadow text-black mt-20 z-50">
            <h2 class="text-xl font-semibold mb-4">Uploaded Images</h2>

            <?php if (!empty($images)): ?>
                <div class="grid grid-cols-2 gap-10">
                    <?php foreach (['In', 'Out'] as $status): ?>
                        <div>
                            <h3 class="text-lg font-bold mb-3"><?php echo $status; ?></h3>
                            <div class="grid grid-cols-1 gap-4">
                                <?php foreach ($images as $image): ?>
                                    <?php if ($image['location'] === $status): ?>
                                        <div class="image-container" data-uploaded-at="<?php echo explode(' ', $image['uploaded_at'])[0]; ?>">
                                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 alt="<?php echo $status; ?> Image"
                                                 onclick="openModal('<?php echo htmlspecialchars($image['image_path']); ?>')">
                                            <div class="image-overlay"><?php echo $image['uploaded_at']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No images uploaded yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Image Modal -->
    <div class="modal" id="modal">
        <button class="close-btn" onclick="closeModal()">×</button>
        <img id="modal-image" src="" alt="Full Image">
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datePicker = document.getElementById('date-picker');
            const today = new Date().toISOString().split('T')[0]; 
            datePicker.value = today; 

            // Filter images on date change
            datePicker.addEventListener('change', function() {
                const selectedDate = this.value;
                document.querySelectorAll('.image-container').forEach(image => {
                    const imageDate = image.getAttribute('data-uploaded-at');
                    image.style.display = (imageDate === selectedDate) ? 'block' : 'none';
                });
            });

            // Initialize the filter on page load
            datePicker.dispatchEvent(new Event('change'));
        });

        function openModal(imagePath) {
            document.getElementById('modal').style.display = 'flex';
            document.getElementById('modal-image').src = imagePath;
        }
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
    </script>
</body>
</html>
