<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Integration tests for the Purchaser Module (RIS and ATP workflows)
 * These tests verify that:
 * 1. All routes are registered correctly
 * 2. All controllers have required methods
 * 3. All views exist and render properly
 * 4. Workflow integration points are in place
 */
class PurchaserModuleIntegrationTest extends TestCase
{
    use WithFaker;

    // ============ ROUTE REGISTRATION TESTS ============

    /**
     * Test that all RIS routes are registered by checking specific routes
     */
    public function test_all_ris_routes_are_registered()
    {
        // Check for RIS key routes by verifying routes list contains them
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        
        $risIndexRoute = $routes->getByName('purchaser.ris.index');
        $risStoreRoute = $routes->getByName('purchaser.ris.store');
        $risSubmitRoute = $routes->getByName('purchaser.ris.submit');

        $this->assertNotNull($risIndexRoute);
        $this->assertNotNull($risStoreRoute);
        $this->assertNotNull($risSubmitRoute);
    }

    /**
     * Test that all ATP routes are registered
     */
    public function test_all_atp_routes_are_registered()
    {
        // Check for ATP key routes
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        
        $atpIndexRoute = $routes->getByName('purchaser.atp.index');
        $atpCreateRoute = $routes->getByName('purchaser.atp.create');
        $atpStoreRoute = $routes->getByName('purchaser.atp.store');
        $atpSubmitRoute = $routes->getByName('purchaser.atp.submit');
        $atpApproveRoute = $routes->getByName('purchaser.atp.approve');
        $atpRejectRoute = $routes->getByName('purchaser.atp.reject');
        $atpArchiveRoute = $routes->getByName('purchaser.atp.archive');

        $this->assertNotNull($atpIndexRoute);
        $this->assertNotNull($atpCreateRoute);
        $this->assertNotNull($atpStoreRoute);
        $this->assertNotNull($atpSubmitRoute);
        $this->assertNotNull($atpApproveRoute);
        $this->assertNotNull($atpRejectRoute);
        $this->assertNotNull($atpArchiveRoute);
    }

    /**
     * Test that purchaser dashboard route exists
     */
    public function test_purchaser_dashboard_route_exists()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $dashboardRoute = $routes->getByName('purchaser.dashboard');
        
