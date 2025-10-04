-- Migration: Add contacts table
-- Date: 2025-01-04

CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) DEFAULT NULL,
  organization VARCHAR(255) DEFAULT NULL,
  phone_number VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_contacts_first_name ON contacts(first_name);
CREATE INDEX idx_contacts_last_name ON contacts(last_name);
CREATE INDEX idx_contacts_email ON contacts(email);
CREATE INDEX idx_contacts_organization ON contacts(organization);
CREATE INDEX idx_contacts_created_at ON contacts(created_at);
