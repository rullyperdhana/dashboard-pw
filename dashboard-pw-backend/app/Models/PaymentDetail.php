<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'tb_payment_detail';

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'payment_id',
        'employee_id',
        'gaji_pokok',
        'pajak',
        'iwp',
        'tunjangan',
        'potongan',
        'total_amoun',
        'notes',
    ];

    /**
     * Cast tipe data
     */
    protected $casts = [
        'gaji_pokok' => 'decimal:2',
        'pajak' => 'decimal:2',
        'iwp' => 'decimal:2',
        'tunjangan' => 'decimal:2',
        'potongan' => 'decimal:2',
        'total_amoun' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Payment (Header)
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Relasi ke Employee (Pegawai)
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /**
     * Hitung total gaji bersih
     * Formula: gaji_pokok - pajak - iwp + tunjangan - potongan
     */
    public function calculateNetSalary()
    {
        return $this->gaji_pokok - $this->pajak - $this->iwp + $this->tunjangan - $this->potongan;
    }

    /**
     * Boot method untuk auto-calculate total_amoun
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto calculate total_amoun jika belum diset
            if (is_null($model->total_amoun)) {
                $model->total_amoun = $model->calculateNetSalary();
            }
        });
    }
}
