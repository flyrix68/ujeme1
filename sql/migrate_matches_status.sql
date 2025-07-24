-- Migration to update matches table status enum values
ALTER TABLE matches 
MODIFY COLUMN status 
ENUM('pending','ongoing','completed','finished') 
DEFAULT 'pending';
