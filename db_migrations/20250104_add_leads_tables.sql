-- Migration: Add leads tables
-- Date: 2025-01-04

-- ===== Leads =====
CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  main_contact_id INT NOT NULL,
  channel VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  party_type VARCHAR(100) DEFAULT NULL,
  number_of_people INT DEFAULT NULL,
  description TEXT DEFAULT NULL,
  status ENUM('new', 'active', 'converted_to_reservation', 'deleted') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_leads_main_contact FOREIGN KEY (main_contact_id) REFERENCES contacts(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_leads_main_contact_id ON leads(main_contact_id);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_created_at ON leads(created_at);
CREATE INDEX idx_leads_channel ON leads(channel);

-- Lead secondary contacts
CREATE TABLE IF NOT EXISTS lead_secondary_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  contact_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lsc_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_lsc_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  UNIQUE KEY uk_lead_contact (lead_id, contact_id)
) ENGINE=InnoDB;

CREATE INDEX idx_lsc_lead_id ON lead_secondary_contacts(lead_id);
CREATE INDEX idx_lsc_contact_id ON lead_secondary_contacts(contact_id);

-- Lead comments
CREATE TABLE IF NOT EXISTS lead_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  comment_text TEXT NOT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tour_scheduled TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_lc_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_lc_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_lc_lead_id ON lead_comments(lead_id);
CREATE INDEX idx_lc_created_by ON lead_comments(created_by_user_id);
CREATE INDEX idx_lc_created_at ON lead_comments(created_at);
