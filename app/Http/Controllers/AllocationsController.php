<?php

namespace App\Http\Controllers;
use App\Allocations;
use App\AllocationChanges;
use App\School;
use App\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helper\ResponseHelper;

class AllocationsController extends Controller
{
    private $allocationsCount = 0;
    private static $currentYearId = NULL;
    private $limit = 10;
    private $page = 1;
    private $condition = ['param'=>null,'value'=>null];
    /**
     * Create a new controller instance.
     * @return void
     */
    public function __construct()
    {
        if($this->condition['param'] && $this->condition['value']) {
            $this->allocationsCount = Allocations::where($this->condition['param'],$this->condition['value'])->count();
        } else {
            $this->allocationsCount = Allocations::all()->count();
        }

        if(!$this->currentYearId) {
            $currentYear = SchoolYear::where('is_current',1)->first();
            $this->currentYearId = $currentYear->id;
        }
    }

    public function index($allocationType,Request $request)
    {
        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;
        $allocations = Allocations::where('allocation_type',$allocationType)->skip($skip)->take($limit)->orderBy('id', 'DESC')->get();
        $allocationResponse = ResponseHelper::makeAllocationData($allocations);
        $allocationsCount = Allocations::where('allocation_type',$allocationType)->count();
        $pagesCount = ceil($allocationsCount / $limit);
        return response()->json(['allocations'=>$allocationResponse,'pagesCount'=>$pagesCount]);
    }

    public function getAllocationsByStatus($allocationType , Request $request)
    {

        $filter = $request->get('filter') ? $request->get('filter') : 0;
        $allocationStatus = Allocations::$status[$filter];
        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;
        $allocations = Allocations::where('is_final',$allocationStatus)->where('allocation_type',$allocationType)->skip($skip)->take($limit)->get();
        $allocationResponse = ResponseHelper::makeAllocationData($allocations);
        $allocationsCount = Allocations::where('is_final',$allocationStatus)->count();
        $pagesCount = ceil($allocationsCount / $limit);
        return response()->json(['allocations'=>$allocationResponse,'pagesCount'=>$pagesCount]);
    }

    public function filterAllocation($allocationType , Request $request)
    {
        $schoolName = $request->get('search') ? $request->get('search') : NULL;
        $allocationStatus = $request->get('status') ?  Allocations::$status[$request->get('status')] : NULL;
        $schoolYear = $request->get('year') ? $request->get('year') : NULL;

        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;

        $schoolYearId = (int)$schoolYear;
        $allocationStatus = (bool)$allocationStatus;

        $query = Allocations::query();
        $query->where('allocation_type',$allocationType);

        $schoolIds = [];
        if($schoolName) {
            $schools = School::where('school_name', 'like', '%' . $schoolName . '%')->get();
            foreach ($schools as $school) {
                $schoolIds[] = $school->id;
            }
            $query->whereIn('school_id',$schoolIds);
        }
        if(!is_null($allocationStatus)) {
            $query->where('is_final',$allocationStatus);
        }
        if($schoolYear) {
            $query->where('school_year_id',$schoolYearId);
        }
        $allocationsCount = $query->count();
        $allocations = $query->skip($skip)->take($limit)->get();
        $allocationResponse = ResponseHelper::makeAllocationData($allocations);
        $pagesCount = ceil($allocationsCount / $limit);
        return response()->json(['allocations'=>$allocationResponse,'pagesCount'=>$pagesCount]);
    }

    public function searchBySchoolName($allocationType , Request $request)
    {
        $schoolName = $request->get('school_name') ? $request->get('school_name') : NULL;
        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;
        if (!$schoolName) return [];
        $schools = School::where('school_name', 'like', '%' . $schoolName . '%')->get();
        $schoolIds = [];
        foreach ($schools as $school) {
            $schoolIds[] = $school->id;
        }
        $allocations = Allocations::whereIn('school_id', $schoolIds)->where('allocation_type' , $allocationType)->get();
        $allocationResponse = ResponseHelper::makeAllocationData($allocations);
        $pagesCount = ceil(count($allocationResponse) / $limit);
        return response()->json(['allocations'=>$allocationResponse,'pagesCount'=>$pagesCount]);
    }

