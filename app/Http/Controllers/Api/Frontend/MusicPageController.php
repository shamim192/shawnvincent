<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Models\CMS;
use App\Enums\PageEnum;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MusicPageController extends Controller
{
    use ApiResponse;

    public function index() {

        $data = [];

        $cmsItems = CMS::where('page', PageEnum::MUSIC)->where('status', 'active')->get();

        foreach ($cmsItems as $item) {
            $data[$item->section] = [
                'title' => $item->title,
                'sub_title' => $item->sub_title,
                'description' => $item->description,
                'image' => asset($item->image),
                'link' => $item->music_link,
            ];
        }

        return $this->success($data, 'Music page data retrieved successfully', 200);

    }
}
