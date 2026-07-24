
ALTER TABLE products ADD COLUMN IF NOT EXISTS created_by INT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved';
ALTER TABLE products ADD COLUMN IF NOT EXISTS image_file_path VARCHAR(255) NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_custom BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS reviewed_by INT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL;

ALTER TABLE products ADD FOREIGN KEY IF NOT EXISTS fk_product_creator (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE products ADD FOREIGN KEY IF NOT EXISTS fk_product_reviewer (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_image (product_id, file_name)
);

CREATE INDEX IF NOT EXISTS idx_product_approval_status ON products(approval_status);
CREATE INDEX IF NOT EXISTS idx_product_created_by ON products(created_by);
CREATE INDEX IF NOT EXISTS idx_product_is_custom ON products(is_custom);
