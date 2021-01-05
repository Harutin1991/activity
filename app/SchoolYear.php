<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $table = 'school_year';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'year_name','start_date', 'end_date','description'
    ];

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }

    public function schoolsYearRef()
    {
        return $this->hasMany('App\SchoolYearReference','school_year_id');
    }
}
