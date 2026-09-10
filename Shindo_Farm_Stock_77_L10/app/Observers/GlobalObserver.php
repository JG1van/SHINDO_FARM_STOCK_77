<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Kandang;

class GlobalObserver
{
    protected $globalExcept = [
        'updated_at', 'created_at', 'remember_token',
        'password', 'photo', 'deleted_at', 'email_verified_at',
    ];

    protected $idFieldMap = [
        'kandang_id' => ['model' => Kandang::class, 'field' => 'nama'],
        'user_id' => ['model' => \App\Models\User::class, 'field' => 'name'],
    ];

    protected $labelFields = ['name', 'nama', 'title', 'email', 'username', 'keterangan', 'nama_pembeli'];

    public function created($model): void
    {
        $this->log($model, 'CREATE');
    }

    public function updated($model): void
    {
        $this->log($model, 'UPDATE');
    }

    public function deleted($model): void
    {
        $this->log($model, 'DELETE');
    }

    protected function log($model, string $action): void
    {
        // Anti infinite loop: jangan log tabel log itu sendiri
        if ($model instanceof ActivityLog) {
            return;
        }

        // UPDATE kosong (hanya field dikecualikan) → tidak menulis log
        if ($action === 'UPDATE') {
            $meaningful = array_diff_key($model->getChanges(), array_flip($this->globalExcept));
            if (empty($meaningful)) {
                return;
            }
        }

        $modelName = class_basename($model);
        $label = $this->getLabel($model);

        // Waktu lokal user: offset dikirim dari JS via header X-Timezone-Offset (menit, positif=di timur UTC)
        $offset = (int) (request()->header('X-Timezone-Offset', 0));
        $localNow = now()->subMinutes($offset);

       ActivityLog::create([
        'user_id'      => auth()->check() ? auth()->id() : null,
        'action'       => $action,
        'logable_type' => $modelName,
        'logable_id'   => $model->getKey(),
        'description'  => $this->buildDescription($model, $modelName, $action, $label),
        'ip_address'   => request()->ip(),
        'created_at'   => $localNow,
        'updated_at'   => $localNow,
    ]);
    }

    protected function getLabel($model): string
    {
        $modelName = class_basename($model);

        // Label khusus per model agar deskripsi bermakna
        if ($modelName === 'Telur') {
            $kandang = $model->kandang_id ? Kandang::find($model->kandang_id) : null;
            $nama = $kandang ? $kandang->nama : "id#{$model->kandang_id}";
            return "{$model->jumlah_butir} butir — {$nama} ({$model->tanggal})";
        }
        if ($modelName === 'Penjualan') {
            return "{$model->nama_pembeli} ({$model->jumlah_telur} butir)";
        }

        foreach ($this->labelFields as $field) {
            if (!empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return "ID #{$model->getKey()}";
    }

    protected function buildDescription($model, string $modelName, string $action, string $label): string
    {
        $agent = $this->parseAgent(request()->header('User-Agent') ?? '');
        $base = match ($action) {
            'CREATE' => "Menambahkan data {$modelName} baru — {$label}.",
            'UPDATE' => "Mengubah data {$modelName} — {$label}.",
            'DELETE' => "Menghapus data {$modelName} — {$label}.",
            default => "{$action} data {$modelName} — {$label}.",
        };

        if ($action === 'CREATE') {
            $details = [];
            foreach ($model->getAttributes() as $field => $val) {
                if ($field === 'id' || in_array($field, $this->globalExcept, true)) {
                    continue;
                }
                $details[] = "{$field}: \"{$this->formatValue($field, $val)}\"";
            }
            if ($details) {
                $base .= ' Detail: ' . implode(', ', $details) . '.';
            }
        }

        if ($action === 'UPDATE') {
            $changes = array_diff_key($model->getChanges(), array_flip($this->globalExcept));
            $details = [];
            foreach ($changes as $field => $newVal) {
                $oldVal = $model->getOriginal($field);
                $details[] = "{$field} dari \"{$this->formatValue($field, $oldVal)}\" menjadi \"{$this->formatValue($field, $newVal)}\"";
            }
            if ($details) {
                $base .= ' Perubahan: ' . implode(', ', $details) . '.';
            }
        }

        if ($action === 'DELETE') {
            $details = [];
            foreach ($model->getAttributes() as $field => $val) {
                if ($field === 'id' || in_array($field, $this->globalExcept, true)) {
                    continue;
                }
                $details[] = "{$field}: \"{$this->formatValue($field, $val)}\"";
            }
            if ($details) {
                $base .= ' Data terhapus: ' . implode(', ', $details) . '.';
            }
        }

        $base .= ' Melalui ' . request()->method() . ' ' . request()->fullUrl() . " menggunakan {$agent}.";

        return $base;
    }

    protected function formatValue(string $field, $value): string
    {
        if ($value === null) {
            return '-';
        }
        if (is_array($value)) {
            return implode(', ', $value);
        }
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return implode(', ', $decoded);
            }
        }

        // Resolve FK terdaftar jadi label asli dari DB
        if (isset($this->idFieldMap[$field])) {
            $map = $this->idFieldMap[$field];
            $related = $map['model']::find($value);
            return $related ? "{$field}: \"{$related->{$map['field']}}\"" : "{$field}: id#{$value}";
        }

        // FK tak ter-map → id#N
        if (str_ends_with($field, '_id') && is_numeric($value)) {
            return "{$field}: id#{$value}";
        }

        return (string) $value;
    }

    protected function parseAgent(string $ua): string
    {
        $browser = 'Unknown';
        $os = 'Unknown';

        if (preg_match('/Edg\/[\d.]+/', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\/[\d.]+/', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/', $ua)) {
            $browser = 'Safari';
        }

        if (preg_match('/Windows/', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac/', $ua)) {
            $os = 'Mac';
        } elseif (preg_match('/Linux/', $ua)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/', $ua)) {
            $os = 'Android';
        }

        return "{$browser}/{$os}";
    }
}
