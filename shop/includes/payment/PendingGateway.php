<?php
require_once __DIR__.'/PaymentGatewayInterface.php';
class PendingGateway implements PaymentGatewayInterface {
    public function createPaymentRequest(array $order): array { return ['status'=>'pending_payment','message'=>'Payment gateway is not connected yet.']; }
    public function redirectToPayment(array $order): never { throw new RuntimeException('Payment gateway is not configured.'); }
    public function verifyPayment(array $order,array $gatewayResponse): bool { return false; }
    public function handleCallback(array $payload): array { return ['status'=>'payment_failed','message'=>'No payment gateway configured.']; }
    public function getPaymentStatus(array $order): string { return 'pending'; }
}
