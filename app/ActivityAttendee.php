<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityAttendee extends Model
{
    protected $table = 'activity_attendee';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'sea_id','lea_id','ses_id', 'school_id', 'schedule_id', 'activity_school_attendee_id', 'activity_attendee_type_id',
        'attendance_status'
    ];

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }

    public function attendeeType()
    {
        return $this->hasOne('App\ActivityAttendeeType', 'activity_attendee_type_id');
    }

    public function attendeeSchool()
    {
        return $this->hasOne('App\ActivitySchoolAttendeeList', 'activity_school_attendee_id');
    }
}
