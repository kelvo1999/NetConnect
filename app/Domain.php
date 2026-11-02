<?php
declare(strict_types=1);
namespace NetConnect;
final class Money {
    public static function parse(string $value): int {
        if(!preg_match('/\A(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?\z/',$value,$m))throw new \InvalidArgumentException('Enter a valid KES amount with up to two decimals.');
        return ((int)$m[1])*100+(int)str_pad($m[2]??'',2,'0');
    }
    public static function format(int $cents): string {return ($cents<0?'-':'').intdiv(abs($cents),100).'.'.str_pad((string)(abs($cents)%100),2,'0',STR_PAD_LEFT);}
}
function phone(string $value): string {$v=preg_replace('/[\s()-]/','',$value);if(preg_match('/^0([17][0-9]{8})$/',$v,$m))return '+254'.$m[1];if(preg_match('/^254([17][0-9]{8})$/',$v,$m))return '+'.$v;if(preg_match('/^\+254[17][0-9]{8}$/',$v))return $v;throw new \InvalidArgumentException('Use a Kenyan mobile number, for example 0712345678.');}
function renewalEnd(\DateTimeImmutable $now,?\DateTimeImmutable $expires,int $days): \DateTimeImmutable {if($days<1||$days>3660)throw new \InvalidArgumentException('Invalid duration.');return ($expires && $expires>$now ? $expires : $now)->modify('+'.$days.' days');}
final class Billing {
    public static function invoice(int $actor,int $connection): int {return transaction(function()use($actor,$connection){
        $c=query('SELECT c.*,p.name,p.price_cents,p.duration_days FROM connections c JOIN packages p ON p.id=c.package_id WHERE c.id=? FOR UPDATE',[$connection])->fetch();
        if(!$c)throw new \InvalidArgumentException('Connection not found.');
        query('INSERT INTO invoices(reference,customer_id,connection_id,total_cents,duration_days,package_name,due_at) VALUES(?,?,?,?,?,?,UTC_TIMESTAMP())',['INV-'.bin2hex(random_bytes(12)),$c['customer_id'],$connection,$c['price_cents'],$c['duration_days'],$c['name']]);$id=(int)db()->lastInsertId();
        query('INSERT INTO invoice_items(invoice_id,description,quantity,unit_cents,total_cents) VALUES(?,?,1,?,?)',[$id,$c['name'].' subscription',$c['price_cents'],$c['price_cents']]);audit($actor,'invoice.create','invoice',$id);return $id;
    });}
    public static function record(int $actor,int $invoice,int $amount,string $reference): int {
        if($amount<1 || strlen($reference)<3 || strlen($reference)>100)throw new \InvalidArgumentException('A positive amount and unique payment reference are required.');
        return transaction(function()use($actor,$invoice,$amount,$reference){
            $i=query('SELECT * FROM invoices WHERE id=? FOR UPDATE',[$invoice])->fetch();if(!$i)throw new \InvalidArgumentException('Invoice not found.');
            $duplicate=query('SELECT * FROM payments WHERE reference=?',[$reference])->fetch();
            if($duplicate){if((int)$duplicate['invoice_id']!==$invoice||(int)$duplicate['amount_cents']!==$amount)throw new \InvalidArgumentException('Reference already belongs to a different payment.');return (int)$duplicate['id'];}
            query('INSERT INTO payments(reference,customer_id,invoice_id,amount_cents,method,recorded_by) VALUES(?,?,?,?,?,?)',[$reference,$i['customer_id'],$invoice,$amount,'manual',$actor]);$pid=(int)db()->lastInsertId();
            $allocated=min($amount,max(0,(int)$i['total_cents']-(int)$i['paid_cents']));
            query('INSERT INTO allocations(payment_id,invoice_id,amount_cents) VALUES(?,?,?)',[$pid,$invoice,$allocated]);
            if($amount>$allocated)query('INSERT INTO credits(customer_id,payment_id,amount_cents) VALUES(?,?,?)',[$i['customer_id'],$pid,$amount-$allocated]);
            $paid=(int)$i['paid_cents']+$allocated;$status=$paid>=(int)$i['total_cents']?'paid':'partial';
            query('UPDATE invoices SET paid_cents=?,status=? WHERE id=?',[$paid,$status,$invoice]);
            if($status==='paid' && !$i['fulfilled_at']){
                $c=query('SELECT * FROM connections WHERE id=? FOR UPDATE',[$i['connection_id']])->fetch();
                $now=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$old=$c['expires_at']?new \DateTimeImmutable($c['expires_at'],new \DateTimeZone('UTC')):null;$end=renewalEnd($now,$old,(int)$i['duration_days'])->format('Y-m-d H:i:s');
                query('INSERT INTO subscriptions(connection_id,invoice_id,starts_at,ends_at,price_cents,package_name) VALUES(?,?,?,?,?,?)',[$c['id'],$invoice,($old&&$old>$now?$old:$now)->format('Y-m-d H:i:s'),$end,$i['total_cents'],$i['package_name']]);
                // Installation clearance is independent from payment.
                $desired=$c['installation_status']==='completed'?'active':'suspended';
                query('UPDATE connections SET expires_at=?,desired_state=?,state_version=state_version+1 WHERE id=?',[$end,$desired,$c['id']]);
                query('UPDATE invoices SET fulfilled_at=UTC_TIMESTAMP() WHERE id=?',[$invoice]);
                enqueue('invoice-'.$invoice,'provision',['connection_id'=>(int)$c['id']]);
            }
            enqueue('receipt-'.$pid,'notification',['customer_id'=>(int)$i['customer_id'],'message'=>'Payment received. Reference '.$reference.'; KES '.Money::format($amount)]);
            audit($actor,'payment.record','payment',$pid);return $pid;
        });
    }
}
interface NetworkProvider {public function reconcile(array $connection): string;}
final class SimulationProvider implements NetworkProvider {public function reconcile(array $connection): string {return 'simulated:'.$connection['desired_state'];}}
