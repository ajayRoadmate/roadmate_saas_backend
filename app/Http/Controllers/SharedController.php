<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SharedController extends Controller
{
    public function fetchCountries()
    {
        $countryArr = DB::table('countries')
            ->select('id', 'country_name')
            ->where('country_status', 1)
            ->get();

        return handleFetchResponse($countryArr);
    }

    public function fetchStates(Request $request)
    {
        $request->validate([
            'country_id' => 'required|integer'
        ]);

        $countryId = $request->country_id;

        $stateArr = DB::table('states')
            ->select('id', 'state_name')
            ->where('country_id', $countryId)
            ->where('state_status', 1)
            ->get();

        return handleFetchResponse($stateArr);
    }

    public function fetchDistricts(Request $request)
    {
        $request->validate([
            'state_id' => 'required|integer'
        ]);

        $stateId = $request->state_id;

        $districtArr = DB::table('districts')
            ->select('id', 'district_name')
            ->where('state_id', $stateId)
            ->where('district_status', 1)
            ->get();

        return handleFetchResponse($districtArr);
    }

    public function fetchPlaceTypes()
    {

        $placeTypeArr = DB::table('place_types')
            ->select('id', 'place_type_name')
            ->where('place_type_status', 1)
            ->get();

        return handleFetchResponse($placeTypeArr);
    }

    public function fetchPlaces(Request $request)
    {
        $request->validate([
            'district_id' => 'required|integer',
            'place_type_id' => 'required|integer'
        ]);

        $districtId = $request->district_id;
        $placeTypeId = $request->place_type_id;

        $placeArr = DB::table('places')
            ->select('id', 'place_name')
            ->where('district_id', $districtId)
            ->where('place_type_id', $placeTypeId)
            ->where('place_status', 1)
            ->get();

        return handleFetchResponse($placeArr);
    }

    public function fecthShopServices()
    {
        $shopServiceArr = DB::table('services')
            ->select('id', 'service_name', 'image')
            ->where('service_type', 1)
            ->where('service_status', 1)
            ->orderBy('order_number', 'ASC')
            ->get();

        return handleFetchResponse($shopServiceArr);
    }
}
