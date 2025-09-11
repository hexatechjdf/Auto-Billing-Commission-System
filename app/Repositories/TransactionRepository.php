<?php
namespace App\Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    protected $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function findByBatchId($batchId)
    {
        return $this->model->where('batch_id', $batchId)->get();
    }

    public function findByLocationId($locationId)
    {
        return $this->model->where('location_id', $locationId)->get();
    }

    public function findByStatus($status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function updateStatus($id, $status)
    {
        $transaction = $this->findById($id);
        if ($transaction) {
            $transaction->status = $status;
            $transaction->save();
            return $transaction;
        }
        return null;
    }

    public function findForPeriod($startDate, $endDate)
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function update($id, array $data)
    {
        $transaction = $this->findById($id);
        if ($transaction) {
            $transaction->update($data);
            return $transaction;
        }
        return null;
    }

    public function delete($id)
    {
        $transaction = $this->findById($id);
        if ($transaction) {
            return $transaction->delete();
        }
        return false;
    }

    public function findWithBatch()
    {
        return $this->model->with('batch')->get();
    }
}
