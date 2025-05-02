<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/projects.php';
require_once '../includes/equipment.php';

// Require login for this page
require_login();

// Get action and ID
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$project_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Check permissions for edit/add
if (($action === 'edit' || $action === 'add') && !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to perform this action.';
    header('Location: project_list.php');
    exit;
}

// Initialize variables
$project = null;
$project_equipment = [];
$project_resources = [];
$student_list = '';
$errors = [];
$success_message = '';
$error_message = '';

// Check if we have a session message
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Load project data if ID is provided
if ($project_id && $action !== 'add') {
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        $_SESSION['error_message'] = 'Project not found.';
        header('Location: project_list.php');
        exit;
    }
    
    // Get project equipment
    $project_equipment = get_project_equipment($project_id);
    
    // Get project resources
    $project_resources = get_project_resources($project_id);
    
    // Get student list
    $student_list = get_project_students($project_id);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle project CRUD
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_project' || $_POST['action'] === 'edit_project') {
            // Validate form data
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $start_date = trim($_POST['start_date'] ?? '');
            $end_date = trim($_POST['end_date'] ?? '');
            $course_name = trim($_POST['course_name'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $additional_notes = trim($_POST['additional_notes'] ?? '');
            $student_list = trim($_POST['student_list'] ?? '');
            
            // Validate required fields
            if (empty($title)) {
                $errors[] = 'Project title is required.';
            }
            
            if (empty($status)) {
                $errors[] = 'Status is required.';
            }
            
            // If no errors, process the form
            if (empty($errors)) {
                $data = [
                    'title' => $title,
                    'description' => $description,
                    'start_date' => $start_date ?: null,
                    'end_date' => $end_date ?: null,
                    'course_name' => $course_name,
                    'status' => $status,
                    'additional_notes' => $additional_notes,
                    'student_list' => $student_list
                ];
                
                if ($_POST['action'] === 'add_project') {
                    // Add new project
                    $result = add_project($data);
                    
                    if ($result['success']) {
                        $_SESSION['success_message'] = 'Project added successfully.';
                        header('Location: project_detail.php?id=' . $result['project_id']);
                        exit;
                    } else {
                        $error_message = $result['message'] ?? 'Failed to add project.';
                    }
                } elseif ($_POST['action'] === 'edit_project' && $project_id) {
                    // Update project
                    $result = update_project($project_id, $data);
                    
                    if ($result['success']) {
                        $_SESSION['success_message'] = 'Project updated successfully.';
                        header('Location: project_detail.php?id=' . $project_id);
                        exit;
                    } else {
                        $error_message = $result['message'] ?? 'Failed to update project.';
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete_project' && $project_id && is_admin()) {
            // Delete project
            $result = delete_project($project_id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Project deleted successfully.';
                header('Location: project_list.php');
                exit;
            } else {
                $error_message = $result['message'] ?? 'Failed to delete project.';
            }
        } elseif ($_POST['action'] === 'add_resource' && $project_id) {
            // Add resource
            $title = trim($_POST['resource_title'] ?? '');
            $description = trim($_POST['resource_description'] ?? '');
            $resource_type = $_POST['resource_type'] ?? '';
            $external_url = trim($_POST['external_url'] ?? '');
            $file_path = '';
            
            // Validate required fields
            if (empty($title)) {
                $errors[] = 'Resource title is required.';
            }
            
            if (empty($resource_type)) {
                $errors[] = 'Resource type is required.';
            }
            
            // Handle file upload if there is one
            if (isset($_FILES['resource_file']) && $_FILES['resource_file']['size'] > 0) {
                $target_dir = "../../upload_tmp/";
                $upload_result = upload_file($_FILES['resource_file'], $target_dir);
                
                if ($upload_result['success']) {
                    $file_path = $upload_result['path'];
                } else {
                    $errors[] = 'Failed to upload file: ' . $upload_result['error'];
                }
            } elseif (empty($external_url) && $resource_type !== 'other') {
                $errors[] = 'Please provide either a file or an external URL.';
            }
            
            if (empty($errors)) {
                $data = [
                    'project_id' => $project_id,
                    'resource_type' => $resource_type,
                    'title' => $title,
                    'description' => $description,
                    'file_path' => $file_path,
                    'external_url' => $external_url,
                    'uploaded_by' => $_SESSION['user_id'] ?? null
                ];
                
                $result = add_project_resource($data);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Resource added successfully.';
                    header('Location: project_detail.php?id=' . $project_id);
                    exit;
                } else {
                    $error_message = $result['message'] ?? 'Failed to add resource.';
                }
            }
        } elseif ($_POST['action'] === 'delete_resource' && isset($_POST['resource_id']) && is_admin()) {
            // Delete resource
            $resource_id = intval($_POST['resource_id']);
            $result = delete_project_resource($resource_id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Resource deleted successfully.';
                header('Location: project_detail.php?id=' . $project_id);
                exit;
            } else {
                $error_message = $result['message'] ?? 'Failed to delete resource.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'add' ? 'Add New Project' : ($action === 'edit' ? 'Edit Project' : 'Project Details'); ?> - Equipment Tracking System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">
                    <?php if ($action === 'add'): ?>
                        Add New Project
                    <?php elseif ($action === 'edit'): ?>
                        Edit Project: <?php echo htmlspecialchars($project['title']); ?>
                    <?php elseif ($action === 'delete'): ?>
                        Delete Project: <?php echo htmlspecialchars($project['title']); ?>
                    <?php else: ?>
                        Project Details: <?php echo htmlspecialchars($project['title']); ?>
                    <?php endif; ?>
                </h1>
                
                <?php if ($action === 'view' && $project_id): ?>
                    <div class="page-actions">
                        <?php if (is_admin()): ?>
                            <a href="project_detail.php?id=<?php echo $project_id; ?>&action=edit" class="btn-secondary">Edit Project</a>
                            <a href="#" onclick="confirmDelete(<?php echo $project_id; ?>)" class="btn-danger">Delete Project</a>
                            <a href="checkout_equipment.php?project_id=<?php echo $project_id; ?>" class="btn-warning">Checkout Equipment</a>
                        <?php endif; ?>
                        <a href="project_list.php" class="btn-primary">Back to List</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'delete'): ?>
                <!-- Delete confirmation -->
                <div class="card">
                    <h2 class="card-title">Confirm Deletion</h2>
                    <p>Are you sure you want to delete this project? This action cannot be undone.</p>
                    
                    <?php if (!empty($project_equipment)): ?>
                        <div class="error-message">
                            <p>This project has equipment associated with it. Deleting it will remove all equipment usage records.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="project_detail.php?id=<?php echo $project_id; ?>">
                        <input type="hidden" name="action" value="delete_project">
                        <div class="form-actions">
                            <button type="submit" class="btn-danger">Confirm Delete</button>
                            <a href="project_detail.php?id=<?php echo $project_id; ?>" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit form -->
                <div class="card">
                    <form method="post" action="<?php echo $action === 'add' ? 'project_detail.php?action=add' : 'project_detail.php?id=' . $project_id . '&action=edit'; ?>">
                        <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'add_project' : 'edit_project'; ?>">
                        
                        <div class="form-group">
                            <label for="title">Project Title <span class="required">*</span></label>
                            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($project['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($project['start_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($project['end_date'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_name">Course/Program</label>
                            <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($project['course_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="ongoing" <?php echo isset($project['status']) && $project['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo isset($project['status']) && $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="archived" <?php echo isset($project['status']) && $project['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="student_list">Student List (comma separated)</label>
                            <textarea id="student_list" name="student_list" rows="3" placeholder="John Doe, Jane Smith, etc."><?php echo htmlspecialchars($student_list ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="additional_notes">Additional Notes</label>
                            <textarea id="additional_notes" name="additional_notes" rows="4"><?php echo htmlspecialchars($project['additional_notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><?php echo $action === 'add' ? 'Add Project' : 'Update Project'; ?></button>
                            <a href="<?php echo $action === 'add' ? 'project_list.php' : 'project_detail.php?id=' . $project_id; ?>" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- View mode -->
                <div class="card">
                    <div class="detail-section">
                        <h3>Project Information</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value"><?php echo get_project_status_label($project['status']); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Description:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($project['description'] ?? 'N/A')); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Course/Program:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($project['course_name'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Start Date:</div>
                            <div class="detail-value"><?php echo $project['start_date'] ? format_date($project['start_date']) : 'N/A'; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">End Date:</div>
                            <div class="detail-value"><?php echo $project['end_date'] ? format_date($project['end_date']) : 'N/A'; ?></div>
                        </div>
                        
                        <?php if (!empty($student_list)): ?>
                        <div class="detail-row">
                            <div class="detail-label">Students:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($student_list)); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($project['additional_notes'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Additional Notes:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($project['additional_notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Equipment section -->
                <div class="card">
                    <div class="section-header">
                        <h3>Project Equipment</h3>
                        <?php if (is_admin() && $project['status'] === 'ongoing'): ?>
                            <a href="checkout_equipment.php?project_id=<?php echo $project_id; ?>" class="btn-primary">Checkout Equipment</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($project_equipment)): ?>
                        <p>No equipment has been used for this project yet.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Checkout Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($project_equipment as $equipment): ?>
                                        <tr>
                                            <td><a href="equipment_detail.php?id=<?php echo $equipment['equipment_id']; ?>"><?php echo htmlspecialchars($equipment['name']); ?></a></td>
                                            <td><?php echo format_date($equipment['checkout_date']); ?></td>
                                            <td><?php echo $equipment['return_date'] ? format_date($equipment['return_date']) : 'Not returned'; ?></td>
                                            <td>
                                                <?php if ($equipment['return_date']): ?>
                                                    <span class="badge status-available">Returned</span>
                                                    <?php if (!empty($equipment['status_on_return'])): ?>
                                                        <span class="badge"><?php echo htmlspecialchars($equipment['status_on_return']); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge status-in-use">Checked Out</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (is_admin() && !$equipment['return_date']): ?>
                                                    <a href="return_equipment.php?id=<?php echo $equipment['id']; ?>" class="btn-warning">Return</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Resources section -->
                <div class="card">
                    <div class="section-header">
                        <h3>Project Resources</h3>
                        <button class="btn-primary" onclick="showAddResourceForm()">Add Resource</button>
                    </div>
                    
                    <!-- Add resource form (hidden by default) -->
                    <div id="add-resource-form" style="display: none;">
                        <form method="post" action="project_detail.php?id=<?php echo $project_id; ?>" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_resource">
                            
                            <div class="form-group">
                                <label for="resource_title">Resource Title <span class="required">*</span></label>
                                <input type="text" id="resource_title" name="resource_title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="resource_type">Resource Type <span class="required">*</span></label>
                                <select id="resource_type" name="resource_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="report">Report</option>
                                    <option value="presentation">Presentation</option>
                                    <option value="image">Image</option>
                                    <option value="video">Video</option>
                                    <option value="code">Code</option>
                                    <option value="git_repository">Git Repository</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="resource_description">Description</label>
                                <textarea id="resource_description" name="resource_description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="resource_file">File Upload</label>
                                <input type="file" id="resource_file" name="resource_file">
                                <small>Max file size: 10MB</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="external_url">External URL (optional)</label>
                                <input type="url" id="external_url" name="external_url">
                                <small>For Git repositories, documentation links, etc.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Add Resource</button>
                                <button type="button" class="btn-secondary" onclick="hideAddResourceForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (empty($project_resources)): ?>
                        <p>No resources have been added to this project yet.</p>
                    <?php else: ?>
                        <div class="resources-list">
                            <?php foreach ($project_resources as $resource): ?>
                                <div class="resource-item">
                                    <div class="resource-header">
                                        <h4><?php echo htmlspecialchars($resource['title']); ?></h4>
                                        <span class="badge"><?php echo ucfirst(str_replace('_', ' ', $resource['resource_type'])); ?></span>
                                    </div>
                                    
                                    <?php if (!empty($resource['description'])): ?>
                                        <div class="resource-description">
                                            <?php echo nl2br(htmlspecialchars($resource['description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="resource-actions">
                                        <?php if (!empty($resource['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="btn-primary" target="_blank">Download File</a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($resource['external_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($resource['external_url']); ?>" class="btn-secondary" target="_blank">Visit External Link</a>
                                        <?php endif; ?>
                                        
                                        <?php if (is_admin()): ?>
                                            <form method="post" action="project_detail.php?id=<?php echo $project_id; ?>" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                                <input type="hidden" name="action" value="delete_resource">
                                                <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                                <button type="submit" class="btn-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function showAddResourceForm() {
            document.getElementById('add-resource-form').style.display = 'block';
        }
        
        function hideAddResourceForm() {
            document.getElementById('add-resource-form').style.display = 'none';
        }
        
        function confirmDelete(projectId) {
            if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                window.location.href = 'project_detail.php?id=' + projectId + '&action=delete';
            }
        }
    </script>
    
    <script src="../assets/js/projects.js"></script>
</body>
</html>