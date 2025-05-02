<?php
// Create database connection
function connect_db() {
    $db_host = 'tp-epua:3308';
    $db_user = 'hachimii';
    $db_password = 'OZ8Gybv2';
    $db_name = 'hachimii';
    
    // Create connection
    $connection = new mysqli($db_host, $db_user, $db_password, $db_name );
    
    // Set character set
    $connection->set_charset("utf8");

    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    return $connection;
}

// Function to execute queries with error handling
function query($sql, $params = []) {
    $conn = connect_db();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Build the types string first
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
        
        // Convert params to references for bind_param
        $bindParamRefs = [];
        $bindParamRefs[] = $types;
        
        for ($i = 0; $i < count($bindParams); $i++) {
            // This creates a reference to each element
            $bindParamRefs[] = &$bindParams[$i];
        }
        
        // Call bind_param with references
        call_user_func_array([$stmt, 'bind_param'], $bindParamRefs);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        die("Query execution failed: " . $stmt->error);
    }
    
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