-- Set default values for score columns
ALTER TABLE matches 
MODIFY COLUMN score_home INT DEFAULT 0,
MODIFY COLUMN score_away INT DEFAULT 0;

-- Set actual scores for match_id=26
UPDATE matches 
SET score_home = 0, score_away = 0,
    status = 'completed'
WHERE id = 26;
