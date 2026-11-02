<?php
function shop_validate_checkout(array $input,array $config): array {
    $errors=[];$clean=[];
    foreach(['full_name','email','phone','delivery_method','county','address','notes'] as $key){$clean[$key]=trim((string)($input[$key]??''));}
    if(mb_strlen($clean['full_name'])<2||mb_strlen($clean['full_name'])>100)$errors['full_name']='Enter your full name.';
    if(!filter_var($clean['email'],FILTER_VALIDATE_EMAIL)||mb_strlen($clean['email'])>254)$errors['email']='Enter a valid email address.';
    if(!preg_match('/^[0+][0-9 ()-]{7,19}$/',$clean['phone']))$errors['phone']='Enter a valid phone number.';
    if(!isset($config['delivery'][$clean['delivery_method']]))$errors['delivery_method']='Choose a valid delivery method.';
    if(mb_strlen($clean['county'])<2||mb_strlen($clean['county'])>100)$errors['county']='Enter your county or pickup location.';
    if(isset($config['delivery'][$clean['delivery_method']])&&$config['delivery'][$clean['delivery_method']]['requires_address']&&mb_strlen($clean['address'])<8)$errors['address']='Enter a complete delivery address.';
    if(mb_strlen($clean['notes'])>1000)$errors['notes']='Order notes must be 1000 characters or fewer.';
    return [$clean,$errors];
}
