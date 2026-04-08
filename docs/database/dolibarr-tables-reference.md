# Dolibarr SQL Tables - Thematic Learning Guide

## 1. Accounting / Contabilidad

### Main Tables
- **llx_accounting_account** - Chart of accounts
- **llx_accounting_system** - Accounting system configuration
- **llx_accountingtransaction** - Accounting transactions
- **llx_accountingdebcred** - Debits and credits

### Related Tables
- **llx_compta** - Accounting entries (legacy)
- **llx_compta_account** - Accounting accounts
- **llx_compta_compte_generaux** - General ledger accounts
- **llx_export_compta** - Accounting export records

---

## 2. Third Parties & Contacts / Terceros y Contactos

### Companies
- **llx_societe** - Companies/Organizations (main table)
- **llx_societe_account** - Third party accounts
- **llx_societe_address** - Company addresses
- **llx_societe_commerciaux** - Sales representatives assignment
- **llx_societe_log** - Company modification history
- **llx_societe_prices** - Customer-specific pricing
- **llx_societe_rib** - Bank account details (RIB)

### Discounts
- **llx_societe_remise** - Customer discounts
- **llx_societe_remise_except** - Special discount exceptions
- **llx_societe_remise_supplier** - Supplier discounts

### Contacts
- **llx_socpeople** - Contacts/People associated with companies

---

## 3. Products & Services / Productos y Servicios

### Core Product Tables
- **llx_product** - Products and services (main table)
- **llx_product_price** - Product price history
- **llx_product_lang** - Product translations
- **llx_product_batch** - Product batches/lots tracking
- **llx_product_lot** - Lot/serial number details

### Supplier Pricing
- **llx_product_fournisseur_price** - Supplier prices
- **llx_product_fournisseur_price_log** - Supplier price history

### Stock Management
- **llx_product_stock** - Stock levels by warehouse
- **llx_stock_mouvement** - Stock movements history
- **llx_entrepot** - Warehouses

---

## 4. Sales / Ventas

### Proposals
- **llx_propal** - Commercial proposals/quotes
- **llx_propaldet** - Proposal line items

### Orders
- **llx_commande** - Customer orders
- **llx_commandedet** - Order line items

### Invoices
- **llx_facture** - Customer invoices
- **llx_facturedet** - Invoice line items
- **llx_facture_rec** - Recurring invoices templates
- **llx_facturedet_rec** - Recurring invoice line items

### Shipments
- **llx_expedition** - Shipments
- **llx_expeditiondet** - Shipment line items
- **llx_expeditiondet_batch** - Batch/lot details for shipments
- **llx_livraison** - Deliveries
- **llx_livraisondet** - Delivery line items

---

## 5. Purchases / Compras

### Supplier Orders
- **llx_commande_fournisseur** - Supplier orders
- **llx_commande_fournisseurdet** - Supplier order line items
- **llx_commande_fournisseur_dispatch** - Receipt dispatch details
- **llx_commande_fournisseur_log** - Supplier order history

### Supplier Invoices
- **llx_facture_fourn** - Supplier invoices
- **llx_facture_fourn_det** - Supplier invoice line items

---

## 6. Payments / Pagos

### Customer Payments
- **llx_paiement** - Customer payments
- **llx_paiement_facture** - Payment-invoice linkage

### Supplier Payments
- **llx_paiementfourn** - Supplier payments
- **llx_paiementfourn_facturefourn** - Supplier payment-invoice linkage

### Social/Tax Charges
- **llx_chargesociales** - Social and tax charges
- **llx_paiementcharge** - Social charge payments

### Direct Debit
- **llx_prelevement_bons** - Direct debit batches
- **llx_prelevement_lignes** - Direct debit lines
- **llx_prelevement_facture** - Invoice direct debit links
- **llx_prelevement_facture_demande** - Direct debit requests
- **llx_prelevement_notifications** - Notifications
- **llx_prelevement_rejet** - Rejected direct debits

---

## 7. Banking / Banca

### Bank Accounts
- **llx_bank_account** - Bank accounts
- **llx_bank** - Bank transactions
- **llx_bank_url** - Transaction URLs/links
- **llx_bank_categ** - Bank transaction categories
- **llx_category_bankline** - Category assignment for bank lines

### Check Management
- **llx_bordereau_cheque** - Check deposits

### Point of Sale
- **llx_pos_cash_fence** - POS cash register movements

---

## 8. Projects & Tasks / Proyectos y Tareas

- **llx_projet** - Projects
- **llx_projet_task** - Project tasks
- **llx_projet_task_actors** - Task assignments
- **llx_element_time** - Time tracking entries

---

## 9. Contracts & Interventions / Contratos e Intervenciones

### Contracts
- **llx_contrat** - Contracts
- **llx_contratdet** - Contract lines/services
- **llx_contratdet_log** - Contract line history

### Interventions
- **llx_fichinter** - Intervention records
- **llx_fichinterdet** - Intervention details
- **llx_deplacement** - Travel/displacement records

---

## 10. CRM & Activities / CRM y Actividades

