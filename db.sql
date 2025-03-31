CREATE DATABASE biodata_db;
USE biodata_db;


CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    god_image VARCHAR(255),              
    god_name VARCHAR(100),      
    biodata JSON,               
    family_details JSON,      
    contact_details JSON,                
    background_image VARCHAR(255) NOT NULL, 
    photo VARCHAR(255) DEFAULT NULL,     
    preview_image VARCHAR(255) NOT NULL  
);


CREATE TABLE customized_biodatas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,            
    user_identifier VARCHAR(255) NOT NULL, 
    god_image VARCHAR(255),              
    god_name VARCHAR(255),               
    biodata JSON,                       
    family_details JSON,                 
    contact_details JSON,                
    background_image VARCHAR(255) NOT NULL, 
    photo VARCHAR(255) DEFAULT NULL,     
    preview_image VARCHAR(255) NOT NULL, 
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES templates(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customized_biodata_id INT NOT NULL,  
    user_identifier VARCHAR(255) NOT NULL, 
    phone_number VARCHAR(20) NOT NULL,   
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    amount DECIMAL(10, 2) DEFAULT 50.00, 
    transaction_id VARCHAR(255),         
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customized_biodata_id) REFERENCES customized_biodatas(id)
);