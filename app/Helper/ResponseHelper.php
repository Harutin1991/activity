<?php


namespace App\Helper;

class ResponseHelper
{
    const OK = 200;
    const UNAUTHORIZED = 401;
    const UNPROCESSABLE_ENTITY_EXPLAINED = 422;

    public static function success($data, $pagination = null, $msg = null)
    {
        if($pagination == null) {
            $response = array(
                "message" => $msg ?? "",
                "data" => $data,
                "status" => true
            );
        } else {
            $data = $data->toArray();
            $response = array(
                "message" => $msg ?? "",
                "data" => array(
                    "list" => $data["data"],
                    "meta" => array(
                        "page" => $data['current_page'],
                        "limit" => intval($data['per_page']),
                        "total" => $data['total'],
                        "last_page" => $data["last_page"]
                    ),
                ),
                "status" => true
            );
        }
        return response()->json($response, 200);
    }

    public static function makeAllocationData($allocations)
    {
        $allocationResponse = [];
        if(!isset($allocations->id)) {
            foreach ($allocations as $key => $allocation) {
                $school = $allocation->school;
                $isFinal = (bool)$allocation->is_final;
                $allocationResponse[$key] = $allocation;
                $allocationResponse[$key]['is_final'] = $isFinal;
                $allocationResponse[$key]['grand_total'] = (($allocation->allocation_type == 1) ? $allocation->total_instruction + $allocation->family_engagement :
                    ($allocation->allocation_type == 2)) ? $allocation->professional_development + $allocation->materials :
                    (($allocation->allocation_type == 3) ? $allocation->total_instruction : 0);
                $allocationResponse[$key]['school'] = ($school) ? $school->school_name : '';
            }
        } else {
            $allocation = $allocations;
            $school = $allocation->school;
            $isFinal = (bool)$allocation->is_final;
            $allocationResponse[0] = $allocation;
            $allocationResponse[0]['is_final'] = $isFinal;
            $allocationResponse[0]['grand_total'] = (($allocation->allocation_type == 1) ? $allocation->total_instruction + $allocation->family_engagement :
                ($allocation->allocation_type == 2)) ? $allocation->professional_development + $allocation->materials :
                (($allocation->allocation_type == 3) ? $allocation->total_instruction : 0);
            $allocationResponse[0]['school'] = ($school) ? $school->school_name : '';
        }
        return $allocationResponse;
    }

    public static function fail($msg, $code)
    {
        $response = array(
            "message" => $msg ?? "",
            "data" => array(),
            "status" => false
        );
        return response()->json($response, $code);
    }
}
