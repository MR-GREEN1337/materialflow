<?php
// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize user input
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function format_date($date) {
    if (!$date) return '';
    return date('F j, Y', strtotime($date));
}

/**
 * Get equipment status label
 */
function get_equipment_status_label($status) {
    $labels = [
        'available' => '<span class="badge status-available">Available</span>',
        'in_use' => '<span class="badge status-in-use">In Use</span>',
        'broken' => '<span class="badge status-broken">Broken</span>',
        'lost' => '<span class="badge status-lost">Lost</span>',
        'deprecated' => '<span class="badge status-deprecated">Deprecated</span>'
    ];
    
    return $labels[$status] ?? '<span class="badge">Unknown</span>';
}

/**
 * Get project status label
 */
function get_project_status_label($status) {
    $labels = [
        'ongoing' => '<span class="badge status-in-use">Ongoing</span>',
        'completed' => '<span class="badge status-available">Completed</span>',
        'archived' => '<span class="badge status-deprecated">Archived</span>'
    ];
    
    return $labels[$status] ?? '<span class="badge">Unknown</span>';
}

/**
 * Upload a file
 */
function upload_file($file, $target_dir) {
    // Check if target directory exists, create if not
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $target_file
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Failed to upload file'
        ];
    }
}

/**
 * Get file type icon
 */
function get_file_icon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'ppt' => 'fa-file-powerpoint',
        'pptx' => 'fa-file-powerpoint',
        'txt' => 'fa-file-alt',
        'zip' => 'fa-file-archive',
        'rar' => 'fa-file-archive',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'mp4' => 'fa-file-video',
        'avi' => 'fa-file-video',
        'mov' => 'fa-file-video',
        'mp3' => 'fa-file-audio',
        'wav' => 'fa-file-audio'
    ];
    
    return $icons[$extension] ?? 'fa-file';
}

/**
 * Generate a pagination
 */
function paginate($total_items, $items_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<div class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<a href="' . sprintf($url_pattern, $prev_page) . '">&laquo;</a>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination .= '<a class="active" href="#">' . $i . '</a>';
        } else {
            $pagination .= '<a href="' . sprintf($url_pattern, $i) . '">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<a href="' . sprintf($url_pattern, $next_page) . '">&raquo;</a>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}