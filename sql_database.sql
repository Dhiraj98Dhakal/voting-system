-- Create database
CREATE DATABASE IF NOT EXISTS electionnp;
USE electionnp;

-- Provinces table
CREATE TABLE provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Districts table
CREATE TABLE districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    province_id INT,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Constituencies table
CREATE TABLE constituencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    district_id INT,
    constituency_number INT NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parties table
CREATE TABLE parties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    party_name VARCHAR(255) NOT NULL,
    party_logo VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Candidates table
CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    party_id INT,
    candidate_name VARCHAR(255) NOT NULL,
    candidate_photo VARCHAR(500),
    election_type ENUM('FPTP', 'PR') NOT NULL,
    constituency_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE,
    FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE SET NULL
);

-- Voters table
CREATE TABLE voters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voter_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    province_id INT,
    district_id INT,
    constituency_id INT,
    dob DATE NOT NULL,
    citizenship_number VARCHAR(100) UNIQUE NOT NULL,
    father_name VARCHAR(255) NOT NULL,
    mother_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(500),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (constituency_id) REFERENCES constituencies(id)
);

-- Votes table
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voter_id INT,
    candidate_id INT,
    election_type ENUM('FPTP', 'PR') NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_voter_election (voter_id, election_type)
);

-- Admin table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admins (username, password) VALUES ('admin', MD5('admin'));

-- Insert Nepal's 7 provinces
INSERT INTO provinces (name) VALUES 
('Province No. 1'),
('Province No. 2'),
('Bagmati Province'),
('Gandaki Province'),
('Lumbini Province'),
('Karnali Province'),
('Sudurpashchim Province');

-- Insert sample districts for Province 1
INSERT INTO districts (province_id, name) VALUES 
(1, 'Taplejung'),
(1, 'Panchthar'),
(1, 'Ilam'),
(1, 'Jhapa'),
(1, 'Morang'),
(1, 'Sunsari'),
(1, 'Dhankuta'),
(1, 'Terhathum'),
(1, 'Sankhuwasabha'),
(1, 'Bhojpur'),
(1, 'Solukhumbu'),
(1, 'Okhaldhunga'),
(1, 'Khotang'),
(1, 'Udayapur');

-- Add more districts for other provinces as needed