<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access for this page
require_admin();

// Handle user actions (add, edit, delete)
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Initialize messages
$success_message = '';
$error_message = '';

// Check for flash messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get database connection
$conn = connect_db();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['add_user'])) {
        $student_id = trim($_POST['student_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        // Validate input
        if (empty($student_id)) {
            $error_message = 'Student ID is required';
        } else {
            // Check if student ID already exists
            $checkSql = "SELECT id FROM users WHERE student_id = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param('s', $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = 'Student ID already exists';
            } else {
                // Insert new user
                $insertSql = "INSERT INTO users (student_id, password_hash, name, email, role) VALUES (?, '', ?, ?, ?)";
                $stmt = $conn->prepare($insertSql);
                $stmt->bind_param('ssss', $student_id, $name, $email, $role);
                
                if ($stmt->execute()) {
                    $success_message = 'User added successfully';
                    // Redirect to prevent form resubmission
                    $_SESSION['success_message'] = $success_message;
                    header('Location: users.php');
                    exit;
                } else {
                    $error_message = 'Failed to add user: ' . $stmt->error;
                }
            }
        }
    }
    
    // Update user
    else if (isset($_POST['update_user']) && $user_id) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        // Update user
        $updateSql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('sssi', $name, $email, $role, $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'User updated successfully';
            // Redirect to prevent form resubmission
            $_SESSION['success_message'] = $success_message;
            header('Location: users.php');
            exit;
        } else {
            $error_message = 'Failed to update user: ' . $stmt->error;
        }
    }
    
    // Delete user
    else if (isset($_POST['delete_user']) && $user_id) {
        // Delete the user
        $deleteSql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'User deleted successfully';
            // Redirect to prevent form resubmission
            $_SESSION['success_message'] = $success_message;
            header('Location: users.php');
            exit;
        } else {
            $error_message = 'Failed to delete user: ' . $stmt->error;
        }
    }
}

// Get user data for editing
$user = null;
if ($action === 'edit' && $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        $error_message = 'User not found';
        $action = 'list';
    }
}

// Get all users for listing
$users = [];
if ($action === 'list') {
    $sql = "SELECT * FROM users ORDER BY student_id";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Equipment Tracking System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    User Management
                </h1>
                
                <div class="page-actions">
                    <?php if ($action === 'list'): ?>
                        <a href="users.php?action=add" class="btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add New User
                        </a>
                    <?php else: ?>
                        <a href="users.php" class="btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Back to List
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit User Form -->
                <div class="card">
                    <h2 class="card-title">
                        <?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?>
                    </h2>
                    
                    <form method="post" action="<?php echo $action === 'add' ? 'users.php?action=add' : 'users.php?action=edit&id=' . $user_id; ?>">
                        <?php if ($action === 'add'): ?>
                            <div class="form-group">
                                <label for="student_id">Student ID <span style="color: var(--destructive);">*</span></label>
                                <input type="text" id="student_id" name="student_id" required>
                                <small style="display: block; margin-top: 0.25rem; color: var(--muted-foreground);">This will be used for login</small>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Student ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['student_id']); ?>" disabled>
                                <small style="display: block; margin-top: 0.25rem; color: var(--muted-foreground);">Student ID cannot be changed</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role <span style="color: var(--destructive);">*</span></label>
                            <select id="role" name="role" required>
                                <option value="student" <?php echo (isset($user['role']) && $user['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($action === 'add'): ?>
                                <button type="submit" name="add_user" class="btn-primary">Add User</button>
                            <?php else: ?>
                                <button type="submit" name="update_user" class="btn-primary">Update User</button>
                            <?php endif; ?>
                            <a href="users.php" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($action === 'delete' && $user_id): ?>
                <!-- Delete User Confirmation -->
                <div class="card">
                    <h2 class="card-title" style="color: var(--destructive);">Confirm Deletion</h2>
                    <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    
                    <form method="post" action="users.php?action=delete&id=<?php echo $user_id; ?>" style="margin-top: 1.5rem;">
                        <div class="form-actions">
                            <button type="submit" name="delete_user" class="btn-danger">Yes, Delete User</button>
                            <a href="users.php" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Users List -->
                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; display: block; opacity: 0.5;">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                            </svg>
                                            <p style="color: var(--muted-foreground);">No users found</p>
                                            <a href="users.php?action=add" class="btn-primary" style="margin-top: 1rem; display: inline-flex;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                                Add First User
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user_row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user_row['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($user_row['name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user_row['email'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($user_row['role'] === 'admin'): ?>
                                                    <span class="badge" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">Administrator</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">Student</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if (isset($user_row['last_login']) && $user_row['last_login']) {
                                                        echo format_date($user_row['last_login']);
                                                    } else {
                                                        echo '<span style="color: var(--muted-foreground);">Never</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="users.php?action=edit&id=<?php echo $user_row['id']; ?>" class="btn-secondary" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="Edit User">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                                        </svg>
                                                    </a>
                                                    
                                                    <?php if ($user_row['id'] != $_SESSION['user_id']): // Prevent deleting yourself ?>
                                                        <a href="users.php?action=delete&id=<?php echo $user_row['id']; ?>" class="btn-danger" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="Delete User">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                                            </svg>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Add confirmation for delete links
        document.addEventListener('DOMContentLoaded', function() {
            const deleteLinks = document.querySelectorAll('a[href*="action=delete"]');
            
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>