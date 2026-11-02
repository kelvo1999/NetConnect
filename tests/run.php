<?php
declare(strict_types=1);
namespace NetConnect;
require __DIR__.'/../app/Domain.php';
$count=0;
function same(mixed $actual,mixed $expected):void {global $count;$count++;if($actual!==$expected)throw new \RuntimeException('Failed case '.$count.': '.var_export($actual,true));}
same(Money::parse('2500.50'),250050);same(Money::format(250050),'2500.50');same(Money::parse('0.01'),1);same(Money::format(-101),'-1.01');same(phone('0712 345 678'),'+254712345678');same(phone('0112345678'),'+254112345678');
foreach(['-1','1.001','1e3','99999999999999999','00.20'] as $bad){try{Money::parse($bad);throw new \RuntimeException('Accepted bad money');}catch(\InvalidArgumentException $e){$count++;}}
$now=new \DateTimeImmutable('2026-10-30 12:00:00',new \DateTimeZone('UTC'));
same(renewalEnd($now,new \DateTimeImmutable('2026-11-01 12:00:00',new \DateTimeZone('UTC')),30)->format('Y-m-d H:i:s'),'2026-12-01 12:00:00');
same(renewalEnd($now,new \DateTimeImmutable('2026-10-01',new \DateTimeZone('UTC')),30)->format('Y-m-d H:i:s'),'2026-11-29 12:00:00');
same((new SimulationProvider)->reconcile(['desired_state'=>'active']),'simulated:active');
echo "$count domain checks passed. Database and HTTP tests are separate and not covered by this command.\n";
