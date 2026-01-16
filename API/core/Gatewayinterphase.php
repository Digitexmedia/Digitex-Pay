<?php
/**
 * Digitex Pay – Gateway Interface
 * Every payment gateway MUST follow this structure
 */

interface GatewayInterface
{
    /**
     * Initiate a payment
     *
     * @param array $data
     * @return array
     */
    public function initiatePayment(array $data): array;

    /**
     * Verify payment status
     *
     * @param string $reference
     * @return array
     */
    public function verifyPayment(string $reference): array;

    /**
     * (Optional) Refund payment
     *
     * @param string $reference
     * @param float $amount
     * @return array
     */
    public function refund(string $reference, float $amount): array;
}
