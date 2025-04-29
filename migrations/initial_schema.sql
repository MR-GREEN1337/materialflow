-- MySQL Database Schema for Equipment Tracking System

-- Drop existing tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS project_equipment;
DROP TABLE IF EXISTS project_resources;
DROP TABLE IF EXISTS project_students;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table (for authentication)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    email VARCHAR(100),
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    purchase_date DATE,
    purchase_price DECIMAL(10, 2),
    vendor VARCHAR(100),
    vendor_url VARCHAR(255),
    documentation_url VARCHAR(255),
    technical_specs TEXT,
    status ENUM('available', 'in_use', 'broken', 'lost', 'deprecated') NOT NULL DEFAULT 'available',
    storage_location VARCHAR(100),
    additional_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    course_name VARCHAR(100),
    status ENUM('ongoing', 'completed', 'archived') NOT NULL DEFAULT 'ongoing',
    additional_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Project-Equipment relation table (tracks equipment usage in projects)
CREATE TABLE project_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    equipment_id INT NOT NULL,
    checkout_date DATE NOT NULL,
    return_date DATE,
    status_on_return TEXT,
    notes TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- Project resources table (for storing files, links, etc.)
CREATE TABLE project_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    resource_type ENUM('report', 'presentation', 'image', 'video', 'code', 'git_repository', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    external_url VARCHAR(255),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Project students relation (simple storage as requested in the requirements)
CREATE TABLE project_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_list TEXT NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Insert an admin user (password: admin123)
INSERT INTO users (student_id, password_hash, name, role) 
VALUES ('admin', '$2y$10$8JmVBe1MKdOUZPFZzIRUWusGxZRIQhXz2eRnrjXGQZr07JtKKVZFi', 'Administrator', 'admin');

-- Create an index for faster lookups
CREATE INDEX idx_equipment_status ON equipment(status);
CREATE INDEX idx_projects_status ON projects(status);