<?php
// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Get all equipment with optional filtering
 */
function get_all_equipment($status = null, $search = null) {
    $sql = "SELECT * FROM equipment WHERE 1=1";
    $params = [];
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $sql .= " AND (name LIKE ? OR description LIKE ? OR vendor LIKE ? OR storage_location LIKE ?)";
        $search_param = "%" . $search . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $sql .= " ORDER BY name ASC";
    
    return query($sql, $params);
}

/**
 * Get equipment by ID
 */
function get_equipment_by_id($id) {
    $sql = "SELECT * FROM equipment WHERE id = ?";
    $result = query($sql, [$id]);
    
    return $result[0] ?? null;
}

/**
 * Add new equipment
 */
function add_equipment($data) {
    $sql = "INSERT INTO equipment (name, description, purchase_date, purchase_price, 
                vendor, vendor_url, documentation_url, technical_specs, status, 
                storage_location, additional_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['name'],
        $data['description'],
        $data['purchase_date'],
        $data['purchase_price'],
        $data['vendor'],
        $data['vendor_url'],
        $data['documentation_url'],
        $data['technical_specs'],
        $data['status'],
        $data['storage_location'],
        $data['additional_notes']
    ];
    
    $result = query($sql, $params);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true,
            'equipment_id' => $result['insert_id']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add equipment'
        ];
    }
}

/**
 * Update equipment
 */
function update_equipment($id, $data) {
    $sql = "UPDATE equipment SET 
                name = ?,
                description = ?,
                purchase_date = ?,
                purchase_price = ?,
                vendor = ?,
                vendor_url = ?,
                documentation_url = ?,
                technical_specs = ?,
                status = ?,
                storage_location = ?,
                additional_notes = ?
            WHERE id = ?";
    
    $params = [
        $data['name'],
        $data['description'],
        $data['purchase_date'],
        $data['purchase_price'],
        $data['vendor'],
        $data['vendor_url'],
        $data['documentation_url'],
        $data['technical_specs'],
        $data['status'],
        $data['storage_location'],
        $data['additional_notes'],
        $id
    ];
    
    $result = query($sql, $params);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'No changes were made or equipment not found'
        ];
    }
}

/**
 * Delete equipment
 */
function delete_equipment($id) {
    // Check if equipment is in use
    $checkSql = "SELECT COUNT(*) AS count FROM project_equipment 
                WHERE equipment_id = ? AND return_date IS NULL";
    $checkResult = query($checkSql, [$id]);
    
    if ($checkResult[0]['count'] > 0) {
        return [
            'success' => false,
            'message' => 'Cannot delete equipment that is currently in use by a project'
        ];
    }
    
    $sql = "DELETE FROM equipment WHERE id = ?";
    $result = query($sql, [$id]);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete equipment or equipment not found'
        ];
    }
}

/**
 * Get projects using a specific equipment item
 */
function get_projects_using_equipment($equipment_id) {
    $sql = "SELECT p.id, p.title, p.status, pe.checkout_date, pe.return_date
            FROM projects p
            JOIN project_equipment pe ON p.id = pe.project_id
            WHERE pe.equipment_id = ?
            ORDER BY pe.checkout_date DESC";
    
    return query($sql, [$equipment_id]);
}

/**
 * Checkout equipment for a project
 */
function checkout_equipment($project_id, $equipment_id, $checkout_date, $notes = null) {
    // Check if equipment is available
    $equipmentSql = "SELECT status FROM equipment WHERE id = ?";
    $equipmentResult = query($equipmentSql, [$equipment_id]);
    
    if (empty($equipmentResult) || $equipmentResult[0]['status'] !== 'available') {
        return [
            'success' => false,
            'message' => 'Equipment is not available for checkout'
        ];
    }
    
    // Begin transaction
    $conn = connect_db();
    $conn->begin_transaction();
    
    try {
        // Insert into project_equipment
        $insertSql = "INSERT INTO project_equipment (project_id, equipment_id, checkout_date, notes)
                    VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param('iiss', $project_id, $equipment_id, $checkout_date, $notes);
        $stmt->execute();
        
        // Update equipment status
        $updateSql = "UPDATE equipment SET status = 'in_use' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('i', $equipment_id);
        $updateStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => 'Failed to checkout equipment: ' . $e->getMessage()
        ];
    } finally {
        $conn->close();
    }
}

/**
 * Return equipment from a project
 */
function return_equipment($project_equipment_id, $return_date, $status_on_return = null, $notes = null) {
    // Get project equipment record
    $getPESql = "SELECT pe.equipment_id, pe.return_date
                FROM project_equipment pe
                WHERE pe.id = ?";
    $peResult = query($getPESql, [$project_equipment_id]);
    
    if (empty($peResult) || $peResult[0]['return_date'] !== null) {
        return [
            'success' => false,
            'message' => 'Equipment not found or already returned'
        ];
    }
    
    $equipment_id = $peResult[0]['equipment_id'];
    
    // Begin transaction
    $conn = connect_db();
    $conn->begin_transaction();
    
    try {
        // Update project_equipment record
        $updatePESql = "UPDATE project_equipment 
                      SET return_date = ?, status_on_return = ?, notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                      WHERE id = ?";
        $stmt = $conn->prepare($updatePESql);
        $stmt->bind_param('sssi', $return_date, $status_on_return, $notes, $project_equipment_id);
        $stmt->execute();
        
        // Update equipment status back to available
        $updateEquipSql = "UPDATE equipment SET status = 'available' WHERE id = ?";
        $updateEquipStmt = $conn->prepare($updateEquipSql);
        $updateEquipStmt->bind_param('i', $equipment_id);
        $updateEquipStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => 'Failed to return equipment: ' . $e->getMessage()
        ];
    } finally {
        $conn->close();
    }
}