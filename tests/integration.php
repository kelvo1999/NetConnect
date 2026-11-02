<?php
declare(strict_types=1);
namespace NetConnect;
if(PHP_SAPI!=='cli')exit;
$testDsn=getenv('NETCONNECT_TEST_DSN');
if(!$testDsn||!preg_match('/(?:^|;)dbname=netconnect_test(?:;|$)/',$testDsn)){fwrite(STDERR,"Use a separate empty database named exactly netconnect_test and set NETCONNECT_TEST_DSN.\n");exit(1);}
// bootstrap must not override the explicit test database selection.
putenv('DB_DSN='.$testDsn);
require __DIR__.'/../app/bootstrap.php';
if(query("SHOW TABLES LIKE 'users'")->fetchColumn()){fwrite(STDERR,"Refusing an initialized database. Create an empty disposable netconnect_test database.\n");exit(1);}
foreach(glob(ROOT.'/database/migrations/*.sql') as $file)foreach(explode(';',file_get_contents($file)) as $sql)if(trim($sql))db()->exec($sql);
$count=0;
function check(bool $condition,string $name):void {global $count;if(!$condition)throw new \RuntimeException('FAIL: '.$name);$count++;echo 'PASS: '.$name."\n";}
try {
query("INSERT INTO users(name,email,phone,password_hash,role) VALUES('Test operator','operator@example.invalid','+254700000001',?,'super_admin')",[password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]);$actor=(int)db()->lastInsertId();
query("INSERT INTO users(name,email,phone,password_hash) VALUES('Test customer','customer@example.invalid','+254700000002',?)",[password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]);$uid=(int)db()->lastInsertId();
query('INSERT INTO customers(user_id,account_number) VALUES(?,?)',[$uid,'TEST-0001']);$cid=(int)db()->lastInsertId();
query("INSERT INTO packages(name,download_mbps,upload_mbps,price_cents,duration_days) VALUES('Test package',10,10,10000,30)");$package=(int)db()->lastInsertId();
query("INSERT INTO connections(customer_id,package_id,label,address) VALUES(?,?,'Test connection','Test only')",[$cid,$package]);$connection=(int)db()->lastInsertId();
$invoice=Billing::invoice($actor,$connection);
Billing::record($actor,$invoice,4000,'TEST-PARTIAL');
check((int)query('SELECT paid_cents FROM invoices WHERE id=?',[$invoice])->fetchColumn()===4000,'partial allocation');
check((int)query('SELECT COUNT(*) FROM subscriptions')->fetchColumn()===0,'partial payment does not renew');
$payment=Billing::record($actor,$invoice,8000,'TEST-FINAL');
check((int)query('SELECT amount_cents FROM credits WHERE payment_id=?',[$payment])->fetchColumn()===2000,'overpayment credit');
check(query('SELECT status FROM invoices WHERE id=?',[$invoice])->fetchColumn()==='paid','paid invoice');
check((int)query('SELECT COUNT(*) FROM subscriptions')->fetchColumn()===1,'one subscription');
$expiry=query('SELECT expires_at FROM connections WHERE id=?',[$connection])->fetchColumn();
check(query('SELECT desired_state FROM connections WHERE id=?',[$connection])->fetchColumn()==='suspended','installation gate remains closed');
check(Billing::record($actor,$invoice,8000,'TEST-FINAL')===$payment,'same reference is idempotent');
check((int)query('SELECT COUNT(*) FROM payments')->fetchColumn()===2,'duplicate creates no payment');
check(query('SELECT expires_at FROM connections WHERE id=?',[$connection])->fetchColumn()===$expiry,'duplicate does not renew twice');
check((int)query('SELECT COUNT(*) FROM jobs')->fetchColumn()===3,'one provisioning and two receipt jobs');
try{Billing::record($actor,$invoice,9000,'TEST-FINAL');throw new \RuntimeException('Mismatched reference accepted');}catch(\InvalidArgumentException $e){check(true,'mismatched duplicate rejected');}
echo "$count integration checks passed. Disposable fixtures retained for inspection; reset the isolated test database before rerunning.\n";
}catch(\Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
