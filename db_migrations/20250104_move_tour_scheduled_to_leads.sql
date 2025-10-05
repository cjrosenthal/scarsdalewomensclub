-- Migration: Move tour_scheduled from lead_comments to leads table
-- Date: 2025-01-04

-- Add tour_scheduled to leads table
ALTER TABLE leads
  ADD COLUMN tour_scheduled TINYINT(1) NOT NULL DEFAULT 0 AFTER description;

-- Remove tour_scheduled from lead_comments table
ALTER TABLE lead_comments
  DROP COLUMN tour_scheduled;
