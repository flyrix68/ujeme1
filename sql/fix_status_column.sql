-- Fix matches table status column
ALTER TABLE matches 
MODIFY COLUMN status 
ENUM('pending','ongoing','completed','finished') 
DEFAULT 'pending'
COMMENT 'Match status with all possible values';

-- Verify the change
SHOW COLUMNS FROM matches LIKE 'status';
