<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pengeluaran
 * 
 * @property int $id
 * @property Carbon $tanggal
 * @property float $jumlah
 * @property string $keterangan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Pengeluaran extends Model
{
	protected $table = 'pengeluaran';
	protected $guarded = [];
	protected $casts = [
		'tanggal' => 'date:Y-m-d',
		'jumlah' => 'float'
	];

	protected $fillable = [
		'tanggal',
		'jumlah',
		'keterangan'
	];
}
