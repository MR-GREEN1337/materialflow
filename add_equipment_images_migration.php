<?php
// Database configuration
require_once __DIR__ . '/config/database.php';

// Connect to database
$conn = connect_db();

// Create equipment_images table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS equipment_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255),
    is_primary BOOLEAN DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table equipment_images created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create uploads directory if it doesn't exist
$uploads_dir = __DIR__ . '/uploads/equipment';
if (!file_exists($uploads_dir)) {
    if (mkdir($uploads_dir, 0755, true)) {
        echo "Created uploads directory at: $uploads_dir<br>";
    } else {
        echo "Failed to create uploads directory at: $uploads_dir<br>";
    }
}

$conn->close();

echo "<br>Migration completed.<br>";
echo "<a href='index.php'>Return to Dashboard</a>";
?>