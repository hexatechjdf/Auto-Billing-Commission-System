<?php
namespace App\Services;

use App\Repositories\TransactionRepository;

class TransactionService
{
    protected $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function createTransaction(array $data)
    {
        return $this->transactionRepository->create($data);
    }

    public function getTransactionById($id)
    {
        return $this->transactionRepository->findById($id);
    }

    public function getTransactionsByBatchId($batchId)
    {
        return $this->transactionRepository->findByBatchId($batchId);
    }

    public function getTransactionsByLocationId($locationId)
    {
        return $this->transactionRepository->findByLocationId($locationId);
    }

    public function getTransactionsByStatus($status)
    {
        return $this->transactionRepository->findByStatus($status);
    }

    public function updateTransactionStatus($id, $status)
    {
        return $this->transactionRepository->updateStatus($id, $status);
    }

    public function getTransactionsForPeriod($startDate, $endDate)
    {
        return $this->transactionRepository->findForPeriod($startDate, $endDate);
    }

    public function getFailedTransactions()
    {
        return $this->transactionRepository->findByStatus('failed');
    }

    public function retryFailedTransaction($id)
    {
        $transaction = $this->getTransactionById($id);
        if ($transaction && $transaction->status === 'failed') {
            // Logic to retry the transaction
            // This would typically involve calling Stripe API again
            return $this->updateTransactionStatus($id, 'pending');
        }
        return false;
    }
}
