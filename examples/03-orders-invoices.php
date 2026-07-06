<?php

declare(strict_types=1);

/**
 * 03 - Orders and Invoices
 *
 * Invoices are NEVER created directly via `POST /invoice/`. The OrgaMax
 * OpenAPI does not expose that endpoint. Instead, you create an Order
 * and then "draft" an invoice from it via `POST /order/{id}/invoice`.
 *
 * Run:  php examples/03-orders-invoices.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::create(
    apiKey: getenv('ORGAMAX_API_KEY') ?: 'demo-key',
    apiSecret: getenv('ORGAMAX_API_SECRET') ?: 'demo-secret',
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership',
);

$customerId = 'c-existing-1001';
$articleId  = 'a-existing-42';

// --- 1. Create the order ---------------------------------------------------
$order = $client->orders()->create([
    'order' => [
        'customerId' => $customerId,
        'orderDate'  => date('Y-m-d'),
        'positions'  => [[
            'articleId' => $articleId,
            'quantity'  => 2,
            'price'     => 199.00,
        ]],
    ],
]);

$orderId = $order->first()['id'];
echo "Order id={$orderId}\n";

// --- 2. Draft an invoice from the order -----------------------------------
$invoice = $client->orders()->createInvoice(
    orderId: $orderId,
    payload: [
        'invoiceDate' => date('Y-m-d'),
        'dueDate'     => date('Y-m-d', strtotime('+14 days')),
    ],
);

$invoiceId = $invoice->first()['id'];
echo "Invoice id={$invoiceId} drafted from order {$orderId}\n";

// --- 3. Send the invoice to the customer ----------------------------------
$client->invoices()->send($invoiceId, [
    'method' => 'email',
    'to'     => 'buchhaltung@acme.example',
]);
echo "Invoice sent\n";

// --- 4. Lock the invoice so it cannot be edited anymore -------------------
$client->invoices()->lock($invoiceId);
echo "Invoice locked\n";

// --- 5. Download the rendered PDF ------------------------------------------
$pdf = $client->invoices()->downloadDocument($invoiceId);
file_put_contents(__DIR__ . "/invoice-{$invoiceId}.pdf", $pdf->body());
echo "Invoice PDF saved to examples/invoice-{$invoiceId}.pdf\n";

// --- 6. Record a payment ---------------------------------------------------
$client->invoices()->addPayment($invoiceId, [
    'amount'      => 199.00,
    'paidAt'      => date('Y-m-d'),
    'paymentType' => 'bankTransfer',
]);
echo "Payment recorded\n";

// --- 7. Look up the final state --------------------------------------------
$final = $client->invoices()->get($invoiceId);
echo "Final invoice state: " . json_encode($final->first(), JSON_PRETTY_PRINT) . "\n";
