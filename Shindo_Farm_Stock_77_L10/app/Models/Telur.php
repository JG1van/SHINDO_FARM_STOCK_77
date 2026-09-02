<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Telur
 * 
 * @property int $id
 * @property int $kandang_id
 * @property Carbon $tanggal
 * @property int $jumlah_butir
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Kandang $kandang
 *
 * @package App\Models
 */
class Telur extends Model
{
	protected $table = 'telur';
	protected $guarded = [];
	protected $casts = [
		'kandang_id' => 'int',
		'tanggal' => 'date:Y-m-d',
		'jumlah_butir' => 'int'
	];

	protected $fillable = [
		'kandang_id',
		'tanggal',
		'jumlah_butir'
	];

	public function kandang()
	{
		return $this->belongsTo(Kandang::class);
	}
}
