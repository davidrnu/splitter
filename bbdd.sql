CREATE DATABASE IF NOT EXISTS splitter
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE splitter;

CREATE TABLE usuarios (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100)    NOT NULL,
    email       VARCHAR(255)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,
    creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE grupos (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150)    NOT NULL,
    descripcion TEXT            DEFAULT NULL,
    creado_por  INT UNSIGNED    NOT NULL,
    creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE miembros (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    grupo_id    INT UNSIGNED    NOT NULL,
    usuario_id  INT UNSIGNED    NOT NULL,
    rol         ENUM('admin', 'miembro') NOT NULL DEFAULT 'miembro',
    unido_en    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (grupo_id, usuario_id),
    FOREIGN KEY (grupo_id)    REFERENCES grupos(id)   ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE invitaciones (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    grupo_id    INT UNSIGNED    NOT NULL,
    token       VARCHAR(64)     NOT NULL UNIQUE,
    creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE movimientos (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    grupo_id        INT UNSIGNED    NOT NULL,
    usuario_id      INT UNSIGNED    NOT NULL,
    tipo            ENUM('gasto', 'ingreso', 'transferencia') NOT NULL,
    importe         DECIMAL(10,2)   NOT NULL,
    descripcion     VARCHAR(255)    NOT NULL,
    destinatario_id INT UNSIGNED    DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id)        REFERENCES grupos(id)   ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)      REFERENCES usuarios(id),
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE participantes (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    movimiento_id   INT UNSIGNED    NOT NULL,
    usuario_id      INT UNSIGNED    NOT NULL,
    importe         DECIMAL(10,2)   NOT NULL,
    UNIQUE (movimiento_id, usuario_id),
    FOREIGN KEY (movimiento_id) REFERENCES movimientos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE tokens_reset (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED    NOT NULL,
    token       VARCHAR(64)     NOT NULL UNIQUE,
    expira_en   DATETIME        NOT NULL,
    usado       TINYINT(1)      NOT NULL DEFAULT 0,
    creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
