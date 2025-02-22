<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    echo json_encode(["success" => false, "error" => "Unauthorized access"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['image']) && isset($data['location'])) {
    $image_data = $data['image'];
    $location = $data['location'];

    $image_path = 'uploads/' . time() . '.jpg';
    $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
    $image_data = base64_decode($image_data);

    if (file_put_contents($image_path, $image_data)) {
        $query = "INSERT INTO uploads (user_id, image_path, location) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $image_path, $location);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "location" => $location]);
        } else {
            echo json_encode(["success" => false, "error" => "Database error"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Failed to save image"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Capture</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: #000;
            z-index: 1000;
        }
        .menu {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .menu button {
            background: none;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .menu button:hover {
            opacity: 0.8;
        }
        .menu .dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: #333;
            color: white;
            border-radius: 5px;
            display: none;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .menu:hover .dropdown {
            display: flex;
        }
        .dropdown a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }
        .dropdown a:hover {
            background: #444;
        }
        .logo {
            width: 100px;
            height: auto;
        }
        .camera-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #222;
        }
        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        img {
            width: auto;
            height: auto;
            object-fit: contain;
        }
        canvas { display: none; }
        .bottom-frame {
            position: absolute;
            height: 100px;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to right, #B8860B, #000);
            padding: 15px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .buttons {
            display: flex;
            gap: 15px;
        }
        button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }
        .capture-btn { background: #ffffff; color: black; }
        .retake-btn { background: rgb(255, 255, 255); color: black; }
        .in-btn { background: rgb(255, 255, 255); color: black; }
        .out-btn { background: rgb(255, 255, 255); color: black; }
        button:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <!-- Dropdown Menu -->
        <div class="menu">
            <button>| | |</button>
            <div class="dropdown">
                <a href="camera.php">Camera</a>
                <a href="user_profile.php">Profile</a>
                <a href="user_gallery.php">Gallery</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <!-- Centered Logo -->
        <img src="images/logo.jpg" alt="Company Logo" class="mx-auto" style="width:200px;">
    </div>

    <div class="camera-container">
        <video id="video" autoplay></video>
        <canvas id="canvas"></canvas>
        <img id="photo" style="display:none;">
    </div>

    <div class="bottom-frame">
        <div class="buttons">
            <button id="capture" class="capture-btn">Capture</button>
            <button id="retake" class="retake-btn" style="display: none;">Retake</button>
            <button id="in" class="in-btn" style="display: none;">Time In</button>
            <button id="out" class="out-btn" style="display: none;">Time Out</button>
        </div>
    </div>

    <script>
        // JavaScript code remains unchanged
    </script>
</body>
</html>

    <script>
       const video = document.getElementById("video");
const canvas = document.getElementById("canvas");
const ctx = canvas.getContext("2d");
const photo = document.getElementById("photo");
const captureBtn = document.getElementById("capture");
const retakeBtn = document.getElementById("retake");
const inBtn = document.getElementById("in");
const outBtn = document.getElementById("out");

let lastPhotoData = null;
let locationData = "Fetching location...";

// Load watermark logo
const logo = new Image();
logo.src = "images/logo.jpg"; // Adjust the path as needed

// Access camera
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
    .then(stream => video.srcObject = stream)
    .catch(err => console.error("Camera access denied:", err));

// Get location info
navigator.geolocation.getCurrentPosition(
    async (position) => {
        const { latitude, longitude } = position.coords;
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`
            );
            const data = await response.json();
            locationData = data.display_name || "Unknown Location";
        } catch (error) {
            locationData = "Location fetch failed";
        }
    },
    () => locationData = "Location access denied"
);

// Capture photo
captureBtn.addEventListener("click", () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw video frame
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Add black rectangle for watermark background
    const watermarkHeight = 80; // Height of the watermark area
    ctx.fillStyle = "black";
    ctx.fillRect(0, 0, canvas.width, watermarkHeight); // Top background

    // Draw logo at the top center
    const logoWidth = 150; // Adjust size as needed
    const logoHeight = 60; // Adjust size as needed
    const logoX = (canvas.width - logoWidth) / 2; // Center horizontally
    const logoY = (watermarkHeight - logoHeight) / 2; // Center vertically in the black bar
    ctx.drawImage(logo, logoX, logoY, logoWidth, logoHeight);

    // Add text for date/time and location at the bottom
    ctx.fillStyle = "white";
    ctx.font = "15px Arial";
    const dateTime = new Date().toLocaleString();
    ctx.fillText(dateTime, 20, canvas.height - 90);
    ctx.fillText(locationData, 20, canvas.height - 50);

    // Store image data
    lastPhotoData = canvas.toDataURL("image/jpeg");
    photo.src = lastPhotoData;
    photo.style.display = "block";
    video.style.display = "none";
    captureBtn.style.display = "none";
    retakeBtn.style.display = "block";
    inBtn.style.display = "block"; // Show "Time In" button
    outBtn.style.display = "block"; // Show "Time Out" button
});


// Retake photo
retakeBtn.addEventListener("click", () => {
    photo.style.display = "none";
    video.style.display = "block";
    captureBtn.style.display = "block";
    retakeBtn.style.display = "none";
    inBtn.style.display = "none"; // Hide "Time In" button
    outBtn.style.display = "none"; // Hide "Time Out" button
});

// Upload image
function uploadImage(locationType) {
    if (!lastPhotoData) {
        alert("Please capture an image first!");
        return;
    }

    fetch("upload_image.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ image: lastPhotoData, location: locationType }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Image uploaded successfully as ${locationType}!`);
            retakeBtn.click();
        } else {
            alert("Upload failed: " + data.error);
        }
    })
    .catch(error => alert("Error uploading image."));
}

// Time In button logic
inBtn.addEventListener("click", () => uploadImage("In"));

// Time Out button logic
outBtn.addEventListener("click", () => uploadImage("Out"));

    </script>
</body>
</html>
