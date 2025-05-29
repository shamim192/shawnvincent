<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Add;
use App\Models\MusicAdd;
use App\Models\SidebarAdd;
use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;

class AdvertisementController extends Controller
{
    use ApiResponse;
    public function MusicAdd()
    {
        $musicAdd = MusicAdd::all();

        return $this->success($musicAdd, 'Music Add retrieved successfully.');

    }

    public function MerchandiseAdd()
    {
        $merchandiseAdd = Add::all();

        return $this->success($merchandiseAdd, 'Merchandise Add retrieved successfully.');

    }

    public function SidebarAdd()
    {
        $sidebarAdd = SidebarAdd::all();

        return $this->success($sidebarAdd, 'Sidebar Add retrieved successfully.');

    }
}
