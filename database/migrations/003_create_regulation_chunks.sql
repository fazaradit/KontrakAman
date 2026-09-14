CREATE TABLE regulation_chunks (
    id              SERIAL PRIMARY KEY,
    source_law      VARCHAR(50) NOT NULL,
    pasal           VARCHAR(20),
    ayat            VARCHAR(20),
    full_text       TEXT NOT NULL,
    topic_tags      TEXT[],
    embedding       VECTOR(768)
);
