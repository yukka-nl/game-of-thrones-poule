<?php

namespace App\Http\Controllers;

use App\House;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HouseController extends Controller
{
    public function index()
    {
        return House::all();
    }

    public function join(Request $request)
    {
        $user = Auth::user();
        $user->house_id = $request->houseId;
        $user->save();

        if($request->flashMessage == 'true') {
            session()->flash('message', 'You have joined ' . $user->house->name . '!');
        }
        return $user->house_id;
    }
}