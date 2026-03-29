-- Module: CompliancePLD
-- Description: Historial de consultas PEP a la UIF (Arts. 45 Bis-Quinquies Reglamento LFPIORPI DOF 27/03/2026)

CREATE TABLE IF NOT EXISTS llx_pld_pep_verificacion (
  rowid          INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity         INTEGER DEFAULT 1 NOT NULL,

  fk_societe     INTEGER NOT NULL,

  fecha_consulta DATETIME NOT NULL,

  -- 'negativo' | 'positivo' | 'sin_respuesta' | 'error_uif'
  resultado      VARCHAR(20) NOT NULL,

  -- Referencia/folio de la consulta electrónica en el portal UIF (opcional)
  referencia_uif VARCHAR(100) DEFAULT NULL,

  observaciones  TEXT,

  -- Campos estándar
  date_creation  DATETIME NOT NULL,
  tms            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat  INTEGER DEFAULT NULL,
  fk_user_modif  INTEGER DEFAULT NULL,
  import_key     VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
