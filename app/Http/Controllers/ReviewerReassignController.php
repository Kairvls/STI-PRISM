<?php

namespace App\Http\Controllers;

use App\Support\ReviewerAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewerReassignController extends Controller
{
    public function __invoke(Request $request, string $type, $id)
    {
        $type = strtolower(trim($type));
        $catalog = ReviewerAssignment::reassignCatalog();

        if (! isset($catalog[$type])) {
            return back()->with('error', 'Unknown document type.');
        }

        $validated = $request->validate([
            'assigned_reviewer_id' => [
                'required',
                'integer',
                Rule::in(array_column(ReviewerAssignment::options($catalog[$type]['role']), 'id')),
            ],
        ]);

        $result = ReviewerAssignment::reassignForPurchaser(
            $type,
            (int) $id,
            (int) $validated['assigned_reviewer_id']
        );

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
