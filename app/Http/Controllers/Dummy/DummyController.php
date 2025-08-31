<?php

namespace App\Http\Controllers\Dummy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\DummyResource;
use Illuminate\Pagination\LengthAwarePaginator;

class DummyController extends Controller
{
    public function dummy()
    {
        $data = collect([
            ['id' => 1, 'name' => 'Alpha'],
            ['id' => 2, 'name' => 'Beta'],
            ['id' => 3, 'name' => 'Gamma'],
            ['id' => 4, 'name' => 'Delta'],
        ]);

        $page = request()->get('page', 1);
        $perPage = 4;
        $items = $data->forPage($page, $perPage);

        $paginator = new LengthAwarePaginator(
            $items,
            $data->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return DummyResource::collection($paginator);
    }
}
