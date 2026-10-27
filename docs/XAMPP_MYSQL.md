# Ejecución local con Apache XAMPP y MySQL80

Apache sirve la aplicación en http://127.0.0.1:8000. Su DocumentRoot apunta exclusivamente a public/. La configuración del sitio está en config/apache-docencia.conf y se incluye desde C:/xampp/apache/conf/httpd.conf. El PHP usado por Apache es C:/xampp/php/php.exe (8.2.12), con extensiones ZIP y GD habilitadas. iniciar.bat abre esta dirección; no inicia el servidor de desarrollo de Laravel.

La conexión MySQL de destino es 127.0.0.1:3306, usuario root, base docencia_io. Workbench administra esa instancia; no se inicia ni se sustituye por el MySQL de XAMPP.

La contraseña de MySQL fue configurada en .env y se verificó la conexión el 27/10/2026. La base docencia_io ya contiene los datos trasladados. Los comandos siguientes se documentan como referencia de la preparación inicial; no deben repetirse para reemplazar esta base:

```powershell
C:\xampp\php\php.exe scripts/conectar-mysql.php
C:\xampp\php\php.exe artisan config:clear
```

El script rechaza bases que ya contengan tablas, respalda el SQLite de origen y el archivo .env en storage/app/private/backups, crea las tablas de MySQL y copia los datos conservando cuentas e historiales. Solo después de verificar los conteos cambia la conexión de la aplicación a MySQL. No ejecutar este script para reemplazar una base que ya esté en uso.
