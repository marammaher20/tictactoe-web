CREATE DATABASE IF NOT EXISTS tictactoe
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tictactoe;

CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player1 VARCHAR(50) NOT NULL,
    player2 VARCHAR(50) NOT NULL,
    result ENUM('X', 'O', 'Draw') NOT NULL,
    winner_name VARCHAR(50) NULL,
    moves_json TEXT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code CHAR(5) NOT NULL UNIQUE,
    player_x VARCHAR(50) NOT NULL,
    player_o VARCHAR(50) NULL,
    avatar_x VARCHAR(10) NOT NULL,
    avatar_o VARCHAR(10) NULL,
    board CHAR(9) NOT NULL DEFAULT '---------',
    turn ENUM('X', 'O') NOT NULL DEFAULT 'X',
    status ENUM('waiting', 'playing', 'finished') NOT NULL DEFAULT 'waiting',
    result ENUM('X', 'O', 'Draw') NULL,
    win_line VARCHAR(20) NULL,
    score_x INT NOT NULL DEFAULT 0,
    score_o INT NOT NULL DEFAULT 0,
    draws INT NOT NULL DEFAULT 0,
    version INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);