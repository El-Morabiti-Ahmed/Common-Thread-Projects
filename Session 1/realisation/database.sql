-- ==========================================================
--  Board Game Hub - database schema + starter data
--  Import this file in MySQL Workbench (File > Run SQL Script)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS boardgame_hub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE boardgame_hub;

-- ----------------------------------------------------------
-- PLAYER: accounts, role and rank
-- (the column is called player_rank because RANK is a
--  reserved word in MySQL 8)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS player (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(20)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('player', 'admin') NOT NULL DEFAULT 'player',
    points        INT UNSIGNED NOT NULL DEFAULT 0,
    player_rank   ENUM('iron','copper','bronze','gold','diamond','beast','immortal')
                  NOT NULL DEFAULT 'iron',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_player_username (username),
    UNIQUE KEY uq_player_email (email)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- GAME CATEGORY: used to sort the games
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS game_category (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50)  NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- GAME: the games players can play
-- slug = name of the JS file in assets/js/games/ (without .js)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS game (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    name        VARCHAR(60)  NOT NULL,
    slug        VARCHAR(60)  NOT NULL,
    description VARCHAR(255) NULL,
    icon        VARCHAR(16)  NOT NULL DEFAULT '🎲',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_game_slug (slug),
    CONSTRAINT fk_game_category
        FOREIGN KEY (category_id) REFERENCES game_category (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- GAME SESSION: one finished single-player game (win or loss)
-- points_change stores what the session really gave/took
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS game_session (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    player_id     INT UNSIGNED NOT NULL,
    game_id       INT UNSIGNED NOT NULL,
    result        ENUM('win', 'loss') NOT NULL,
    points_change SMALLINT     NOT NULL,
    played_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_session_player (player_id, played_at),
    KEY idx_session_game (game_id),
    CONSTRAINT fk_session_player
        FOREIGN KEY (player_id) REFERENCES player (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_session_game
        FOREIGN KEY (game_id) REFERENCES game (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Starter data
-- ----------------------------------------------------------
INSERT INTO game_category (name, description) VALUES
    ('Strategy', 'Think ahead and outplay your opponent.'),
    ('Memory',   'Train your memory and focus.');

INSERT INTO game (category_id, name, slug, description, icon) VALUES
    ((SELECT id FROM game_category WHERE name = 'Strategy'),
     'Tic Tac Toe', 'tic-tac-toe',
     'Beat the computer by getting three in a row. Draws are replayed.', '❌'),
    ((SELECT id FROM game_category WHERE name = 'Memory'),
     'Memory Match', 'memory-match',
     'Find all 8 pairs before you make 8 mistakes.', '🧩');

-- ----------------------------------------------------------
-- HOW TO MAKE SOMEONE AN ADMIN (run in Workbench):
--   UPDATE player SET role = 'admin' WHERE username = 'yourname';
-- and back to a normal player:
--   UPDATE player SET role = 'player' WHERE username = 'yourname';
-- ----------------------------------------------------------
