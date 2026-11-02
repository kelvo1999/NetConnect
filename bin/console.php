<?php
declare(strict_types=1);
namespace NetConnect;
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/bootstrap.php';
$command=$argv[1]??'help';
try {
if($command==='migrate'){
 query('CREATE TABLE IF NOT EXISTS migrations(name VARCHAR(190) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
 foreach(glob(ROOT.'/database/migrations/*.sql') as $file){$name=basename($file);if(query('SELECT 1 FROM migrations WHERE name=?',[$name])->fetchColumn())continue;
 foreach(explode(';',file_get_contents($file)) as $sql)if(trim($sql))db()->exec($sql);
 query('INSERT INTO migrations(name) VALUES(?)',[$name]);echo "Applied $name\n";
 }
 $map=['admin'=>['customers.read','customers.write','packages.write','billing.read','billing.write','tickets.read','tickets.write','audit.read','work.read','work.write','work.update','inventory.read','inventory.write','incidents.write'],'finance'=>['customers.read','billing.read','billing.write'],'support'=>['customers.read','tickets.read','tickets.write'],'network_operator'=>['customers.read','inventory.read','incidents.write'],'technician'=>['work.read','work.update'],'customer'=>[]];
 foreach($map as $role=>$permissions)foreach($permissions as $p)query('INSERT IGNORE INTO role_permissions VALUES(?,?)',[$role,$p]);
 }elseif($command==='create-user-stdin'){
 $data=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
 $role=$data['role']??'super_admin';
 if(!in_array($role,['super_admin','admin','finance','support','technician','network_operator'],true))throw new \InvalidArgumentException('Invalid staff role.');
 if(!filter_var($data['email']??'',FILTER_VALIDATE_EMAIL)||strlen($data['password']??'')<12||trim($data['name']??'')==='')throw new \InvalidArgumentException('Valid name, email and password (12+ characters) required.');
 query('INSERT INTO users(name,email,phone,password_hash,role) VALUES(?,?,?,?,?)',[$data['name'],strtolower($data['email']),phone($data['phone']),password_hash($data['password'],PASSWORD_DEFAULT),$role]);echo "Staff account created.\n";
}elseif($command==='admin'){
 if(!function_exists('readline'))throw new \RuntimeException('CLI readline required.');
 $name=readline('Name: ');$email=readline('Email: ');$mobile=phone(readline('Phone: '));
 if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \InvalidArgumentException('Invalid email.');
 if(PHP_OS_FAMILY==='Windows')throw new \RuntimeException('Run this command in WSL or a Unix terminal with stty for hidden password entry.');
 echo 'Password (12+ characters): ';system('stty -echo');try{$password=trim(fgets(STDIN));}finally{system('stty echo');echo "\n";}
 if(strlen($password)<12)throw new \InvalidArgumentException('Password too short.');
 query('INSERT INTO users(name,email,phone,password_hash,role) VALUES(?,?,?,?,?)',[$name,strtolower($email),$mobile,password_hash($password,PASSWORD_DEFAULT),'super_admin']);echo "Administrator created.\n";
}elseif($command==='worker'){
 if((getenv('NETWORK_DRIVER')?:'simulation')!=='simulation')throw new \RuntimeException('Only the simulation adapter is implemented; no real router actions are permitted.');
 $job=transaction(function(){query("UPDATE jobs SET status='pending',locked_at=NULL WHERE status='processing' AND locked_at<UTC_TIMESTAMP()-INTERVAL 10 MINUTE");$j=query("SELECT * FROM jobs WHERE status='pending' AND available_at<=UTC_TIMESTAMP() AND attempts<5 ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED")->fetch();if($j)query("UPDATE jobs SET status='processing',attempts=attempts+1,locked_at=UTC_TIMESTAMP() WHERE id=?",[$j['id']]);return $j;});
 if(!$job){echo "Queue empty.\n";exit;}
 try {transaction(function()use($job){$p=json_decode($job['payload'],true,512,JSON_THROW_ON_ERROR);
 if($job['kind']==='provision'){$c=query('SELECT * FROM connections WHERE id=? FOR UPDATE',[$p['connection_id']])->fetch();if(!$c)throw new \RuntimeException('Missing connection');$state=(new SimulationProvider)->reconcile($c);query('UPDATE connections SET confirmed_state=? WHERE id=?',[$state,$c['id']]);}
 elseif($job['kind']==='notification')query('INSERT IGNORE INTO notification_logs(job_id,customer_id,message) VALUES(?,?,?)',[$job['id'],$p['customer_id'],$p['message']]);else throw new \RuntimeException('Unknown job kind');
 query("UPDATE jobs SET status='done',locked_at=NULL,last_error=NULL WHERE id=?",[$job['id']]);});echo "Processed job ".$job['id']."\n";
 }catch(\Throwable $e){$attempt=(int)$job['attempts']+1;$delay=min(3600,30*(2**$attempt));query("UPDATE jobs SET status=?,locked_at=NULL,last_error='Job failed; investigate using job ID',available_at=TIMESTAMPADD(SECOND,?,UTC_TIMESTAMP()) WHERE id=?",[$attempt>=5?'failed':'pending',$delay,$job['id']]);throw $e;}
}elseif($command==='schedule'){
 transaction(function(){$rows=query("SELECT id,state_version FROM connections WHERE expires_at<=UTC_TIMESTAMP() AND desired_state='active' FOR UPDATE")->fetchAll();foreach($rows as $c){query("UPDATE connections SET desired_state='suspended',state_version=state_version+1 WHERE id=?",[$c['id']]);enqueue('expire-'.$c['id'].'-'.($c['state_version']+1),'provision',['connection_id'=>(int)$c['id']]);}});echo "Expiry processing complete.\n";
}elseif($command==='seed-demo'){
 if(getenv('APP_ENV')!=='local')throw new \RuntimeException('Demo seeding requires APP_ENV=local.');
 query('INSERT INTO packages(name,download_mbps,upload_mbps,price_cents,duration_days,fair_usage) VALUES(?,?,?,?,?,?)',['DEMO — Home 10',10,10,250000,30,'Demonstration package; replace before launch.']);echo "Demo package created.\n";
}else echo "Commands: migrate, admin, create-user-stdin, seed-demo, worker (one job), schedule\n";
}catch(\Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
