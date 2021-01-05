<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $table = 'school';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_name', 'org_id','status_id','contact_id','district_id'
    ];

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }

    public function allocation()
    {
        return $this->hasMany('App\Allocations','school_id');
    }
}
