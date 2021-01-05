<?php

namespace App\Http\Controllers;
use App\Activity;
use App\Schedule;
use App\School;
use App\ActivityApprovalType;
use App\ActivityApprovalStatus;
use App\ActivityAttendee;
use App\ActivityAttendeeType;
use App\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helper\ResponseHelper;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ActivityController extends Controller
{
    private $limit = 10;
    private $page = 1;
    private $condition = ['param' => null, 'value' => null];

    /**
     * Create a new controller instance.
     * @return void
     */
    public function __construct()
    {
    }

    public function index($allocationType, $schoolId, Request $request)
    {
        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;
        $activity = Activity::where('allocation_id', $allocationType)->where('school_id', $schoolId)
            ->skip($skip)->take($limit)->orderBy('id', 'DESC')->get();
        $activityResponse = ResponseHelper::makeActivitynData($activity);
        $activityCount = Activity::where('allocation_id', $allocationType)->where('school_id', $schoolId)->count();
        $pagesCount = ceil($activityCount / $limit);
        return response()->json(['activity' => $activityResponse, 'pagesCount' => $pagesCount]);
    }

    public function filterActivity($activityType, $schoolId, Request $request)
    {
        $schoolName = $request->get('search') ? $request->get('search') : NULL;
        $activitystatus = $request->get('status') ? Activity::$status[$request->get('status')] : NULL;
        $schoolYear = $request->get('year') ? $request->get('year') : NULL;

        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;

        $schoolYearId = (int)$schoolYear;
        $activitystatus = (bool)$activitystatus;

        $query = Activity::query();
        $query->where('allocation_type', $activityType);
        $query->where('school_id', $schoolId);

        $schoolIds = [];
        if ($schoolName) {
            $schools = School::where('school_name', 'like', '%' . $schoolName . '%')->get();
            foreach ($schools as $school) {
                $schoolIds[] = $school->id;
            }
            $query->whereIn('school_id', $schoolIds);
        }
        if (!is_null($activitystatus)) {
            $query->where('is_final', $activitystatus);
        }
        if ($schoolYear) {
            $query->where('school_year_id', $schoolYearId);
        }
        $activityCount = $query->count();
        $activity = $query->skip($skip)->take($limit)->get();
        $activityResponse = ResponseHelper::makeActivitynData($activity);
        $pagesCount = ceil($activityCount / $limit);
        return response()->json(['activity' => $activityResponse, 'pagesCount' => $pagesCount]);
    }

    public function getTotalsForBarSection($allocationType, $schoolId, Request $request)
    {
        $activityTypes = ActivityType::where('allocation_id',$allocationType)->get();
        $activityTypesIds = Arr::pluck($activityTypes, 'id');
        $totalCosts = [];
        $activityTotals = Activity::groupBy('allocation_type_categories_id')
            ->selectRaw('SUM(total_cost) as totalCost, allocation_type_categories_id')
            ->where('allocation_id' , $allocationType)
            ->where('school_id',$schoolId)
            ->whereIn('allocation_type_categories_id',$activityTypesIds)
            ->pluck('totalCost','allocation_type_categories_id');

        foreach($activityTotals as $key=>$totals)
        {
            $totalCosts[ActivityType::$types[$key]] = $totals;
        }
        return response()->json($totalCosts);
    }

    public function getApprovals()
    {
        $activityApprovalStatus = ActivityApprovalStatus::all();
        $activityApprovalTypes = ActivityApprovalType::all();
        return response()->json(['activityApprovalStatus' => $activityApprovalStatus, 'activityApprovalTypes' => $activityApprovalTypes]);
    }

    public function create(Request $request)
    {
        $success = true;
        $errorMessage = '';
        $activityResponse = [];
        try {
            $data = $request->all();
            $data['ses_id'] = 101;
            $data['lea_id'] = 2;
            $data['sea_id'] = 30;
            //$data['total_cost'] = (float)$data['cost'] * (int)$data['quantity'];
            if ($activity = Activity::create($data)) {
                $schedule = new Schedule;
              /********/
            }
            $activityResponse = ResponseHelper::makeActivitynData($activity);
        } catch (Throwable $e) {
            $success = false;
            $errorMessage = $e->getMessage();
        }

        return response()->json(['activity'=>$activityResponse,'success'=>$success,'errorMessage'=>$errorMessage]);
    }

    public function show($id)
    {
        $activity = Activity::find($id);
        return response()->json($activity);
    }

    public function modifyActivity()
    {

    }

    public function update(Request $request, $id)
    {
        $activityResponse = [];
        $success = true;
        $errorMessage = '';
        try {
            $activity = Activity::find($id);
            $activityOldInfo = $activity;
            $data = $request->all();
            $data['sea_id'] = 30;
            $data['lea_id'] = 2;
            $data['ses_id'] = 101;
            $cost = (float)$data['cost'];
            $data['total_cost'] = $cost * $activity->upcharge_percentage;
            /*
            upcharge_percentage = 1,12
                fee = 1,12
                5000 , 5600
              600
    /*
                $data['purchase_date'] = isset($data['purchase_date']) ? date('Y-m-d H:i:s',strtotime($data['purchase_date'])) : date('Y-m-d H:i:s',strtotime($license->purchase_date));
                $data['expiration_date'] = isset($data['expiration_date']) ? date('Y-m-d H:i:s',strtotime($data['expiration_date'])) : date('Y-m-d H:i:s',strtotime($license->expiration_date));
                $data['renewal_date'] = isset($data['renewal_date']) ? date('Y-m-d H:i:s',strtotime($data['renewal_date'])) : date('Y-m-d H:i:s',strtotime($license->renewal_date));;

                */

            $update = $activity->update($data);
            if($activityOldInfo->activity_approval_status_id != $data['activity_approval_status_id'])
            {
    
            }

            $activityResponse = ResponseHelper::makeActivitynData($activity);
        } catch (Throwable $e) {
            $success = false;
            $errorMessage = $e->getMessage();
        }


        return response()->json(['activity'=>$activityResponse,'success'=>$success,'errorMessage'=>$errorMessage]);
    }

    public function destroy($id)
    {
        $activity = Activity::find($id);
        if (!$activity) return [];
        $activity->delete();
        return response()->json('Activity removed successfully');
    }

}
