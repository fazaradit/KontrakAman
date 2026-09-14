CREATE TABLE analysis_results (
    id                          SERIAL PRIMARY KEY,
    clause_id                   INT REFERENCES contract_clauses(id) ON DELETE CASCADE,
    matched_regulation_ids      INT[],
    verdict                     VARCHAR(20),
    severity                    VARCHAR(10),
    source                      VARCHAR(10),
    explanation                 TEXT,
    confidence                  NUMERIC(3,2),
    created_at                  TIMESTAMP DEFAULT now()
);
