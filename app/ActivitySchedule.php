<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivitySchedule extends Model
{
    protected $table = 'activity_schedule';

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }

    public function schedule()
    {
        return $this->belongsTo('App\Schedule', 'schedule_id');
    }

    public function activity()
    {
        return $this->belongsTo('App\Activity', 'activity_id');
    }

}
