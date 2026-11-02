<?php
declare(strict_types=1);
namespace NetConnect;
const ROOT = __DIR__ . '/..';
if (is_file(ROOT.'/.env')) foreach (file(ROOT.'/.env', FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line),'#') || !str_contains($line,'=')) continue;
    [$key,$value]=explode('=',$line,2); if (getenv(trim($key))===false) putenv(trim($key).'='.trim($value));
}
date_default_timezone_set('UTC');
require_once __DIR__.'/Domain.php';
function db(): \PDO {
    static $pdo;
    return $pdo ??= new \PDO(getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=netconnect;charset=utf8mb4',getenv('DB_USER') ?: 'root',getenv('DB_PASSWORD') ?: '',[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC,\PDO::ATTR_EMULATE_PREPARES=>false]);
}
function query(string $sql,array $params=[]): \PDOStatement {$s=db()->prepare($sql);$s->execute($params);return $s;}
function transaction(callable $fn): mixed {db()->beginTransaction();try {$result=$fn();db()->commit();return $result;}catch(\Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}}
function e(mixed $s): string {return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function audit(int $actor,string $action,string $type,int $id): void {query('INSERT INTO audit_events(actor_id,action,entity_type,entity_id) VALUES(?,?,?,?)',[$actor,$action,$type,$id]);}
function enqueue(string $key,string $kind,array $payload): void {query('INSERT IGNORE INTO jobs(dedupe_key,kind,payload) VALUES(?,?,?)',[$key,$kind,json_encode($payload,JSON_THROW_ON_ERROR)]);}
function currentUser(): ?array {return isset($_SESSION['uid']) ? query('SELECT id,name,email,phone,role,status FROM users WHERE id=?',[$_SESSION['uid']])->fetch() ?: null : null;}
function allowed(array $user,string $permission): bool {
    if($user['status']!=='active')return false;
    if($user['role']==='super_admin')return true;
    return (bool)query('SELECT 1 FROM role_permissions WHERE role=? AND permission=?',[$user['role'],$permission])->fetchColumn();
}
function requirePermission(array $u,string $p): void {if(!allowed($u,$p)){http_response_code(403);exit('Access denied.');}}
function csrf(): void {if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))){http_response_code(419);exit('Form expired. Refresh and retry.');}}
function redirect(string $url): never {header('Location: '.$url);exit;}