    public function getTotalsForBarSection($allocationType, Request $request)
    {
        $schoolYearId = $request->get('school_year') ? $request->get('school_year') : $this->currentYearId;
        $allocation = Allocations::where('allocation_type' , $allocationType);
        $totalInstruction = round($allocation->sum('total_instruction'),2);
        $profDevTotal = round($allocation->sum('professional_development'),2);
        $totalAllocation = round($allocation->sum('total_allocation'),2);
        $materials = round($allocation->sum('materials'),2);

        $wellRoundedTotal = round($allocation->sum('well_rounded_amount'),2);
        $safeHealthyTotal = round($allocation->sum('safe_healthy_amount'),2);
        $teachPDTotal = round($allocation->sum('teach_professional_development_amount'),2);
        $teachInstructionTotal = round($allocation->sum('teach_instruction_amount'),2);

        $grandTotal = round($totalAllocation , 2) ;
        $familyEngagement = round($allocation->sum('family_engagement'),2);
        $allocation->where('school_year_id',$schoolYearId);
        $instructionForSchoolYear = round($allocation->sum('total_instruction'),2);
        return response()->json(['instructionForSchoolYear'=>$instructionForSchoolYear,'totalInstruction'=>$totalInstruction,
           'pdTotal'=>$profDevTotal, 'familyEngTotal'=>$familyEngagement,'materialTotal'=>$materials,
            'grandTotal'=>round($grandTotal , 2),'totalAllocation'=>$totalAllocation,
            'wellRoundedTotal'=>$wellRoundedTotal,'safeHealthyTotal'=>$safeHealthyTotal,'teachPDTotal'=>$teachPDTotal,
            'teachInstructionTotal'=>$teachInstructionTotal]);
    }

    public function getTotalCost($allocationType,$schoolId, Request $request)
    {
        $schoolYearId = $request->get('schoolYearId') ? $request->get('schoolYearId') : self::$currentYearId;
        $inventory = Inventory::where('allocation_type_id', $allocationType)
            ->where('school_id', $schoolId)->where('school_year_id', $schoolYearId);
        $totalCost = round($inventory->sum('total_cost'),2);
        return response()->json(['totalCost'=>$totalCost]);
    }

    public function listOfAllocations()
    {
        $allocations = Allocations::all();
        $allocationResponse = ResponseHelper::makeAllocationData($allocations);
        $schools = School::all();
        $schoolYears = SchoolYear::all();

        return response()->json(['allocations'=>$allocationResponse,'schools'=>$schools,'schoolYears'=>$schoolYears]);
    }

