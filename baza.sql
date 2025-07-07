
-- Baza: lojalnost

CREATE DATABASE IF NOT EXISTS lojalnost;
USE lojalnost;

-- Tabela: klijenti
CREATE TABLE IF NOT EXISTS klijenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(100),
    prezime VARCHAR(100),
    adresa VARCHAR(255),
    email VARCHAR(100),
    telefon VARCHAR(30),
    clanski_broj VARCHAR(50) UNIQUE,
    sponzor VARCHAR(50)
);

-- Tabela: proizvodi
CREATE TABLE IF NOT EXISTS proizvodi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sifra VARCHAR(50) UNIQUE,
    naziv VARCHAR(100),
    cena DECIMAL(10,2),
    cv DECIMAL(10,2),
    je_set BOOLEAN DEFAULT FALSE
);

-- Tabela: recepti_setova
CREATE TABLE IF NOT EXISTS recepti_setova (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_id INT,
    proizvod_id INT,
    kolicina INT,
    FOREIGN KEY (set_id) REFERENCES proizvodi(id) ON DELETE CASCADE,
    FOREIGN KEY (proizvod_id) REFERENCES proizvodi(id) ON DELETE CASCADE
);

-- Tabela: porudzbine
CREATE TABLE IF NOT EXISTS porudzbine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATETIME DEFAULT CURRENT_TIMESTAMP,
    klijent_id INT,
    ukupno_cv DECIMAL(10,2),
    FOREIGN KEY (klijent_id) REFERENCES klijenti(id)
);

-- Tabela: stavke_porudzbine
CREATE TABLE IF NOT EXISTS stavke_porudzbine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    porudzbina_id INT,
    proizvod_id INT,
    kolicina INT,
    cena_po_jedinici DECIMAL(10,2),
    cv_po_jedinici DECIMAL(10,2),
    FOREIGN KEY (porudzbina_id) REFERENCES porudzbine(id) ON DELETE CASCADE,
    FOREIGN KEY (proizvod_id) REFERENCES proizvodi(id)
);

-- Tabela: istorija_lojalnosti
CREATE TABLE IF NOT EXISTS istorija_lojalnosti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    klijent_id INT,
    mesec_godina VARCHAR(7), -- primer: 2025-06
    ukupno_cv_mesec DECIMAL(10,2),
    poklon VARCHAR(255),
    FOREIGN KEY (klijent_id) REFERENCES klijenti(id)
);
