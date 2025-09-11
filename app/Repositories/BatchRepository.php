<?php
namespace App\Repositories;

use App\Models\Batch;

class BatchRepository
{
    protected $model;

    public function __construct(Batch $model)
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

    public function findByStatus($status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function updateStatus($id, $status)
    {
        $batch = $this->findById($id);
        if ($batch) {
            $batch->status = $status;
            $batch->save();
            return $batch;
        }
        return null;
    }

    public function findForPeriod($startDate, $endDate)
    {
        return $this->model
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get();
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function update($id, array $data)
    {
        $batch = $this->findById($id);
        if ($batch) {
            $batch->update($data);
            return $batch;
        }
        return null;
    }

    public function delete($id)
    {
        $batch = $this->findById($id);
        if ($batch) {
            return $batch->delete();
        }
        return false;
    }

    public function findWithTransactions()
    {
        return $this->model->with('transactions')->get();
    }

    public function findLatest($limit = 10)
    {
        return $this->model->orderBy('start_time', 'desc')->limit($limit)->get();
    }
}
