# Compliance PLD México — Módulo Dolibarr

Módulo de **Prevención de Lavado de Dinero (PLD)** para Dolibarr ERP.
Cumplimiento **LFPIORPI Art. 17 Fracciones V, VIII, XI, XII, XIII y XV** —
actividades vulnerables de compra-venta de vehículos en México.

Genera, firma y prepara el envío de **avisos XML** al portal SPPLD del SAT.

<!--
![Screenshot modulecompliancepld](img/screenshot_modulecompliancepld.png?raw=true "Modulecompliancepld"){imgmd}
-->

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files in the module directories under `langs`.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more information, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


## Installation

Prerequisites: You must have Dolibarr ERP & CRM software installed. You can download it from [Dolistore.org](https://www.dolibarr.org).
You can also get a ready-to-use instance in the cloud from https://saas.dolibarr.org


### From the ZIP file and GUI interface

If the module is a ready-to-deploy zip file, so with a name `module_xxx-version.zip` (e.g., when downloading it from a marketplace like [Dolistore](https://www.dolistore.com)),
go to menu `Home> Setup> Modules> Deploy external module` and upload the zip file.

Note: If this screen tells you that there is no "custom" directory, check that your setup is correct:

<!--

- In your Dolibarr installation directory, edit the `htdocs/conf/conf.php` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading `//`) and assign the proper value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
-->

<!--

### From a GIT repository

Clone the repository in `$dolibarr_main_document_root_alt/modulecompliancepld`

```shell
cd ....../custom
git clone git@github.com:gitlogin/modulecompliancepld.git modulecompliancepld
```

-->

### Final steps

Using your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup"> "Modules"
  - You should now be able to find and enable the module



---

## Instalación de e.firma (FIEL SAT) — Instrucciones para el implementador técnico

La generación de XMLs firmados requiere que el **sujeto obligado** cuente con
su e.firma vigente (antes FIEL) emitida por el SAT. Esta consiste en dos archivos:

| Archivo | Descripción |
|---------|-------------|
| `*.cer` | Certificado público X.509 |
| `*.key` | Llave privada cifrada |

Además se necesita la **contraseña** que protege el archivo `.key`.

### Paso 1 — Crear el directorio seguro en el servidor

El directorio debe quedar **fuera del docroot** de Apache/Nginx para que
no sea accesible por HTTP. Ejemplo en Linux:

```bash
mkdir -p /var/datos_pld/efirma
chmod 700 /var/datos_pld/efirma
chown www-data:www-data /var/datos_pld/efirma   # o el usuario del servidor web
```

> **Nunca** colocar estos archivos dentro de `htdocs/` ni de ninguna carpeta
> que el servidor web sirva públicamente.

### Paso 2 — Copiar los archivos al servidor

```bash
scp certificado_empresa.cer usuario@servidor:/var/datos_pld/efirma/
scp llave_privada.key       usuario@servidor:/var/datos_pld/efirma/
chmod 600 /var/datos_pld/efirma/*.cer /var/datos_pld/efirma/*.key
```

### Paso 3 — Configurar las rutas y contraseña en Dolibarr

En `Configuración > Módulos > Compliance PLD > Ajustes`, registrar:

| Constante | Valor ejemplo |
|-----------|---------------|
| `MODULECOMPLIANCEPLD_RFC_SUJETO` | RFC del sujeto obligado (12 o 13 chars) |
| `MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH` | `/var/datos_pld/efirma/certificado.cer` |
| `MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH` | `/var/datos_pld/efirma/llave_privada.key` |
| `MODULECOMPLIANCEPLD_EFIRMA_PASSWORD` | Contraseña del archivo `.key` |

> La contraseña queda almacenada en la base de datos de Dolibarr (tabla
> `llx_const`). Asegurarse de que la BD esté protegida y con backups cifrados.

### Consideraciones de seguridad

- La llave privada **nunca debe viajar por la red en texto plano**; usar siempre SCP/SFTP.
- El proceso de firma ocurre enteramente en el servidor; el archivo `.key` nunca sale.
- Renovar la e.firma antes de su vencimiento (4 años desde su emisión) en el SAT.
- Ante pérdida o compromiso de la llave privada, revocar inmediatamente ante el SAT.

### Verificar la configuración

Desde la UI de administración del módulo (`Acerca de > e.firma`) se puede
ejecutar una prueba de validación que verifica que los archivos existen y son
legibles por PHP sin realizar ninguna firma real.

---

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readme's are licensed under [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).
