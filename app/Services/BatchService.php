<?php
namespace App\Services;

use App\Repositories\BatchRepository;
use Carbon\Carbon;

class BatchService
{
    protected $batchRepository;

    public function __construct(BatchRepository $batchRepository)
    {
        $this->batchRepository = $batchRepository;
    }

    public function createBatch(array $data)
    {
        return $this->batchRepository->create($data);
    }

    public function getBatchById($id)
    {
        return $this->batchRepository->findById($id);
    }

    public function getBatchesByStatus($status)
    {
        return $this->batchRepository->findByStatus($status);
    }

    public function updateBatchStatus($id, $status)
    {
        return $this->batchRepository->updateStatus($id, $status);
    }

    public function startNewBatch()
    {
        return $this->createBatch([
            'status'     => 'processing',
            'start_time' => Carbon::now(),
        ]);
    }

    public function completeBatch($id)
    {
        return $this->batchRepository->update($id, [
            'status'   => 'completed',
            'end_time' => Carbon::now(),
        ]);
    }

    public function failBatch($id)
    {
        return $this->batchRepository->update($id, [
            'status'   => 'failed',
            'end_time' => Carbon::now(),
        ]);
    }

    public function getActiveBatches()
    {
        return $this->batchRepository->findByStatus('processing');
    }

    public function getCompletedBatches()
    {
        return $this->batchRepository->findByStatus('completed');
    }

    public function getFailedBatches()
    {
        return $this->batchRepository->findByStatus('failed');
    }

    public function getBatchesForPeriod($startDate, $endDate)
    {
        return $this->batchRepository->findForPeriod($startDate, $endDate);
    }
}
