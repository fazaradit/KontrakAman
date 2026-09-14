CREATE TABLE contract_clauses (
    id                  SERIAL PRIMARY KEY,
    contract_id         INT REFERENCES contracts(id) ON DELETE CASCADE,
    clause_number       VARCHAR(20),
    raw_text            TEXT NOT NULL,
    category            VARCHAR(50),
    extracted_values    JSONB,
    embedding           VECTOR(768),
    position_order      INT
);
