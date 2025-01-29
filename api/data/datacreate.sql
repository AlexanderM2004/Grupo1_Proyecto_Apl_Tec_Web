-- Conectarse a la base de datos
\c susurros_db;

-- Verificar si las tablas ya existen
DO $$ 
BEGIN
    -- Drop tables if they exist
    DROP TABLE IF EXISTS susurros_etiquetas CASCADE;
    DROP TABLE IF EXISTS susurros CASCADE;
    DROP TABLE IF EXISTS etiquetas CASCADE;
    DROP TABLE IF EXISTS usuarios CASCADE;
    
    -- Drop functions if they exist
    DROP FUNCTION IF EXISTS actualizar_fecha_actualizacion() CASCADE;
    DROP FUNCTION IF EXISTS incrementar_vistas(INTEGER) CASCADE;
    DROP FUNCTION IF EXISTS validar_nivel_respuesta() CASCADE;
END $$;

-- Crear tabla de usuarios
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    genero CHAR(1) CHECK (genero IN ('M', 'F', 'O')),
    pais VARCHAR(100) NOT NULL,
    direccion TEXT,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de etiquetas
CREATE TABLE etiquetas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de susurros
CREATE TABLE susurros (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    mensaje VARCHAR(500) NOT NULL,
    nivel INTEGER DEFAULT 0 CHECK (nivel >= 0 AND nivel <= 5),
    susurro_padre_id INTEGER REFERENCES susurros(id) ON DELETE CASCADE,
    vistas INTEGER DEFAULT 0,
    me_interesa INTEGER DEFAULT 0,
    es_mentira INTEGER DEFAULT 0,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla pivote para susurros y etiquetas
CREATE TABLE susurros_etiquetas (
    susurro_id INTEGER REFERENCES susurros(id) ON DELETE CASCADE,
    etiqueta_id INTEGER REFERENCES etiquetas(id) ON DELETE CASCADE,
    PRIMARY KEY (susurro_id, etiqueta_id)
);

-- Crear función para actualizar fecha_actualizacion
CREATE OR REPLACE FUNCTION actualizar_fecha_actualizacion()
RETURNS TRIGGER AS $$
BEGIN
    NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Crear function para incrementar vistas
CREATE OR REPLACE FUNCTION incrementar_vistas(susurro_id INTEGER)
RETURNS VOID AS $$
BEGIN
    UPDATE susurros
    SET vistas = vistas + 1
    WHERE id = susurro_id;
END;
$$ LANGUAGE plpgsql;

-- Crear función para validar nivel de respuesta
CREATE OR REPLACE FUNCTION validar_nivel_respuesta()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.susurro_padre_id IS NOT NULL THEN
        IF (
            SELECT nivel 
            FROM susurros 
            WHERE id = NEW.susurro_padre_id
        ) >= 5 THEN
            RAISE EXCEPTION 'No se permiten respuestas más allá del nivel 5';
        END IF;
        
        NEW.nivel = (
            SELECT nivel + 1 
            FROM susurros 
            WHERE id = NEW.susurro_padre_id
        );
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Crear triggers
CREATE TRIGGER actualizar_fecha_usuario
    BEFORE UPDATE ON usuarios
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_fecha_actualizacion();

CREATE TRIGGER actualizar_fecha_susurro
    BEFORE UPDATE ON susurros
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_fecha_actualizacion();

CREATE TRIGGER validar_nivel_respuesta_trigger
    BEFORE INSERT ON susurros
    FOR EACH ROW
    EXECUTE FUNCTION validar_nivel_respuesta();

-- Crear vistas para consultas comunes
CREATE VIEW susurros_populares AS
SELECT s.*, u.username
FROM susurros s
JOIN usuarios u ON s.usuario_id = u.id
WHERE s.fecha_creacion >= CURRENT_DATE - INTERVAL '7 days'
ORDER BY (s.me_interesa - s.es_mentira) DESC, s.vistas DESC;

-- Insertar algunos datos de prueba
INSERT INTO usuarios (username, password, genero, pais) 
VALUES 
('test_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'M', 'Peru'),
('test_user2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'F', 'Peru');

-- Grant permissions
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO postgres;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO postgres;