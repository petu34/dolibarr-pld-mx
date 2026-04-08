-- Module: CompliancePLD
-- Description: Registro central de operaciones vulnerables (Art. 17 Fracc. VIII LFPIORPI)

CREATE TABLE IF NOT EXISTS llx_pld_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Referencias Dolibarr
  fk_facture INT DEFAULT NULL,
  fk_societe INT NOT NULL,
  fk_product INT DEFAULT NULL,                                    -- ID del vehículo
  
  -- Tipo de operación
  tipo_operacion VARCHAR(50) NOT NULL DEFAULT 'venta_vehiculo',
  tipo_actividad_vulnerable VARCHAR(10) NOT NULL,                 -- Fracción Art. 17 LFPIORPI
  
  -- Datos de la operación
  fecha_operacion DATE NOT NULL,
  mes_reportado VARCHAR(6) NOT NULL,                              -- YYYYMM para agrupación
  folio_interno VARCHAR(50),
  
  -- Montos
  moneda VARCHAR(3) DEFAULT 'MXN',
  monto_mxn DECIMAL(15,2) NOT NULL,
  
  -- Umbrales (calculados en PHP, no triggers SQL)
  supera_umbral tinyint DEFAULT 0,
  
  -- Estado de cumplimiento
  cliente_identificado tinyint DEFAULT 0,
  documentacion_completa tinyint DEFAULT 0,
  
  -- Avisos
  requiere_aviso tinyint DEFAULT 0,
  aviso_presentado tinyint DEFAULT 0,
  fk_pld_aviso INT DEFAULT NULL,
  
  -- Alertas
  genera_alerta tinyint DEFAULT 0,
  fk_pld_alerta INT DEFAULT NULL,
  
  -- Estado
  estado VARCHAR(50) DEFAULT 'borrador',                          -- borrador|pendiente_documentacion|completada|cancelada
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
