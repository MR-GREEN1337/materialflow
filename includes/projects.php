<?php
// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Get all projects with optional filtering
 */
function get_all_projects($status = null, $search = null) {
    $sql = "SELECT * FROM projects WHERE 1=1";
    $params = [];
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $sql .= " AND (title LIKE ? OR description LIKE ? OR course_name LIKE ?)";
        $search_param = "%" . $search . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $sql .= " ORDER BY start_date DESC";
    
    return query($sql, $params);
}

/**
 * Get project by ID
 */
function get_project_by_id($id) {
    $sql = "SELECT * FROM projects WHERE id = ?";
    $result = query($sql, [$id]);
    
    return $result[0] ?? null;
}

/**
 * Add new project
 */
function add_project($data) {
    $sql = "INSERT INTO projects (title, description, start_date, end_date, 
                course_name, status, additional_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['title'],
        $data['description'],
        $data['start_date'],
        $data['end_date'],
        $data['course_name'],
        $data['status'],
        $data['additional_notes']
    ];
    
    $result = query($sql, $params);
    
    if ($result['affected_rows'] === 1) {
        $project_id = $result['insert_id'];
        
        // Add student list if provided
        if (!empty($data['student_list'])) {
            $studentSql = "INSERT INTO project_students (project_id, student_list) VALUES (?, ?)";
            query($studentSql, [$project_id, $data['student_list']]);
        }
        
        return [
            'success' => true,
            'project_id' => $project_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add project'
        ];
    }
}

/**
 * Update project
 */
function update_project($id, $data) {
    $sql = "UPDATE projects SET 
                title = ?,
                description = ?,
                start_date = ?,
                end_date = ?,
                course_name = ?,
                status = ?,
                additional_notes = ?
            WHERE id = ?";
    
    $params = [
        $data['title'],
        $data['description'],
        $data['start_date'],
        $data['end_date'],
        $data['course_name'],
        $data['status'],
        $data['additional_notes'],
        $id
    ];
    
    $result = query($sql, $params);
    
    // Update student list if provided
    if (isset($data['student_list'])) {
        // Check if student list exists
        $studentCheckSql = "SELECT id FROM project_students WHERE project_id = ?";
        $studentCheck = query($studentCheckSql, [$id]);
        
        if (count($studentCheck) > 0) {
            // Update existing student list
            $studentUpdateSql = "UPDATE project_students SET student_list = ? WHERE project_id = ?";
            query($studentUpdateSql, [$data['student_list'], $id]);
        } else {
            // Insert new student list
            $studentInsertSql = "INSERT INTO project_students (project_id, student_list) VALUES (?, ?)";
            query($studentInsertSql, [$id, $data['student_list']]);
        }
    }
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'No changes were made or project not found'
        ];
    }
}

/**
 * Delete project
 */
function delete_project($id) {
    // Check if equipment is in use
    $checkSql = "SELECT COUNT(*) AS count FROM project_equipment 
                WHERE project_id = ? AND return_date IS NULL";
    $checkResult = query($checkSql, [$id]);
    
    if ($checkResult[0]['count'] > 0) {
        return [
            'success' => false,
            'message' => 'Cannot delete project with equipment checked out. Please return all equipment first.'
        ];
    }
    
    $sql = "DELETE FROM projects WHERE id = ?";
    $result = query($sql, [$id]);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete project or project not found'
        ];
    }
}

/**
 * Get equipment used in a project
 */
function get_project_equipment($project_id) {
    $sql = "SELECT pe.id, e.id AS equipment_id, e.name, e.description, 
                pe.checkout_date, pe.return_date, pe.status_on_return, pe.notes
            FROM project_equipment pe
            JOIN equipment e ON pe.equipment_id = e.id
            WHERE pe.project_id = ?
            ORDER BY pe.checkout_date DESC";
    
    return query($sql, [$project_id]);
}

/**
 * Get project resources
 */
function get_project_resources($project_id) {
    $sql = "SELECT * FROM project_resources WHERE project_id = ? ORDER BY created_at DESC";
    
    return query($sql, [$project_id]);
}

/**
 * Add project resource
 */
function add_project_resource($data) {
    $sql = "INSERT INTO project_resources (project_id, resource_type, title, description, 
                file_path, external_url, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['project_id'],
        $data['resource_type'],
        $data['title'],
        $data['description'],
        $data['file_path'],
        $data['external_url'],
        $data['uploaded_by']
    ];
    
    $result = query($sql, $params);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true,
            'resource_id' => $result['insert_id']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add resource'
        ];
    }
}

/**
 * Delete project resource
 */
function delete_project_resource($id) {
    // Get resource info to delete file if exists
    $resourceSql = "SELECT file_path FROM project_resources WHERE id = ?";
    $resourceResult = query($resourceSql, [$id]);
    
    if (!empty($resourceResult) && !empty($resourceResult[0]['file_path'])) {
        $filePath = $resourceResult[0]['file_path'];
        
        // Check if file exists and delete it
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    $sql = "DELETE FROM project_resources WHERE id = ?";
    $result = query($sql, [$id]);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete resource or resource not found'
        ];
    }
}

/**
 * Get project students
 */
function get_project_students($project_id) {
    $sql = "SELECT student_list FROM project_students WHERE project_id = ?";
    $result = query($sql, [$project_id]);
    
    return $result[0]['student_list'] ?? '';
}