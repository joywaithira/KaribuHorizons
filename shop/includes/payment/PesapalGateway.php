<?php
require_once __DIR__.'/PaymentGatewayInterface.php';
// PLACEHOLDER ONLY: hosted Pesapal checkout is intentionally not implemented.
// Configure credentials only on the server through environment variables when this is enabled.
// Do not put secrets, test credentials, consumer keys, or private API URLs in this file or Git.
class PesapalGateway implements PaymentGatewayInterface {
    public function createPaymentRequest(array $order): array { return ['status'=>'not_configured','message'=>'Pesapal is not connected.']; }
    public function redirectToPayment(array $order): never { throw new RuntimeException('Pesapal is not configured.'); }
    public function verifyPayment(array $order,array $gatewayResponse): bool { return false; }
    public function handleCallback(array $payload): array { return ['status'=>'not_configured']; }
    public function getPaymentStatus(array $order): string { return 'pending'; }
}
