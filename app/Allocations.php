<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Allocations extends Model
{
    protected $table = 'allocations';

    public static $allocationTypes = [
        'title1' => 1,
        'title2' => 2,
        'title3' => 3,
        'title4' => 4,
        'esser' => 5,
        'geer' => 6,
    ];

    public static $status = ['fn' => 1, 'pr' => 0];
    public static $allocationTypesRegular = [5, 6];
    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'school_id', 'school_year_id', 'allocation_type', 'total_instruction', 'family_engagement', 'professional_development',
        'materials', 'allocation', 'is_final', 'creation_date'
    ];

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }

    public function school()
    {
        $sdfasd = self::$allocationTypes['esser'];
        return $this->belongsTo('App\School', 'school_id');
    }

}
