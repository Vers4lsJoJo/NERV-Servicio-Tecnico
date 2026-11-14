USE nerv_service;

-- Ejecutar UNA SOLA VEZ sobre una instalación V5 existente.
-- Realiza primero una copia de seguridad de la base de datos.

ALTER TABLE servicios
    ADD COLUMN eliminado_en DATETIME NULL DEFAULT NULL AFTER actualizado_en,
    ADD COLUMN eliminado_por INT UNSIGNED NULL DEFAULT NULL AFTER eliminado_en,
    ADD COLUMN motivo_eliminacion VARCHAR(255) NULL DEFAULT NULL AFTER eliminado_por,
    ADD INDEX idx_servicio_eliminado (eliminado_en),
    ADD CONSTRAINT fk_servicio_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE clientes
    ADD COLUMN codigo_activacion_hash CHAR(64) NULL DEFAULT NULL AFTER password_hash,
    ADD COLUMN codigo_activacion_expira DATETIME NULL DEFAULT NULL AFTER codigo_activacion_hash;

CREATE TABLE auditoria_servicios (
    id_auditoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_servicio BIGINT UNSIGNED NOT NULL,
    accion ENUM('ARCHIVADO','RESTAURADO') NOT NULL,
    motivo VARCHAR(255) NULL,
    id_usuario INT UNSIGNED NULL,
    fecha_accion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auditoria_servicio (id_servicio),
    INDEX idx_auditoria_fecha (fecha_accion),
    CONSTRAINT fk_auditoria_servicio FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
