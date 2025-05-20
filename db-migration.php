<?php
// Database configuration
require_once __DIR__ . '/config/database.php';

// Connect to database
$conn = connect_db();

// Check if equipment_images table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'equipment_images'");
$tableExists = $tableCheck->num_rows > 0;

if ($tableExists) {
    // Alter table to add s3_key column if it doesn't exist
    $columnCheck = $conn->query("SHOW COLUMNS FROM equipment_images LIKE 's3_key'");
    $columnExists = $columnCheck->num_rows > 0;
    
    if (!$columnExists) {
        $alterSql = "ALTER TABLE equipment_images ADD COLUMN s3_key VARCHAR(255) AFTER file_path";
        if ($conn->query($alterSql) === TRUE) {
            echo "Added s3_key column to equipment_images table<br>";
        } else {
            echo "Error adding s3_key column: " . $conn->error . "<br>";
        }
    } else {
        echo "s3_key column already exists<br>";
    }
} else {
    // Create equipment_images table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS equipment_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        s3_key VARCHAR(255) NOT NULL,
        caption VARCHAR(255),
        is_primary BOOLEAN DEFAULT 0,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table equipment_images created successfully with s3_key column<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

$conn->close();

echo "<br>S3 Migration completed.<br>";
echo "<a href='index.php'>Return to Dashboard</a>";
?>