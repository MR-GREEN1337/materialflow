<?php
// Database configuration
/*
$db_host = 'tp-epua:3308';
$db_user = 'hachimii';
$db_password = 'OZ8Gybv2';
$db_name = 'hachimii';
*/
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'equipment_tracking';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($db_name);

// Read and execute the SQL schema
$schema_file = file_get_contents('migrations/initial_schema.sql');

// Split the SQL file into individual statements
$statements = explode(';', $schema_file);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            echo "Table created/modified successfully<br>";
        } else {
            echo "Error executing statement: " . $conn->error . "<br>";
            echo "Statement: " . $statement . "<br><br>";
        }
    }
}

echo "<br>Database setup completed.<br>";
echo "<a href='login.php'>Go to Login Page</a>";

$conn->close();
?>