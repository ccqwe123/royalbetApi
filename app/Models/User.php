<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\UserSport;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'points',
        'email',
        'password',
        'verify_code'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getCurrentPoints()
	{
		$i = $this->id;

		$inv = WalletLog::where('user_id','=',$i)->get();
		$ct = 0;
		if(count($inv)>0)
		{
			$ct = $inv->where('type','deposit')->sum('amount') - $inv->where('type','withdraw')->sum('amount');
		}
		else
		{
			$ct =0;
		}
		return $ct;

	}
    public function sport()
    {
        return $this->belongsToMany(UserSport::class);
    }
}
