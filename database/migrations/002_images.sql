-- Protected Cloudinary asset references. No existing row is changed.
CREATE TABLE IF NOT EXISTS complaint_images (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    uploaded_by INT NULL,
    label ENUM('before', 'after') NOT NULL,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    format VARCHAR(12) NOT NULL,
    mime_type VARCHAR(40) NOT NULL,
    byte_size INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complaint_images_complaint (complaint_id),
    CONSTRAINT fk_complaint_images_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    CONSTRAINT fk_complaint_images_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profile_images (
    user_id INT PRIMARY KEY,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    format VARCHAR(12) NOT NULL,
    mime_type VARCHAR(40) NOT NULL,
    byte_size INT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_images_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_cleanup_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    attempts INT NOT NULL DEFAULT 0,
    last_error VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
