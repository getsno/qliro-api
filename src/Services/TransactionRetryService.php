<?php

namespace Gets\QliroApi\Services;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Dtos\Transaction\NewlyCreatedTransactionDto;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Models\Order;
use Gets\QliroApi\Models\OrderCaptures;
use Gets\QliroApi\Models\OrderReturns;

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
                        'transaction_id' => $transaction->PaymentTransactionId,
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
                'transaction_id' => $transaction->PaymentTransactionId,
                'status'         => 'exhausted_retries',
                'retry_count'    => $retryCount,
            ];
        }
        // Check for exhausted retries and throw exception if found
        foreach ($finalResults as $result) {
            if ($result['status'] === 'exhausted_retries') {
                throw new QliroException(
                    sprintf(
                        'Transaction retry exhausted for transaction ID %s after %d attempts',
                        $result['transaction_id'],
                        $result['retry_count']
                    )
                );
            }
        }

        return $finalResults;
    }

    /**
     * Retry a single transaction
     * @throws QliroException
     */
    private function retryTransaction(string $orderRef, NewlyCreatedTransactionDto $transaction): array
    {
        $paymentTransactionId = $transaction->PaymentTransactionId;
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;

        // Skip if transaction is now successful
        if ($order->getTransactionStatus($paymentTransactionId) === PaymentTransactionStatus::Success->value) {
            return [
                'transaction_id' => $paymentTransactionId,
                'status'         => 'success',
                'skipped'        => true,
            ];
        }

        $changes = $order->getChangesBasedOnTransaction($paymentTransactionId);    // Build appropriate DTO based on the type of changes
        if ($changes instanceof OrderReturns) {
            $retryDto = $order->buildReturnDto($changes);
            $retryResponse = $this->client->admin()->orders()->returnItems($retryDto)->dto;
        } elseif ($changes instanceof OrderCaptures) {
            $retryDto = $order->buildCaptureDto($changes);
            $retryResponse = $this->client->admin()->orders()->markItemsAsShipped($retryDto)->dto;
        } else {
            throw new QliroException('Unsupported change type: ' . get_class($changes));
        }

        return [
            'transaction_id'   => $paymentTransactionId,
            'status'           => 'retried',
            'new_transactions' => $retryResponse->PaymentTransactions,
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
            $paymentTransactionId = $transaction->PaymentTransactionId;
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
