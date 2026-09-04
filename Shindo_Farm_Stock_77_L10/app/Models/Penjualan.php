<?php
/**
 * Created by Reliese Model.
 */
namespace App\Models;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Penjualan
 * 
 * @property int $id
 * @property Carbon $tanggal
 * @property string $nama_pembeli
 * @property int $jumlah_telur
 * @property float $total_harga
 * @property float $bonus
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Penjualan extends Model
{
	protected $table = 'penjualan';
	protected $guarded = [];
	protected $casts = [
		'tanggal' => 'date:Y-m-d',
		'jumlah_telur' => 'int',
		'total_harga' => 'float',
		'bonus' => 'float'
	];
	protected $fillable = [
		'tanggal',
		'nama_pembeli',
		'jumlah_telur',
		'total_harga',
		'bonus'
	];
}