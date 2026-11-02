<?php
function cart_count(): int { $n=0; foreach($_SESSION['cart'] ?? [] as $item){$n+=(int)$item;} return $n; }
function cart_add(string $id, int $quantity=1): array {
    $product=shop_product($id);
    if(!$product) return ['ok'=>false,'message'=>'That product is unavailable.'];
    $current=(int)($_SESSION['cart'][$id] ?? 0);
    $requested=$current+$quantity;
    if($requested > (int)$product['stock']) return ['ok'=>false,'message'=>'Only '.(int)$product['stock'].' item(s) are available.'];
    $_SESSION['cart'][$id]=$requested;
    return ['ok'=>true,'message'=>'Added to your cart.'];
}
function cart_set(string $id,int $quantity): bool {
    $product=shop_product($id); if(!$product)return false;
    if($quantity<=0){unset($_SESSION['cart'][$id]);return true;}
    if($quantity>(int)$product['stock'])return false;
    $_SESSION['cart'][$id]=$quantity; return true;
}
function cart_items(): array { $items=[]; foreach($_SESSION['cart'] ?? [] as $id=>$qty){$product=shop_product($id);if(!$product)continue;$items[]=['product'=>$product,'quantity'=>(int)$qty,'line_total'=>(int)$product['price']*(int)$qty];}return $items; }
function cart_subtotal(): int { $total=0; foreach(cart_items() as $item){$total+=(int)$item['line_total'];}return $total; }
