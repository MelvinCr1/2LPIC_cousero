-- === 1. CRÉATION DE LA BASE ===
CREATE DATABASE IF NOT EXISTS coursero CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coursero;

-- === 2. TABLE ÉTUDIANTS ===
CREATE TABLE IF NOT EXISTS etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- === 3. TABLE DES SOUMISSIONS ===
CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    date_submission DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('en_attente', 'corrige', 'erreur') DEFAULT 'en_attente',
    note INT DEFAULT NULL,
    commentaire TEXT DEFAULT NULL,

    FOREIGN KEY (id_etudiant) REFERENCES etudiants(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- === 4. INDEX UTILES ===
CREATE INDEX idx_status ON submissions(status);
CREATE INDEX idx_date ON submissions(date_submission);