<?php
/** Prepara una base nueva y copia la demostración sin borrar la fuente SQLite. */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{Artisan,DB};

$database = 'docencia_io';
$source = base_path('database/demo.sqlite');
if (! is_file($source)) {
    fwrite(STDERR, "No se encontró la base SQLite de origen.\n"); exit(1);
}
$settings = config('database.connections.mysql');
$settings['host'] = '127.0.0.1';
$settings['port'] = '3306';
$settings['database'] = $database;
try {
    $server = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $settings['username'], $settings['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo autenticar en MySQL80. Revise DB_USERNAME y DB_PASSWORD en .env.\n"); exit(1);
}
$check = $server->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?');
$check->execute([$database]);
if ((int)$check->fetchColumn() > 0) {
    fwrite(STDERR, "La base docencia_io ya contiene tablas. No se reemplazó ni borró información.\n"); exit(1);
}
$backupDir = storage_path('app/private/backups');
if (! is_dir($backupDir)) mkdir($backupDir, 0775, true);
$stamp = date('Ymd-His');
copy($source, $backupDir.'/demo-pre-mysql-'.$stamp.'.sqlite');
copy(base_path('.env'), $backupDir.'/env-pre-mysql-'.$stamp.'.txt');
$server->exec('CREATE DATABASE IF NOT EXISTS `docencia_io` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
config(['database.connections.mysql'=>$settings,'database.connections.origen_sqlite'=>['driver'=>'sqlite','database'=>$source,'prefix'=>'','foreign_key_constraints'=>true]]);
DB::purge('mysql');
if (Artisan::call('migrate', ['--database'=>'mysql','--force'=>true]) !== 0) {
    fwrite(STDERR, Artisan::output()); exit(1);
}
echo Artisan::output();
$target = DB::connection('mysql');
$origin = DB::connection('origen_sqlite');
$tables = $origin->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name <> 'migrations' ORDER BY name");
$target->statement('SET FOREIGN_KEY_CHECKS=0');
$counts = [];
try {
    $target->transaction(function () use ($tables, $origin, $target, &$counts) {
        foreach ($tables as $entry) {
            $table = $entry->name;
            if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('Nombre de tabla inesperado.');
            if ($target->table($table)->exists()) throw new RuntimeException('La tabla destino ya tiene datos: '.$table);
            $dateColumns = [];
            foreach ($target->select('SHOW COLUMNS FROM `'.$table.'`') as $column) {
                if (strtolower($column->Type) === 'date') $dateColumns[] = $column->Field;
            }
            $rows = $origin->table($table)->get();
            foreach ($rows->chunk(100) as $chunk) {
                $payload = $chunk->map(function ($row) use ($dateColumns) {
                    $data = (array)$row;
                    foreach ($dateColumns as $column) if (! empty($data[$column])) $data[$column] = substr($data[$column], 0, 10);
                    return $data;
                })->all();
                if ($payload) $target->table($table)->insert($payload);
            }
            $counts[$table] = $target->table($table)->count();
            if ($counts[$table] !== $rows->count()) throw new RuntimeException('No coincide el conteo de '.$table);
        }
    });
} finally {
    $target->statement('SET FOREIGN_KEY_CHECKS=1');
}
$envPath = base_path('.env');
$environment = file_get_contents($envPath);
foreach (['DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>$database,'APP_URL'=>'http://127.0.0.1:8000'] as $key=>$value) {
    $environment = preg_replace('/^'.preg_quote($key,'/').'=.*$/m', $key.'='.$value, $environment);
}
file_put_contents($envPath, $environment);
Artisan::call('config:clear');
echo json_encode(['motor'=>$server->getAttribute(PDO::ATTR_SERVER_VERSION),'base'=>$database,'filas'=>$counts,'respaldo_sqlite'=>true],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
