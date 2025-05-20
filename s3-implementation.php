<?php
// Include database configuration and AWS SDK
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Make sure Composer and AWS SDK are installed

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Initialize S3 Client
 */
function get_s3_client() {
    // Load S3 configuration
    $s3Config = require_once __DIR__ . '/../config/s3.php';
    
    return new S3Client([
        'version' => 'latest',
        'region'  => $s3Config['region'],
        'credentials' => [
            'key'    => $s3Config['key'],
            'secret' => $s3Config['secret'],
        ]
    ]);
}

/**
 * Upload file to S3
 * 
 * @param array $file $_FILES array element
 * @param string $prefix Optional folder prefix within bucket
 * @return array Result with success status, filename, and S3 URL
 */
function upload_to_s3($file, $prefix = 'equipment/') {
    // Load S3 configuration
    $s3Config = require_once __DIR__ . '/../config/s3.php';
    $bucket = $s3Config['bucket'];
    
    // Validate file
    $file_tmp = $file['tmp_name'];
    $file_name = basename($file['name']);
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Generate unique filename
    $unique_name = uniqid() . '_' . $file_name;
    $s3_key = $prefix . $unique_name;
    
    // Check file size (10MB max)
    if ($file_size > 10000000) {
        return [
            'success' => false,
            'error' => 'File size must be less than 10MB'
        ];
    }
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_ext, $allowed_types)) {
        return [
            'success' => false,
            'error' => 'Only JPG, JPEG, PNG & GIF files are allowed'
        ];
    }
    
    // Get S3 client
    $s3 = get_s3_client();
    
    try {
        // Upload file to S3
        $result = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $s3_key,
            'SourceFile' => $file_tmp,
            'ACL'    => 'public-read', // Make publicly accessible
        ]);
        
        // Get S3 URL
        $s3_url = $result['ObjectURL'];
        
        return [
            'success' => true,
            'filename' => $unique_name,
            'path' => $s3_url,
            's3_key' => $s3_key
        ];
    } catch (AwsException $e) {
        return [
            'success' => false,
            'error' => 'S3 Upload Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete file from S3
 * 
 * @param string $s3_key The S3 key of the file to delete
 * @return array Result with success status
 */
function delete_from_s3($s3_key) {
    // Load S3 configuration
    $s3Config = require_once __DIR__ . '/../config/s3.php';
    $bucket = $s3Config['bucket'];
    
    // Get S3 client
    $s3 = get_s3_client();
    
    try {
        // Delete file from S3
        $s3->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $s3_key,
        ]);
        
        return [
            'success' => true
        ];
    } catch (AwsException $e) {
        return [
            'success' => false,
            'error' => 'S3 Delete Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Add image to equipment using S3 storage
 */
function add_equipment_image($equipment_id, $file, $caption = '', $is_primary = 0) {
    // Upload the file to S3
    $upload_result = upload_to_s3($file);
    
    if (!$upload_result['success']) {
        return [
            'success' => false,
            'message' => 'Failed to upload image: ' . $upload_result['error']
        ];
    }
    
    // If this is set as primary, reset other primary images
    if ($is_primary) {
        $resetSql = "UPDATE equipment_images SET is_primary = 0 WHERE equipment_id = ?";
        query($resetSql, [$equipment_id]);
    }
    
    // Insert image record into database
    $sql = "INSERT INTO equipment_images (equipment_id, file_name, file_path, s3_key, caption, is_primary)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $params = [
        $equipment_id,
        $upload_result['filename'],
        $upload_result['path'],
        $upload_result['s3_key'],
        $caption,
        $is_primary ? 1 : 0
    ];
    
    $result = query($sql, $params);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true,
            'image_id' => $result['insert_id'],
            'file_path' => $upload_result['path']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to save image information to database'
        ];
    }
}

/**
 * Delete equipment image from S3 and database
 */
function delete_equipment_image($image_id) {
    // Get image info first to delete the file from S3
    $sql = "SELECT file_path, s3_key FROM equipment_images WHERE id = ?";
    $result = query($sql, [$image_id]);
    
    if (empty($result)) {
        return [
            'success' => false,
            'message' => 'Image not found'
        ];
    }
    
    $s3_key = $result[0]['s3_key'];
    
    // Delete file from S3 if s3_key exists
    if (!empty($s3_key)) {
        $delete_result = delete_from_s3($s3_key);
        
        if (!$delete_result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to delete image from S3: ' . $delete_result['error']
            ];
        }
    }
    
    // Delete database record
    $deleteSql = "DELETE FROM equipment_images WHERE id = ?";
    $deleteResult = query($deleteSql, [$image_id]);
    
    if ($deleteResult['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete image record'
        ];
    }
}

/**
 * Get images for equipment
 */
function get_equipment_images($equipment_id) {
    $sql = "SELECT * FROM equipment_images WHERE equipment_id = ? ORDER BY is_primary DESC, uploaded_at DESC";
    return query($sql, [$equipment_id]);
}

/**
 * Set image as primary for equipment
 */
function set_primary_equipment_image($image_id, $equipment_id) {
    // Reset all primary images for this equipment
    $resetSql = "UPDATE equipment_images SET is_primary = 0 WHERE equipment_id = ?";
    query($resetSql, [$equipment_id]);
    
    // Set the selected image as primary
    $setPrimarySql = "UPDATE equipment_images SET is_primary = 1 WHERE id = ? AND equipment_id = ?";
    $result = query($setPrimarySql, [$image_id, $equipment_id]);
    
    if ($result['affected_rows'] === 1) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to set image as primary'
        ];
    }
}
?>