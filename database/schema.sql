PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS accounts (
    id TEXT PRIMARY KEY,
    currency TEXT NOT NULL,
    balance INTEGER NOT NULL CHECK (balance >= 0),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS ledger_entries (
    id TEXT PRIMARY KEY,
    account_id TEXT NOT NULL,
    operation_id TEXT NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('credit', 'debit')),
    amount INTEGER NOT NULL CHECK (amount > 0),
    currency TEXT NOT NULL,
    balance_after INTEGER NOT NULL CHECK (balance_after >= 0),
    created_at TEXT NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

CREATE INDEX IF NOT EXISTS idx_ledger_entries_account_created
    ON ledger_entries (account_id, created_at, id);

CREATE TABLE IF NOT EXISTS idempotency_keys (
    key TEXT PRIMARY KEY,
    request_hash TEXT NOT NULL,
    operation_id TEXT NOT NULL,
    balance INTEGER NOT NULL,
    currency TEXT NOT NULL,
    ledger_entries_json TEXT NOT NULL,
    created_at TEXT NOT NULL
);