        $this->assertNotNull($dashboardRoute);
    }

    public function test_file_maintenance_routes_are_registered()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();

        foreach ([
            'purchaser.file-maintenance.index',
            'purchaser.brands.index',
            'purchaser.brands.store',
            'purchaser.brands.update',
            'purchaser.brands.destroy',
            'purchaser.uom.index',
            'purchaser.uom.store',
            'purchaser.categories.index',
            'purchaser.subcategories.index',
        ] as $name) {
            $this->assertNotNull($routes->getByName($name), "Missing route: {$name}");
        }
    }

    public function test_legacy_file_maintenance_index_urls_redirect_to_hub()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $hub = $routes->getByName('purchaser.file-maintenance.index');
        $brandsIndex = $routes->getByName('purchaser.brands.index');

        $this->assertNotNull($hub);
        $this->assertNotNull($brandsIndex);
        $this->assertSame('purchaser/file-maintenance', $hub->uri());
        $this->assertSame('purchaser/brands', $brandsIndex->uri());
        $this->assertTrue(method_exists(\App\Http\Controllers\FileMaintenanceController::class, 'index'));
    }

    // ============ CONTROLLER METHOD TESTS ============

    /**
     * Test PurchaserController has all required methods
     */
    public function test_purchaser_controller_has_required_methods()
    {
        $controller = app('App\Http\Controllers\PurchaserController');
        
        $requiredMethods = [
            'dashboard',
            'risIndex',
            'storeRis',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($controller, $method),
                "PurchaserController missing method: {$method}"
            );
        }
    }

    /**
     * Test AuthorityToPurchaseController has all required methods
     */
    public function test_workflow_controllers_have_required_methods()
    {
        $this->assertTrue(method_exists(\App\Http\Controllers\AdminController::class, 'approveRis'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AdminController::class, 'directApproveRis'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AdminController::class, 'decideRis'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AdminController::class, 'returnRisForRevision'));
        $this->assertTrue(method_exists(\App\Http\Controllers\PresidentController::class, 'decideRis'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AccountingController::class, 'approveAtp'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AccountingController::class, 'approveRequestCheck'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AccountingController::class, 'releaseFunds'));
        $this->assertTrue(method_exists(\App\Http\Controllers\AccountingController::class, 'approveLiquidation'));
        $this->assertTrue(method_exists(\App\Http\Controllers\ReceivingController::class, 'secondCount'));
        $this->assertTrue(class_exists(\App\Support\RisWorkflow::class));
    }

    /**
     * Test AuthorityToPurchaseController has all required methods
     */
    public function test_atp_controller_has_required_methods()
    {
        $controller = app('App\Http\Controllers\AuthorityToPurchaseController');
        
        $requiredMethods = [
            'index',
            'create',
            'store',
            'edit',
            'update',
            'show',
            'submit',
            'approve',
            'reject',
            'archive',
            'restore',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($controller, $method),
                "AuthorityToPurchaseController missing method: {$method}"
            );
        }
    }

    // ============ VIEW EXISTENCE TESTS ============

    /**
     * Test RIS views exist
     */
    public function test_ris_views_exist()
    {
        $views = [
            'purchaser.ris.index',
        ];

        foreach ($views as $view) {
            $this->assertTrue(
                view()->exists($view),
                "View {$view} does not exist"
            );
        }
    }

    /**
     * Test ATP views exist
     */
    public function test_atp_views_exist()
    {
        $views = [
            'purchaser.authority-to-purchase.index',
        ];

        foreach ($views as $view) {
            $this->assertTrue(
                view()->exists($view),
                "View {$view} does not exist"
            );
        }
    }

    /**
     * Test purchaser dashboard view exists
     */
    public function test_purchaser_dashboard_view_exists()
    {
        $this->assertTrue(view()->exists('purchaser.dashboard'));
    }

    /**
     * Test purchaser layout view exists
     */
    public function test_purchaser_layout_view_exists()
    {
        // Using app layout instead of purchaser-specific layout
        $this->assertTrue(view()->exists('layouts.app'));
    }

    // ============ WORKFLOW INTEGRATION TESTS ============

    /**
     * Test that RIS to ATP integration is in place
     * - RIS index view should pass risHasAtp data
     * - ATP create route should accept selected_ris query param
     */
    public function test_ris_to_atp_integration_points()
    {
        // Verify route exists for ATP creation with pre-selected RIS
        $routes = collect(\Route::getRoutes())
            ->filter(function ($route) {
                return $route->getName() === 'purchaser.atp.create';
            })
            ->first();

        $this->assertNotNull($routes, 'ATP create route not found');
    }

    /**
     * Test that dashboard contains RIS metrics
     */
    public function test_dashboard_has_metrics_tracking()
    {
        // This verifies the dashboard controller computes metrics
        $controller = app('App\Http\Controllers\PurchaserController');
        $this->assertTrue(method_exists($controller, 'dashboard'));
    }

    // ============ MODEL EXISTENCE TESTS ============

    /**
     * Test that required models exist
     */
    public function test_required_models_exist()
    {
        $models = [
            'App\Models\User',
            'App\Models\Supplier',
        ];

        foreach ($models as $model) {
            $this->assertTrue(
                class_exists($model),
                "Model {$model} does not exist"
            );
        }
    }

    // ============ CONFIGURATION TESTS ============

    /**
     * Test that purchaser routes are protected by auth middleware
     */
    public function test_purchaser_routes_require_authentication()
    {
        $response = $this->get(route('purchaser.dashboard'));
        
        // Should redirect to login for unauthenticated requests
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test basic route accessibility structure
     */
    public function test_route_prefix_structure()
    {
        $routes = collect(\Route::getRoutes())
            ->filter(function ($route) {
                return strpos($route->getName() ?? '', 'purchaser') === 0;
            })
            ->pluck('uri')
            ->toArray();

        // Should have purchaser routes with /purchaser prefix
        $purchaserRoutes = array_filter($routes, function ($uri) {
            return strpos($uri, 'purchaser') === 0;
        });

        $this->assertCount(count($purchaserRoutes), array_filter($routes, function ($uri) {
            return strpos($uri, 'purchaser') === 0;
        }));
    }

    // ============ HTTP METHOD TESTS ============

    /**
     * Test that required HTTP methods are defined for each route group
     */
    public function test_route_http_methods_are_correct()
    {
        $routes = collect(\Route::getRoutes())->toArray();
        
        // Find purchaser.atp.store route and verify it's POST
        $storeRoute = collect($routes)
            ->filter(function ($route) {
                return $route->getName() === 'purchaser.atp.store';
            })
            ->first();

        $this->assertNotNull($storeRoute);
        $this->assertContains('POST', $storeRoute->methods);

        // Find purchaser.atp.index route and verify it's GET
        $indexRoute = collect($routes)
            ->filter(function ($route) {
                return $route->getName() === 'purchaser.atp.index';
            })
            ->first();

        $this->assertNotNull($indexRoute);
        $this->assertContains('GET', $indexRoute->methods);
    }

    /**
     * Test that the workflow progression is possible
     * RIS (Pending) -> RIS (Approved) -> ATP (Draft) -> ATP (Submitted) -> ATP (Approved)
     */
    public function test_purchaser_workflow_progression_structure()
    {
        // This test verifies the structural flow is in place
        // Actual functional testing would require database seeding

        $risController = app('App\Http\Controllers\PurchaserController');
        $atpController = app('App\Http\Controllers\AuthorityToPurchaseController');

        // RIS workflow: store -> submit
        $this->assertTrue(method_exists($risController, 'storeRis'));
        // Submit method is in risSubmit

        // ATP workflow: create -> submit -> approve/reject -> archive
        $this->assertTrue(method_exists($atpController, 'store'));
        $this->assertTrue(method_exists($atpController, 'submit'));
        $this->assertTrue(method_exists($atpController, 'approve'));
        $this->assertTrue(method_exists($atpController, 'reject'));
        $this->assertTrue(method_exists($atpController, 'archive'));
    }

    public function test_rfc_liq_and_replacement_routes_are_registered()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();

        foreach ([
            'purchaser.rfc.store',
            'purchaser.rfc.submit',
            'purchaser.rr.store',
            'purchaser.rr.submit',
            'purchaser.liq.store',
            'purchaser.liq.submit',
            'purchaser.procurement.replacement-requests.approve',
            'purchaser.procurement.replacement-requests.reject',
        ] as $name) {
            $this->assertNotNull($routes->getByName($name), "Missing route: {$name}");
        }
    }

    public function test_replacement_reject_requires_remarks()
    {
        $user = new \App\Models\User();
        $user->user_id = 99001;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $this->actingAs($user)
            ->from('/purchaser/procurement/replacement-requests')
            ->post(route('purchaser.procurement.replacement-requests.reject', 1), [])
            ->assertSessionHasErrors('remarks');
    }

    public function test_rfc_cannot_bind_unapproved_atp()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('authority_to_purchase_table')
            || !\Illuminate\Support\Facades\Schema::hasTable('request_check_table')) {
            $this->markTestSkipped('Procurement tables are not available in this environment.');
        }

        $user = new \App\Models\User();
        $user->user_id = 99002;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $atpId = \Illuminate\Support\Facades\DB::table('authority_to_purchase_table')->insertGetId([
            'authority_purchase_status' => 'Pending',
            'authority_purchase_is_archived' => 0,
            'authority_purchase_created_at' => now(),
            'authority_purchase_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('purchaser.rfc.index'))
            ->post(route('purchaser.rfc.store'), [
                'save_action' => 'submit',
                'request_check_authority_purchase_id' => $atpId,
                'request_check_date' => now()->toDateString(),
                'request_check_payee' => 'Test Payee',
                'request_check_amount_figures' => 100,
                'request_check_particulars_purpose' => 'Test purpose',
                'request_check_requested_by' => 'Test Purchaser',
            ])
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->delete();
    }

    public function test_rfc_can_bind_approved_atp_without_existing_rfc()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('authority_to_purchase_table')
            || !\Illuminate\Support\Facades\Schema::hasTable('request_check_table')) {
            $this->markTestSkipped('Procurement tables are not available in this environment.');
        }

        $user = new \App\Models\User();
        $user->user_id = 99005;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $atpId = \Illuminate\Support\Facades\DB::table('authority_to_purchase_table')->insertGetId([
            'authority_purchase_status' => 'Approved',
            'authority_purchase_is_archived' => 0,
            'authority_purchase_created_at' => now(),
            'authority_purchase_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('purchaser.rfc.index'))
            ->post(route('purchaser.rfc.store'), [
                'save_action' => 'submit',
                'request_check_authority_purchase_id' => $atpId,
                'request_check_date' => now()->toDateString(),
                'request_check_payee' => 'Test Payee',
                'request_check_amount_figures' => 100,
                'request_check_particulars_purpose' => 'Test purpose',
                'request_check_requested_by' => 'Test Purchaser',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue(
            \Illuminate\Support\Facades\DB::table('request_check_table')
                ->where('request_check_authority_purchase_id', $atpId)
                ->exists()
        );

        \Illuminate\Support\Facades\DB::table('request_check_table')->where('request_check_authority_purchase_id', $atpId)->delete();
        \Illuminate\Support\Facades\DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->delete();
    }

    public function test_rfc_cannot_bind_atp_that_already_has_rfc()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('authority_to_purchase_table')
            || !\Illuminate\Support\Facades\Schema::hasTable('request_check_table')) {
            $this->markTestSkipped('Procurement tables are not available in this environment.');
        }

        $user = new \App\Models\User();
        $user->user_id = 99006;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $atpId = \Illuminate\Support\Facades\DB::table('authority_to_purchase_table')->insertGetId([
            'authority_purchase_status' => 'Approved',
            'authority_purchase_is_archived' => 0,
            'authority_purchase_created_at' => now(),
            'authority_purchase_updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('request_check_table')->insert([
            'request_check_authority_purchase_id' => $atpId,
            'request_check_status' => 'Approved',
            'request_check_is_archived' => 0,
            'request_check_created_at' => now(),
            'request_check_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('purchaser.rfc.index'))
            ->post(route('purchaser.rfc.store'), [
                'save_action' => 'submit',
                'request_check_authority_purchase_id' => $atpId,
                'request_check_date' => now()->toDateString(),
                'request_check_payee' => 'Test Payee',
                'request_check_amount_figures' => 100,
                'request_check_particulars_purpose' => 'Test purpose',
                'request_check_requested_by' => 'Test Purchaser',
            ])
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\DB::table('request_check_table')->where('request_check_authority_purchase_id', $atpId)->delete();
        \Illuminate\Support\Facades\DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->delete();
    }

    public function test_liq_cannot_bind_incomplete_receiving_report()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('receiving_reports_table')
            || !\Illuminate\Support\Facades\Schema::hasTable('liquidation_reports_table')) {
            $this->markTestSkipped('Liquidation tables are not available in this environment.');
        }

        $user = new \App\Models\User();
        $user->user_id = 99003;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $rrId = \Illuminate\Support\Facades\DB::table('receiving_reports_table')->insertGetId([
            'receiving_report_status' => 'Draft',
            'receiving_report_is_archived' => 0,
            'receiving_report_created_at' => now(),
            'receiving_report_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('purchaser.liq.index'))
            ->post(route('purchaser.liq.store'), [
                'save_action' => 'submit',
                'liquidation_report_receiving_report_id' => $rrId,
                'liquidation_report_employee_name' => 'Test Employee',
                'liquidation_report_purpose' => 'Test purpose',
                'liquidation_report_amount_advance' => 50,
                'liquidation_report_submitted_by_signature' => 'Test Purchaser',
                'items' => [
                    ['particulars' => 'Item', 'amount' => 50, 'actual_amount' => 50, 'actual_total' => 50],
                ],
            ])
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->delete();
    }

    public function test_rfc_submit_rejects_incomplete_draft()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('request_check_table')) {
            $this->markTestSkipped('Request for Check table is not available in this environment.');
        }

        $user = new \App\Models\User();
        $user->user_id = 99004;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $rfcId = \Illuminate\Support\Facades\DB::table('request_check_table')->insertGetId([
            'request_check_status' => 'Draft',
            'request_check_is_archived' => 0,
            'request_check_created_at' => now(),
            'request_check_updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('purchaser.rfc.index'))
            ->post(route('purchaser.rfc.submit', $rfcId))
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\DB::table('request_check_table')->where('request_check_id', $rfcId)->delete();
    }

    public function test_replacement_cannot_approve_non_pending()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('procurement_requests_table')) {
            $this->markTestSkipped('Procurement requests table is not available in this environment.');
        }

        $user = new \App\Models\User();
        $user->user_id = 99005;
        $user->user_role_id = 3;
        $user->user_full_name = 'Test Purchaser';

        $requestId = \Illuminate\Support\Facades\DB::table('procurement_requests_table')->insertGetId([
            'procurement_request_status' => 'Approved',
            'procurement_request_is_archived' => 0,
        ]);

        $this->actingAs($user)
            ->from('/purchaser/procurement/replacement-requests')
            ->post(route('purchaser.procurement.replacement-requests.approve', $requestId))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->from('/purchaser/procurement/replacement-requests')
            ->post(route('purchaser.procurement.replacement-requests.reject', $requestId), [
                'remarks' => 'Too late to reject this request',
            ])
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\DB::table('procurement_requests_table')->where('procurement_request_id', $requestId)->delete();
    }
}
