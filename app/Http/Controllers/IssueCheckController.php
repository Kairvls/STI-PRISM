<?php

namespace App\Http\Controllers;

use App\Support\IssueChecks;
use App\Support\ReportSeverity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The Administrator manages the quick checks reporters go through for each suggested issue.
 */
class IssueCheckController extends Controller
{
    public function index($issueId)
    {
        $issue = $this->findIssue($issueId);

        $checks = IssueChecks::forTemplate((int) $issue->issue_template_id);
        $fixCounts = IssueChecks::selfFixCountsByCheck((int) $issue->issue_template_id);
        $totalFixes = IssueChecks::selfFixCount(null, (int) $issue->issue_template_id);
        $fixesThisMonth = IssueChecks::selfFixCount(now()->startOfMonth(), (int) $issue->issue_template_id);

        return view('admin.suggested-issues.checks', [
            'issue' => $issue,
            'checks' => $checks,
            'fixCounts' => $fixCounts,
            'totalFixes' => $totalFixes,
            'fixesThisMonth' => $fixesThisMonth,
            'levels' => ReportSeverity::levels(),
            'maxChecks' => IssueChecks::MAX_PER_ISSUE,
        ]);
    }

    public function store(Request $request, $issueId)
    {
        $issue = $this->findIssue($issueId);
        $count = DB::table(IssueChecks::TABLE)->where('issue_template_id', $issue->issue_template_id)->count();

        if ($count >= IssueChecks::MAX_PER_ISSUE) {
            return back()->with('error', 'Keep it short: an issue can have up to '.IssueChecks::MAX_PER_ISSUE.' quick checks.');
        }

        $data = $this->validated($request);
        $nextOrder = (int) DB::table(IssueChecks::TABLE)
            ->where('issue_template_id', $issue->issue_template_id)
            ->max('issue_check_order') + 1;

        DB::table(IssueChecks::TABLE)->insert($data + [
            'issue_template_id' => (int) $issue->issue_template_id,
            'issue_check_order' => $nextOrder,
            'issue_check_created_at' => now(),
        ]);

        return back()->with('success', 'Quick check added.');
    }

    public function update(Request $request, $issueId, $checkId)
    {
        $issue = $this->findIssue($issueId);
        $this->findCheck($issue, $checkId);

        DB::table(IssueChecks::TABLE)
            ->where('issue_check_id', $checkId)
            ->update($this->validated($request));

        return back()->with('success', 'Quick check updated.');
    }

    public function destroy($issueId, $checkId)
    {
        $issue = $this->findIssue($issueId);
        $this->findCheck($issue, $checkId);

        DB::table(IssueChecks::TABLE)->where('issue_check_id', $checkId)->delete();
        $this->renumber((int) $issue->issue_template_id);

        return back()->with('success', 'Quick check removed.');
    }

    public function move(Request $request, $issueId, $checkId)
    {
        $issue = $this->findIssue($issueId);
        $this->findCheck($issue, $checkId);
        $direction = $request->input('direction') === 'up' ? -1 : 1;

        $ids = IssueChecks::forTemplate((int) $issue->issue_template_id)
            ->pluck('issue_check_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $position = array_search((int) $checkId, $ids, true);
        $target = $position === false ? false : $position + $direction;

        if ($target !== false && isset($ids[$target])) {
            [$ids[$position], $ids[$target]] = [$ids[$target], $ids[$position]];
            $this->saveOrder($ids);
        }

        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'issue_check_kind' => ['required', Rule::in(IssueChecks::kinds())],
            'issue_check_question' => ['required', 'string', 'max:255'],
            'issue_check_hint' => ['nullable', 'string', 'max:255'],
            'issue_check_severity_if_yes' => ['nullable', Rule::in(ReportSeverity::levels())],
            'issue_check_severity_if_no' => ['nullable', Rule::in(ReportSeverity::levels())],
        ]);

        $isQuestion = $data['issue_check_kind'] === IssueChecks::KIND_ASK;

        return [
            'issue_check_kind' => $data['issue_check_kind'],
            'issue_check_question' => trim($data['issue_check_question']),
            'issue_check_hint' => trim((string) ($data['issue_check_hint'] ?? '')) ?: null,
            // Only Yes / No questions change priority; "try this" checks don't.
            'issue_check_severity_if_yes' => $isQuestion ? (($data['issue_check_severity_if_yes'] ?? null) ?: null) : null,
            'issue_check_severity_if_no' => $isQuestion ? (($data['issue_check_severity_if_no'] ?? null) ?: null) : null,
        ];
    }

    private function findIssue($issueId): object
    {
        abort_unless(IssueChecks::ready(), 404);

        $issue = DB::table('issue_templates_table')
            ->leftJoin(
                'equipment_categories_table',
                'issue_templates_table.issue_template_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->where('issue_templates_table.issue_template_id', $issueId)
            ->select('issue_templates_table.*', 'equipment_categories_table.equipment_category_name')
            ->first();

        abort_unless($issue, 404);

        return $issue;
    }

    private function findCheck(object $issue, $checkId): object
    {
        $check = DB::table(IssueChecks::TABLE)
            ->where('issue_check_id', $checkId)
            ->where('issue_template_id', $issue->issue_template_id)
            ->first();

        abort_unless($check, 404);

        return $check;
    }

    private function renumber(int $templateId): void
    {
        $this->saveOrder(
            IssueChecks::forTemplate($templateId)->pluck('issue_check_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    private function saveOrder(array $ids): void
    {
        foreach (array_values($ids) as $index => $id) {
            DB::table(IssueChecks::TABLE)
                ->where('issue_check_id', $id)
                ->update(['issue_check_order' => $index + 1]);
        }
    }
}
