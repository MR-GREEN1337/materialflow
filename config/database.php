<?php
// Database connection configuration
define('DB_HOST', 'localhost'); // Change to your MySQL host
define('DB_USER', 'root');      // Change to your MySQL username
define('DB_PASS', '');          // Change to your MySQL password
define('DB_NAME', 'equipment_tracking');  // Database name

// Create database connection
function connect_db() {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    // Set character set
    $connection->set_charset("utf8mb4");
    
    return $connection;
}

// Function to execute queries with error handling
function query($sql, $params = []) {
    $conn = connect_db();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    
    // Bind parameters if they exist
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Determine parameter types
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $bindParams[] = $param;
        }
        
        // Add types as first element
        array_unshift($bindParams, $types);
        
        // Bind parameters
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    // Execute statement
    $result = $stmt->execute();
    
    if (!$result) {
        die("Query execution failed: " . $stmt->error);
    }
    
    // Get result if it's a SELECT query
    if (stripos(trim($sql), 'SELECT') === 0) {
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        return $data;
    } else {
        $affectedRows = $stmt->affected_rows;
        $insertId = $stmt->insert_id;
        
        $stmt->close();
        $conn->close();
        
        return [
            'affected_rows' => $affectedRows,
            'insert_id' => $insertId
        ];
    }
}