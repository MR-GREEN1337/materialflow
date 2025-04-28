<?php
// Create database connection
function connect_db() {
    $connection = new mysqli('tp-epua:3308', 'hachimii', 'OZ8Gybv2', 'hachimii');
    

    // Set character set
    $connection->set_charset("utf8");

        // Check connection
    if ($connection->connect_error) {
        die("Échec de la connexion à la base de données: " . $connection->connect_error);
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
        
        array_unshift($bindParams, $types);
        
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
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