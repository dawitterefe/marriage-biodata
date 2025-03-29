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