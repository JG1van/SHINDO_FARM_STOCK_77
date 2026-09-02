<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Kandang
 * 
 * @property int $id
 * @property string $nama
 * @property string $jenis_ayam
 * @property int $jantan
 * @property int $betina
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Telur[] $telurs
 *
 * @package App\Models
 */
class Kandang extends Model
{
	protected $table = 'kandang';
	protected $guarded = [];
	protected $casts = [
		'jantan' => 'int',
		'betina' => 'int'
	];

	protected $fillable = [
		'nama',
		'jenis_ayam',
		'jantan',
		'betina'
	];

	public function telurs()
	{
		return $this->hasMany(Telur::class);
	}
}
