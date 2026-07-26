<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $result = $request->query('result');
        $allowed = ['booked', 'handoff', 'think', 'reject', 'new'];

        $query = Lead::query()->latest();
        if (in_array($result, $allowed, true)) {
            $query->where('result', $result);
        }

        $items  = $query->paginate(30)->withQueryString();
        $counts = Lead::selectRaw('result, count(*) as c')->groupBy('result')->pluck('c', 'result');
        $total  = Lead::count();

        return view('leads.index', compact('items', 'counts', 'total', 'result'));
    }
}
