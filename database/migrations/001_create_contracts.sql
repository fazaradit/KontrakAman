CREATE TABLE contracts (
    id              SERIAL PRIMARY KEY,
    filename        VARCHAR(255) NOT NULL,
    contract_type   VARCHAR(10) DEFAULT 'UNKNOWN',
    raw_text        TEXT,
    uploaded_at     TIMESTAMP DEFAULT now(),
    status          VARCHAR(20) DEFAULT 'processing'
);
