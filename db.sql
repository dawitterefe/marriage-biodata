CREATE DATABASE biodata_db;
USE biodata_db;

-- Create the templates table
CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    god_image VARCHAR(255),              -- Path to god image (e.g., 'gods/ganesh.png'), nullable
    god_name VARCHAR(100),               -- Name of the god (e.g., 'Ganesh')
    biodata JSON,                        -- JSON object for personal details
    family_details JSON,                 -- JSON object for family details
    contact_details JSON,                -- JSON object for contact details, nullable
    background_image VARCHAR(255) NOT NULL, -- Path to A4 background image (e.g., 'backgrounds/template1.jpg')
    photo VARCHAR(255) DEFAULT NULL,     -- Path to person photo (e.g., 'persons/dawit.jpg'), nullable
    preview_image VARCHAR(255) NOT NULL  -- Path to generated preview PNG (e.g., 'previews/template1_preview.png')
);


CREATE TABLE customized_biodatas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,            -- Reference to the original template
    user_identifier VARCHAR(255) NOT NULL, -- Combination of IP and session ID
    god_image VARCHAR(255),              -- Customized god image
    god_name VARCHAR(255),               -- Customized god name (allowing multiple names as JSON or text)
    biodata JSON,                        -- Customized personal details
    family_details JSON,                 -- Customized family details
    contact_details JSON,                -- Customized contact details
    background_image VARCHAR(255) NOT NULL, -- Copied from templates.background_image
    photo VARCHAR(255) DEFAULT NULL,     -- User-uploaded photo
    preview_image VARCHAR(255) NOT NULL, -- Path to generated customized preview
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES templates(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customized_biodata_id INT NOT NULL,  -- Reference to customized biodata
    user_identifier VARCHAR(255) NOT NULL, -- Same as in customized_biodatas
    phone_number VARCHAR(20) NOT NULL,   -- User-entered phone number
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    amount DECIMAL(10, 2) DEFAULT 50.00, -- Price in INR
    transaction_id VARCHAR(255),         -- Payment gateway transaction ID
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customized_biodata_id) REFERENCES customized_biodatas(id)
);