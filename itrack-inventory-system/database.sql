CREATE DATABASE IF NOT EXISTS device_requisition;
USE device_requisition;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    role VARCHAR(50) NOT NULL DEFAULT 'intern',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    quantity INT DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requisitioner VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    device_type VARCHAR(100) NOT NULL,
    description TEXT,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    requester_email VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sample data
INSERT INTO devices (type, quantity, description)
SELECT 'GPS Tracker', 10, 'Standard vehicle GPS device'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE type = 'GPS Tracker');

INSERT INTO devices (type, quantity, description)
SELECT 'SIM Card', 5, 'Data SIM for trackers'
WHERE NOT EXISTS (SELECT 1 FROM devices WHERE type = 'SIM Card');