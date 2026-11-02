<?php
// Placeholder delivery fees only. Change these after real courier and local delivery rates are confirmed.
return [
    'site_name' => 'TraveL Den Adventures Shop',
    'currency' => 'KES',
    'currency_label' => 'KSh',
    'delivery' => [
        'pickup' => ['label' => 'Customer pickup', 'fee' => 0, 'requires_address' => false, 'description' => 'Arrange pickup details before collection.'],
        'local' => ['label' => 'Local delivery', 'fee' => 500, 'requires_address' => true, 'description' => 'Placeholder fee for local Nairobi-area delivery.'],
        'courier' => ['label' => 'Courier shipping', 'fee' => 1200, 'requires_address' => true, 'description' => 'Placeholder fee; final courier price depends on destination and size.'],
    ],
];
