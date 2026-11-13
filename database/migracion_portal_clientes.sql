USE nerv_service;
-- Ejecutar una sola vez si ya instalaste el esquema anterior. No elimina datos.
ALTER TABLE clientes ADD COLUMN email VARCHAR(190) NULL AFTER nombre, ADD COLUMN telefono VARCHAR(30) NULL AFTER email, ADD COLUMN password_hash VARCHAR(255) NULL AFTER telefono;
ALTER TABLE clientes ADD UNIQUE KEY uq_cliente_email (email);
