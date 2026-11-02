<?php
interface PaymentGatewayInterface {
    public function createPaymentRequest(array $order): array;
    public function redirectToPayment(array $order): never;
    public function verifyPayment(array $order,array $gatewayResponse): bool;
    public function handleCallback(array $payload): array;
    public function getPaymentStatus(array $order): string;
}