    public function create(Request $request)
    {
        $allocation = new Allocations;
        $allocation->school_id = $request->school_id;
        $allocation->school_year_id = $request->school_year_id ? $request->school_year_id : $this->currentYearId;
        $allocation->ses_id = 101;
        $allocation->lea_id = 2;
        $allocation->sea_id = 30;
        $allocation->allocation_type = $request->allocation_type;
        //$allocation->preliminary_allocation = $request->preliminary_allocation;
        $allocation->total_instruction = $request->total_instruction ? $request->total_instruction : 0;
        $allocation->instruction = $allocation->total_instruction ? $allocation->total_instruction - $allocation->total_instruction * $request->professional_development_percentage / 100 : 0;
        $allocation->family_engagement = $request->family_engagement ? $request->family_engagement  : 0;
        $allocation->professional_development_percentage = $request->professional_development_percentage ? $request->professional_development_percentage : 0;

       //$allocation->professional_development = $allocation->total_instruction ? $allocation->total_instruction * $allocation->professional_development_percentage / 100 : 0;

        $allocation->professional_development = ($allocation->allocation_type == 1) ? ($allocation->total_instruction * $allocation->professional_development_percentage) / 100 :
            ($allocation->allocation_type == 2) ? $request->input('professional_development') : 0;

        $allocation->materials = $request->materials ? $request->materials : 0;

        $allocation->total_allocation = (($allocation->allocation_type == 1) ? $allocation->total_instruction + $allocation->family_engagement :
            ($allocation->allocation_type == 2)) ? $allocation->professional_development + $allocation->materials :
            ((in_array($allocation->allocation_type,Allocations::$allocationTypesRegular)) ? $request->input('total_allocation') : $request->input('total_allocation'));


        $allocation->safe_healthy_percentage = $request->safe_healthy_percentage ? $request->safe_healthy_percentage : 0;
        $allocation->safe_healthy_amount = $request->safe_healthy_percentage ? $allocation->total_allocation * $request->safe_healthy_percentage / 100 :0;

        $allocation->well_rounded_percentage = $request->well_rounded_percentage ? $request->well_rounded_percentage : 0;
        $allocation->well_rounded_amount = $request->well_rounded_percentage ? $allocation->total_allocation * $request->well_rounded_percentage / 100 : 0;

        $allocation->teach_instruction_percentage = $request->teach_instruction_percentage ? $request->teach_instruction_percentage : 0;
        $allocation->teach_instruction_amount = $request->teach_instruction_percentage ? ($allocation->total_allocation - $allocation->well_rounded_amount - $allocation->safe_healthy_amount) *  $request->teach_instruction_percentage :0;

        $allocation->teach_professional_development_amount = $request->teach_professional_development_amount ? ($allocation->total_allocation - $allocation->well_rounded_amount - $allocation->safe_healthy_amount - $allocation->tech_infrastructure_amount) : 0;

        $allocation->is_final = $request->is_final;
        $allocation->note = $request->note;
        $allocation->creation_date = date('Y-m-d',strtotime($request->creation_date));
        $allocation->created_at = date('Y-m-d H:i:s');

        if($allocation->save()) {
            $allocationChange = new AllocationChanges;
            $allocationChange->allocation_id = $allocation->id;
            $allocationChange->total_instruction_amt = $allocation->total_instruction;
            $allocationChange->family_engagament_amt = $allocation->family_engagement;
            $allocationChange->materials_amt = $allocation->materials;
            $allocationChange->total_allocation_amt = $allocation->total_allocation;
            $allocationChange->updated_at = null;
            $allocationChange->save();
        }
        $allocationResponse = ResponseHelper::makeAllocationData($allocation);
        return response()->json($allocationResponse);
    }

    public function show($id)
    {
        $allocation = Allocations::find($id);
        return response()->json($allocation);
    }

