-- Module: CompliancePLD
-- Description: Beneficiarios controladores de personas morales (Art. 18 LFPIORPI)

CREATE TABLE IF NOT EXISTS llx_pld_beneficiario (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relación
  fk_societe INT NOT NULL,                                        -- Empresa a la que pertenece el beneficiario
  fk_socpeople INT DEFAULT NULL,                                  -- Contacto Dolibarr si existe
  
  -- Tipo de beneficiario
  tipo_beneficiario VARCHAR(50) NOT NULL,                         -- accionista|fideicomitente|fideicomisario|administrador
  
  -- Datos personales (si fk_socpeople = NULL, se capturan aquí)
  nombre VARCHAR(100),
  apellido_paterno VARCHAR(100),
  apellido_materno VARCHAR(100),
  
  -- Identificación
  curp VARCHAR(18),
  rfc VARCHAR(13),
  fecha_nacimiento DATE,
  nacionalidad VARCHAR(50),
  
  -- Participación
  porcentaje_participacion DECIMAL(5,2) NOT NULL,                -- Máximo 100.00
  
  -- PEP (Persona Expuesta Políticamente)
  es_pep tinyint DEFAULT 0,
  cargo_pep VARCHAR(200),
  
  -- Validación
  verificado tinyint DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  -- Vigencia
  activo tinyint DEFAULT 1,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  import_key VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB;
