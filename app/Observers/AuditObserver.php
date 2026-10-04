<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $action): void
    {
        $changed = $action === 'created' ? $model->getAttributes() : $model->getChanges();
        AuditLog::query()->create([
            'user_id' => Auth::id(), 'action' => $action, 'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'changed_fields' => array_values(array_diff(array_keys($changed),
                ['password', 'remember_token', 'updated_at'])),
        ]);
    }
}
