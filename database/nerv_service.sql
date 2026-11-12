CREATE DATABASE IF NOT EXISTS nerv_service
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nerv_service;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin') NOT NULL DEFAULT 'admin',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nombre_usuario (nombre_usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cliente_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS servicios (
    id_servicio BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_servicio VARCHAR(30) NOT NULL,
    id_cliente INT UNSIGNED NOT NULL,
    tipo_dispositivo VARCHAR(80) NOT NULL,
    marca VARCHAR(80) NOT NULL,
    descripcion_dano TEXT NOT NULL,
    diagnostico TEXT NULL,
    estado ENUM(
        'RECIBIDO',
        'EN DIAGNOSTICO',
        'ESPERANDO APROBACION',
        'EN REPARACION',
        'REPARADO',
        'NO REPARABLE',
        'ENTREGADO'
    ) NOT NULL DEFAULT 'RECIBIDO',
    fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_finalizacion DATETIME NULL,
    creado_por INT UNSIGNED NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_numero_servicio (numero_servicio),
    INDEX idx_servicio_estado (estado),
    INDEX idx_servicio_fecha (fecha_recepcion),
    CONSTRAINT fk_servicio_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_servicio_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historial_servicios (
    id_historial BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_servicio BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(40) NULL,
    estado_nuevo VARCHAR(40) NOT NULL,
    diagnostico TEXT NULL,
    cambiado_por INT UNSIGNED NULL,
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historial_servicio (id_servicio),
    CONSTRAINT fk_historial_servicio
        FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_historial_usuario
        FOREIGN KEY (cambiado_por) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
