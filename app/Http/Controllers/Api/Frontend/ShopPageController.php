<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Models\CMS;
use App\Enums\PageEnum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class ShopPageController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $data = [];

        $cmsItems = CMS::where('page', PageEnum::HOME)->where('status', 'active')->get();

        foreach ($cmsItems as $item) {
            $data[$item->section] = [
                'title' => $item->title,
                'sub_title' => $item->sub_title,
                'description' => $item->description,
                'image' => asset($item->image),
                'link' => $item->btn_link,
            ];
        }

        return $this->success($data, 'Shop page data retrieved successfully', 200);
    }
}
