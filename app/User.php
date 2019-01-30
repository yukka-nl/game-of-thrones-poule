<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are guarded.
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->append('has_predictions');
        $this->append('link');
    }

    public function getLinkAttribute()
    {
        return '/predictions/user/' . $this->id;
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    public function getHasPredictionsAttribute()
    {
        return $this->hasPredictions();
    }

    public function hasPredictions()
    {
        return $this->predictions->count() > 0;
    }

    public function characterPrediction($characterId)
    {
        if ($this->hasPredictions()) {
            return $this->predictions->where('character_id', $characterId)->first()->status;
        }
    }
}
