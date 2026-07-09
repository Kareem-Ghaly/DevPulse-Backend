<?php

use App\Events\ProjectProposalChanged;
use App\Models\ProjectProposal;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMP TEST ROUTE - REMOVE LATER
Route::get('/test-proposal-broadcast', function () {
    $proposal = ProjectProposal::first();

    if (! $proposal) {
        return response()->json([
            'status' => false,
            'message' => 'No project proposal found',
        ], 404);
    }

    ProjectProposalChanged::dispatch($proposal, 'updated');

    return response()->json([
        'status' => true,
        'message' => 'broadcast dispatched',
    ]);
});