    public function update(Request $request, $id)
    {
         $allocation = Allocations::find($id);
        if(!$allocation)  return [];
        $allocation->ses_id = 101;
        $allocation->lea_id = 2;
        $allocation->sea_id = 30;
        $allocation->allocation_type = $request->input('allocation_type') ? $request->input('allocation_type') : $allocation->allocation_type;
        $allocation->professional_development_percentage = $request->input('professional_development_percentage') ? $request->input('professional_development_percentage') : 0;
        //$allocation->preliminary_allocation = $request->input('preliminary_allocation') ?? $request->input('preliminary_allocation');
        $allocation->total_instruction = $request->input('total_instruction') ? $request->input('total_instruction') : $allocation->total_instruction;
        $allocation->instruction = ($allocation->total_instruction && $allocation->professional_development_percentage) ?
            ( $allocation->total_instruction - ($allocation->total_instruction * $allocation->professional_development_percentage) / 100) : $allocation->instruction;
        $allocation->family_engagement = $request->input('family_engagement') ? $request->input('family_engagement') : $allocation->family_engagement;

//        $allocation->professional_development = ($allocation->total_instruction && $allocation->professional_development_percentage) ?
//            ($allocation->total_instruction * $allocation->professional_development_percentage) / 100 : $allocation->professional_development;
        $allocation->professional_development = ($allocation->allocation_type == 1) ? ($allocation->total_instruction * $allocation->professional_development_percentage) / 100 :
            ($allocation->allocation_type == 2) ? $request->input('professional_development') : 0 ;



        $allocation->materials = $request->input('materials') ? $request->input('materials') : $allocation->materials;
       $allocation->total_allocation = (($allocation->allocation_type == 1) ? $allocation->total_instruction + $allocation->family_engagement :
            ($allocation->allocation_type == 2)) ? $allocation->professional_development + $allocation->materials :
            ((in_array($allocation->allocation_type,Allocations::$allocationTypesRegular)) ? $request->input('total_allocation') : $request->input('total_allocation')) ;  //grand total

        $allocation->is_final = $request->input('is_final');
        $allocation->note = $request->input('note');
        $allocation->creation_date = date('Y-m-d',strtotime($request->input('creation_date')));

        $allocation->well_rounded_percentage = $request->input('well_rounded_percentage') ? $request->input('well_rounded_percentage') : $allocation->well_rounded_percentage;
        $allocation->well_rounded_amount = $request->input('well_rounded_percentage') ? $allocation->total_allocation * $allocation->well_rounded_percentage / 100 : $allocation->well_rounded_amount;

        $allocation->safe_healthy_percentage = $request->input('safe_healthy_percentage') ? $request->input('safe_healthy_percentage') : $allocation->safe_healthy_percentage;
        $allocation->safe_healthy_amount = $request->input('safe_healthy_amount') ? $allocation->total_allocation * $allocation->safe_healthy_percentage / 100 : $allocation->safe_healthy_amount;

        $allocation->teach_instruction_percentage = $request->input('teach_instruction_percentage') ? $request->input('teach_instruction_percentage') :0;
        $allocation->teach_instruction_amount = $request->input('teach_instruction_percentage') ? (($allocation->total_allocation - $allocation->well_rounded_amount - $allocation->safe_healthy_amount) *  $allocation->teach_instruction_percentage) / 100 :0;



        $allocation->teach_professional_development_amount = $request->input('teach_professional_development_amount') ? ($allocation->total_allocation - $allocation->well_rounded_amount - $allocation->safe_healthy_amount - $allocation->tech_infrastructure_amount) : 0;


        $allocation->updated_at = date('Y-m-d H:i:s');

        if($allocation->save()) {
            $allocationChange = AllocationChanges::where('allocation_id',$allocation->id)->first();
            $isNew = false;
            if(!$allocationChange) {
                $allocationChange = new AllocationChanges;
                $allocationChange->allocation_id = $allocation->id;
                $isNew = true;
            }
            $allocationChange->total_instruction_amt = $allocation->total_instruction;
            $allocationChange->family_engagament_amt = $allocation->family_engagement;
            $allocationChange->materials_amt = $allocation->materials;
            $allocationChange->total_allocation_amt = $allocation->total_allocation;
            $allocationChange->is_final_change = $allocation->is_final ? 1 : 0;
            if(!$isNew) {
                $allocationChange->updated_at = date('Y-m-d H:i:s');
            }
            $allocationChange->save();
        }
        $allocationResponse = ResponseHelper::makeAllocationData($allocation);
        return response()->json($allocationResponse);
    }

    public function getAllocationBySchoolYear($allocationType , $schoolYearId, Request $request)
    {
        $limit = $request->get('limit') ? $request->get('limit') : $this->limit;
        $page = $request->get('page') ? $request->get('page') : $this->page;
        $skip = (!$page) ? 0 : ($page - 1) * $limit;
        $allocations = Allocations::where('school_year_id',$schoolYearId)->where('allocation_type',$allocationType)->skip($skip)->take($limit)->get();
        $allocationResponse = ResponseHelper::makeAllocationData($allocations);
        $allocationsCount = Allocations::where('school_year_id',$schoolYearId)->count();
        $pagesCount = ceil($allocationsCount / $limit);
        return response()->json(['allocations'=>$allocationResponse,'pagesCount'=>$pagesCount]);
    }

    public function destroy($id)
    {
        $allocation = Allocations::find($id);
        if(!$allocation) return [];
        $allocation->delete();
        return response()->json('Allocation removed successfully');
    }

}
