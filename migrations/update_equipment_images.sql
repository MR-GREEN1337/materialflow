-- Add the equipment_images table

-- First check if the table already exists
SET @tableExists = 0;
SELECT COUNT(*) INTO @tableExists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'equipment_images';

-- Create equipment_images table if it doesn't exist
SET @sql = IF(@tableExists = 0, 
    'CREATE TABLE equipment_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255),
        is_primary BOOLEAN DEFAULT 0,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
    )',
    'SELECT "equipment_images table already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;