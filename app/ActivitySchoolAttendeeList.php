<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivitySchoolAttendeeList extends Model
{
    protected $table = 'activity_school_attendee_list';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'sea_id','lea_id','ses_id', 'school_id', 'first_name', 'last_name', 'email'
    ];

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }

    public function school()
    {
        return $this->hasOne('App\School', 'school_id');
    }

}
