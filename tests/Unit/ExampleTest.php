<?php

namespace Tests\Unit;

use App\Support\ReportGrouping;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_ticket_code_uses_rpt_date_format(): void
    {
        $report = new \stdClass();
        $report->report_id = 83;
        $report->report_submitted_at = '2026-09-07 08:30:00';

        $this->assertSame('RPT-20260907-083', ReportGrouping::ticketCode($report));
    }
}