- **llx_actioncomm** - Events/Actions/Agenda items
- **llx_element_contact** - Contact roles on elements

---

## 11. Members / Miembros (Associations)

- **llx_adherent** - Members
- **llx_adherent_type** - Member types
- **llx_adherent_options** - Custom fields for members
- **llx_adherent_options_label** - Custom field labels
- **llx_subscription** - Member subscriptions
- **llx_don** - Donations

---

## 12. Users & Permissions / Usuarios y Permisos

### Users
- **llx_user** - System users
- **llx_user_param** - User preferences
- **llx_user_rights** - User permissions
- **llx_user_alert** - User alerts
- **llx_user_clicktodial** - Click-to-dial settings
- **llx_user_entrepot** - User-warehouse assignments

### User Groups
- **llx_usergroup** - User groups
- **llx_usergroup_user** - User-group membership
- **llx_usergroup_rights** - Group permissions

### Rights Definition
- **llx_rights_def** - Permission definitions

---

## 13. Categories / Categorías

- **llx_categorie** - Categories (main table)
- **llx_categorie_association** - Sub-category relationships
- **llx_categorie_product** - Product categorization
- **llx_categorie_societe** - Company categorization
- **llx_categorie_fournisseur** - Supplier categorization
- **llx_categorie_member** - Member categorization
- **llx_categorie_ticket** - Ticket categorization

---

## 14. Documents & ECM / Documentos y GED

- **llx_ecm_directories** - Document directories/folders
- **llx_ecm_files** - File metadata
- **llx_document** - Document records
- **llx_document_model** - Document templates

---

## 15. Dictionaries & References / Diccionarios y Referencias

### Geographic
- **llx_c_country** - Countries
- **llx_c_regions** - Regions
- **llx_c_departements** - Departments/States

### Business
- **llx_c_civilite** - Civilities/titles (Mr, Mrs, etc.)
- **llx_c_forme_juridique** - Legal forms
- **llx_c_typent** - Company types
- **llx_c_effectif** - Staff size ranges
- **llx_c_prospectlevel** - Prospect levels
- **llx_c_stcomm** - Commercial status codes

### Financial
- **llx_c_paiement** - Payment methods
- **llx_c_payment_term** - Payment terms
- **llx_c_currencies** - Currencies
- **llx_c_tva** - VAT rates
- **llx_c_revenuestamp** - Revenue stamps
- **llx_c_chargesociales** - Social charge types

### Operations
- **llx_c_actioncomm** - Action/event types
- **llx_c_action_trigger** - Trigger actions
- **llx_c_type_contact** - Contact types/roles
- **llx_c_availability** - Availability codes
- **llx_c_shipment_mode** - Shipping methods
- **llx_c_propalst** - Proposal statuses
- **llx_c_input_method** - Input methods
- **llx_c_input_reason** - Input reasons

### Technical
- **llx_c_barcode_type** - Barcode types
- **llx_c_paper_format** - Paper formats
- **llx_c_ecotaxe** - Eco-tax rates
- **llx_c_type_fees** - Fee types

---

## 16. System Configuration / Configuración del Sistema

- **llx_const** - System constants/configuration
- **llx_dolibarr_modules** - Installed modules
- **llx_menu** - Menu entries
- **llx_boxes** - Dashboard widgets instances
- **llx_boxes_def** - Dashboard widget definitions
- **llx_bookmark** - User bookmarks
- **llx_events** - System events log
- **llx_cronjob** - Scheduled jobs

---

## 17. Notifications & Alerts / Notificaciones y Alertas

- **llx_notify** - Notification history
- **llx_notify_def** - Notification definitions
- **llx_mailing** - Email campaigns
- **llx_mailing_cibles** - Mailing targets/recipients

---

## 18. Support / Soporte

- **llx_ticket** - Support tickets

---

## 19. Tax Management / Gestión Fiscal

- **llx_tva** - VAT/Tax collected
- **llx_chargesociales** - Social/fiscal charges

---

## 20. Links & Relationships / Enlaces y Relaciones

- **llx_element_element** - Generic element-to-element links

---

## 21. Import/Export / Importación/Exportación

- **llx_import_model** - Import profiles
- **llx_export_model** - Export profiles

---

## 22. Extra Fields / Campos Adicionales

- **llx_extrafields** - Custom field definitions

---

## 23. Security & Audit / Seguridad y Auditoría

- **llx_blockedlog** - Immutable audit log (blockchain-style)

---

## Table Naming Convention

All Dolibarr tables follow the prefix **llx_** (legacy from "Logiciels Libres eXpertise").

### Common Suffixes:
- **det** - Detail/line items (e.g., llx_facturedet = invoice lines)
- **_log** - History/audit tables
- **_rec** - Recurring/template records
- **_fourn** / **_fournisseur** - Supplier-related
- **c_** - Dictionary/catalog tables (reference data)

### Key Relationships:
Most transactions follow this pattern:
- Header table (e.g., llx_facture)
- Detail table (e.g., llx_facturedet)
- Payment link table (e.g., llx_paiement_facture)
