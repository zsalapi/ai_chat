-- ==========================================
-- PostgreSQL Gyakorló Adatbázis (postgres_tutorial.sql)
-- ==========================================
-- Futtatás: psql -U postgres -d sajat_adatbazisod -f postgres_tutorial.sql

-- Meglévő adatok takarítása, ha újra futtatnád
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
DROP EXTENSION IF EXISTS "uuid-ossp";

-- UUID generátor kiegészítő betöltése (Ha régebbi a Postgres, mint a 13-as verzió)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 1. USERS Tábla (UUID, Tömbök)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    tags TEXT[], -- TÖMB adattípus (Junction tábla helyett)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. EVENTS Tábla (JSONB, INET, RANGE, Szöveg kereső oszlop)
CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    event_type VARCHAR(50),
    ip_address INET, -- Beépített IP cím típus
    timeline TSTZRANGE, -- Időszak (Mettől-meddig történt TIMESTAMP RANGE)
    metadata JSONB, -- Sémamentes JSON adatok GIN indexelésre készen
    description TEXT -- Hosszú szöveg, amin majd a Full-Text Search-öt gyakoroljuk
);

-- Indexek beállítása az extrém sebességhez
CREATE INDEX idx_users_tags ON users USING GIN (tags);
CREATE INDEX idx_events_metadata ON events USING GIN (metadata);
CREATE INDEX idx_events_description_fts ON events USING GIN (to_tsvector('english', description));


-- ==========================================
-- TESZT ADATOK BETÖLTÉSE
-- ==========================================

-- Beszúrás UUID visszakéréssel (RETURNING)
INSERT INTO users (id, username, email, tags) VALUES 
('11111111-1111-1111-1111-111111111111', 'zsolt_dev', 'zsolt@test.com', ARRAY['admin', 'developer', 'sql_ninja']),
('22222222-2222-2222-2222-222222222222', 'anna_marketing', 'anna@test.com', ARRAY['marketing', 'editor']),
('33333333-3333-3333-3333-333333333333', 'guest_01', 'guest@test.com', ARRAY['guest']);

INSERT INTO events (user_id, event_type, ip_address, timeline, metadata, description) VALUES 
(
    '11111111-1111-1111-1111-111111111111', 
    'login', 
    '192.168.1.15', 
    tstzrange('2024-03-20 08:00:00+01', '2024-03-20 16:30:00+01'), 
    '{"browser": "Chrome", "os": "Linux", "resolution": "1920x1080", "features": ["dark_mode"]}',
    'User successfully logged in and started a rigorous database optimization session.'
),
(
    '11111111-1111-1111-1111-111111111111', 
    'error', 
    '192.168.1.100', 
    tstzrange('2024-03-21 10:00:00+01', '2024-03-21 10:05:00+01'), 
    '{"browser": "Firefox", "os": "Linux", "error_code": 500}',
    'Database connection failed due to missing pgsql driver.'
),
(
    '22222222-2222-2222-2222-222222222222', 
    'view_page', 
    '10.0.0.5', 
    tstzrange('2024-03-20 09:15:00+01', '2024-03-20 09:20:00+01'), 
    '{"browser": "Safari", "os": "iOS", "campaign": "spring_sale"}',
    'User viewed the landing page but immediately left after seeing the cat picture.'
),
(
    '33333333-3333-3333-3333-333333333333', 
    'signup', 
    '8.8.8.8', 
    tstzrange('2024-03-22 14:00:00+01', '2024-03-22 14:01:00+01'), 
    '{"browser": "Chrome", "os": "Windows"}',
    'New user registration completed quickly with standard cat profile photo.'
);
