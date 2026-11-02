<?php
function shop_create_order(array $customer,array $items,int $subtotal,int $delivery_fee,string $delivery_method): array {
    $directory=__DIR__.'/../data';
    $lock=fopen($directory.'/orders.lock','c'); if(!$lock)return ['ok'=>false];
    flock($lock,LOCK_EX);
    $order_number='TD'.gmdate('Ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
    $order=['order_number'=>$order_number,'customer'=>$customer,'items'=>$items,'subtotal'=>$subtotal,'delivery_fee'=>$delivery_fee,'total'=>$subtotal+$delivery_fee,'delivery_method'=>$delivery_method,'status'=>'pending_payment','payment_status'=>'pending','gateway'=>null,'gateway_reference'=>null,'internal_notes'=>'','created_at'=>gmdate('c')];
    $file=$directory.'/orders.json'; $orders=[];
    if(is_file($file)){ $decoded=json_decode((string)file_get_contents($file),true); if(is_array($decoded))$orders=$decoded; }
    $orders[]=$order;
    if(!file_put_contents($file,json_encode($orders,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),LOCK_EX)){flock($lock,LOCK_UN);fclose($lock);return ['ok'=>false];}
    flock($lock,LOCK_UN); fclose($lock); return ['ok'=>true,'order'=>$order];
}
function shop_find_order(string $number): ?array {
    $file=__DIR__.'/../data/orders.json';if(!is_file($file))return null;$orders=json_decode((string)file_get_contents($file),true);if(!is_array($orders))return null;foreach(array_reverse($orders) as $order){if(hash_equals((string)$order['order_number'],$number)&&!empty($_SESSION['confirmed_orders'][$number]))return $order;}return null;
}
