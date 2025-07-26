CREATE TABLE IF NOT EXISTS match_processed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (match_id)
);
