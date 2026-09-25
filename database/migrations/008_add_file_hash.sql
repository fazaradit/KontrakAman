ALTER TABLE contracts ADD COLUMN file_hash VARCHAR(64);
CREATE INDEX idx_contracts_file_hash ON contracts(file_hash);
