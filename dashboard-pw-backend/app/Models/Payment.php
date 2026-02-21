<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'tb_payment';

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'rka_id',
        'month',
        'year',
        'total_amoun',
        'total_emplo',
        'payment_dat',
        'notes',
    ];

    /**
     * Cast tipe data
     */
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_amoun' => 'double',
        'payment_dat' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Konstanta status pembayaran
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relasi ke PaymentDetail (Detail per pegawai)
     */
    public function details()
    {
        return $this->hasMany(PaymentDetail::class, 'payment_id');
    }

    /**
     * Relasi ke RKA Setting
     */
    public function rkaSetting()
    {
        return $this->belongsTo(RkaSetting::class, 'rka_id');
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Accessor untuk nama bulan
     */
    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $months[$this->month] ?? '';
    }

    /**
     * Accessor untuk periode pembayaran
     */
    public function getPeriodAttribute()
    {
        return $this->month_name . ' ' . $this->year;
    }
}
