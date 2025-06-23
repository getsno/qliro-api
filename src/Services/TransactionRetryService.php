<?php

namespace Gets\QliroApi\Services;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Models\Order;

class TransactionRetryService
{
    public function __construct(
        private readonly QliroApi $client,
        private int               $maxRetries = 3,
        private bool              $enableBackoff = true
    )
    {
    }

    /**
     * Process failed transactions with retry logic
     *
     * @param string $orderRef
     * @param array $initialTransactions
     * @return array Array of final transaction statuses
     * @throws QliroException
     */
    public function processFailedTransactions(string $orderRef, array $initialTransactions): array
    {
        $transactionsToRetry = $this->collectFailedTransactions($orderRef, $initialTransactions);
        $retryCount = 0;
        $finalResults = [];

        while (!empty($transactionsToRetry) && $retryCount < $this->maxRetries) {
            $retryCount++;

            if ($this->enableBackoff && $retryCount > 1) {
                sleep(pow(2, $retryCount - 1)); // Exponential backoff: 2, 4, 8 seconds
            }

            $newFailedTransactions = [];

            foreach ($transactionsToRetry as $transaction) {
                try {
                    $result = $this->retryTransaction($orderRef, $transaction);
                    $finalResults[] = $result;

                    // Collect any new failed transactions from this retry
                    if (isset($result['new_transactions'])) {
                        $newlyFailedTransactions = $this->collectFailedTransactions(
                            $orderRef,
                            $result['new_transactions']
                        );
                        $newFailedTransactions = array_merge($newFailedTransactions, $newlyFailedTransactions);
                    }

                } catch (\Exception $e) {
                    $finalResults[] = [
                        'transaction_id' => $transaction['PaymentTransactionId'],
                        'status'         => 'failed',
                        'error'          => $e->getMessage(),
                        'retry_count'    => $retryCount,
                    ];
                }
            }

            $transactionsToRetry = $newFailedTransactions;
        }

        // Handle any remaining failed transactions
        foreach ($transactionsToRetry as $transaction) {
            $finalResults[] = [
                'transaction_id' => $transaction['PaymentTransactionId'],
                'status'         => 'exhausted_retries',
                'retry_count'    => $retryCount,
            ];
        }

        return $finalResults;
    }

    /**
     * Retry a single transaction
     */
    private function retryTransaction(string $orderRef, array $transaction): array
    {
        $paymentTransactionId = $transaction['PaymentTransactionId'];
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;

        // Skip if transaction is now successful
        if ($order->getTransactionStatus($paymentTransactionId) === PaymentTransactionStatus::Success->value) {
            return [
                'transaction_id' => $paymentTransactionId,
                'status'         => 'success',
                'skipped'        => true,
            ];
        }

        $changes = $order->getChangesBasedOnTransaction($paymentTransactionId);
        $retryDto = $order->getReturnDto($changes);
        $retryResponse = $this->client->admin()->orders()->returnItems($retryDto)->response->json();

        return [
            'transaction_id'   => $paymentTransactionId,
            'status'           => 'retried',
            'new_transactions' => $retryResponse['PaymentTransactions'] ?? [],
        ];
    }

    /**
     * Collect failed transactions from a list of transactions
     */
    private function collectFailedTransactions(string $orderRef, array $transactions): array
    {
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $failedTransactions = [];

        foreach ($transactions as $transaction) {
            $paymentTransactionId = $transaction['PaymentTransactionId'];
            if ($order->getTransactionStatus($paymentTransactionId) !== PaymentTransactionStatus::Success->value) {
                $failedTransactions[] = $transaction;
            }
        }

        return $failedTransactions;
    }

    /**
     * Set maximum number of retries
     */
    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    /**
     * Enable or disable exponential backoff
     */
    public function setBackoffEnabled(bool $enabled): self
    {
        $this->enableBackoff = $enabled;
        return $this;
    }
}
