<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Support\EquipmentQrCodes;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class QRController extends Controller
{
    public function qrTools(Request $request)
    {
        // =====================================================
        // GET CATEGORIES FOR CATEGORY DROPDOWN
        // =====================================================

        $categories = DB::table('equipment_categories_table')

            ->orderBy(
                'equipment_category_name',
                'asc'
            )

            ->get();

        $rooms = DB::table('rooms_table')
            ->leftJoin(
                'floors_table',
                'rooms_table.room_floor_id',
                '=',
                'floors_table.floor_id'
            )
            ->orderBy('rooms_table.room_name')
            ->select(
                'rooms_table.room_id',
                'rooms_table.room_name',
                'floors_table.floor_level'
            )
            ->get();


        // =====================================================
        // BUILD EQUIPMENT QUERY
        // =====================================================

        $query = DB::table('equipment_table')

        ->leftJoin(
            'equipment_categories_table',
            'equipment_table.equipment_category_id',
            '=',
            'equipment_categories_table.equipment_category_id'
        )

        // ===========================
        // ADD THIS JOIN
        // ===========================
        ->leftJoin(
            'rooms_table',
            'equipment_table.equipment_room_id',
            '=',
            'rooms_table.room_id'
        )

        ->leftJoin(
            'floors_table',
            'rooms_table.room_floor_id',
            '=',
            'floors_table.floor_id'
        )

        ->leftJoin(
            'buildings_table',
            'floors_table.floor_building_id',
            '=',
            'buildings_table.building_id'
        )

        ->select(

            'equipment_table.*',

            'equipment_categories_table.equipment_category_name',

            'rooms_table.room_name',

            'floors_table.floor_level',

            'buildings_table.building_name'

        );


        // =====================================================
        // SEARCH FILTER
        // SEARCHES THE ENTIRE DATABASE BEFORE PAGINATION
        // =====================================================

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'equipment_table.equipment_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_asset_tag',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_brand_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_model',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_serial_number',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_qr_code',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'rooms_table.room_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'buildings_table.building_name',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });

        }


        // =====================================================
        // CATEGORY FILTER
        // =====================================================

        if ($request->filled('category')) {

            $query->where(
                'equipment_table.equipment_category_id',
                $request->category
            );

        }


        if ($request->filled('room')) {

            $query->where(
                'equipment_table.equipment_room_id',
                $request->room
            );

        }


        // =====================================================
        // QR STATUS FILTER
        // =====================================================

        if ($request->filled('qr_status')) {

            if ($request->qr_status === 'generated') {

                $query

                    ->whereNotNull(
                        'equipment_table.equipment_qr_code'
                    )

                    ->where(
                        'equipment_table.equipment_qr_code',
                        '!=',
                        ''
                    );

            }


            if ($request->qr_status === 'not_generated') {

                $query->where(function ($q) {

                    $q->whereNull(
                        'equipment_table.equipment_qr_code'
                    )

                    ->orWhere(
                        'equipment_table.equipment_qr_code',
                        ''
                    );

                });

            }

        }


        // =====================================================
        // PAGINATED EQUIPMENT LIST
        // =====================================================

        $equipment = $query

            ->orderBy(
                'equipment_table.equipment_name',
                'asc'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // QR DASHBOARD DATA
        // THESE COUNTS REMAIN GLOBAL
        // THEY ARE NOT AFFECTED BY FILTERS
        // =====================================================

        $totalQrEquipment = DB::table('equipment_table')
            ->count();


        // =====================================================
        // GENERATED QR CODES
        // =====================================================

        $generatedQrCodes = DB::table('equipment_table')

            ->whereNotNull(
                'equipment_qr_code'
            )

            ->where(
                'equipment_qr_code',
                '!=',
                ''
            )

            ->count();


        // =====================================================
        // NOT GENERATED QR CODES
        // =====================================================

        $notGeneratedQrCodes =
            $totalQrEquipment
            - $generatedQrCodes;


        // =====================================================
        // GENERATED QR PERCENTAGE
        // =====================================================

        $generatedQrPercentage =
            $totalQrEquipment > 0

                ? (
                    $generatedQrCodes
                    / $totalQrEquipment
                ) * 100

                : 0;


        // =====================================================
        // NOT GENERATED QR PERCENTAGE
        // =====================================================

        $notGeneratedQrPercentage =
            $totalQrEquipment > 0

                ? (
                    $notGeneratedQrCodes
                    / $totalQrEquipment
                ) * 100

                : 0;


        // =====================================================
        // TOTAL QR SCANS
        // =====================================================

        $totalQrScans = DB::table('qr_code_logs_table')
            ->count();


        // =====================================================
        // RETURN QR TOOLS PAGE
        // =====================================================

        return view(
            'maintenance-personnel.equipment.qr-code-generator',

            compact(
                'equipment',
                'categories',
                'rooms',

                'totalQrEquipment',
                'generatedQrCodes',
                'notGeneratedQrCodes',
                'generatedQrPercentage',
                'notGeneratedQrPercentage',
                'totalQrScans'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE QR
    |--------------------------------------------------------------------------
    */

    public function generateQr($id)
    {
        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->first();

        if (!$equipment) {

            return back()->with(
                'error',
                'Equipment not found.'
            );
        }

        $isRegenerate = filled($equipment->equipment_qr_code);
        $categoryName = DB::table('equipment_categories_table')
            ->where('equipment_category_id', $equipment->equipment_category_id)
            ->value('equipment_category_name');
        $typeCode = EquipmentQrCodes::codeForCategoryName($categoryName);

        if ($typeCode) {
            $qrCode = EquipmentQrCodes::nextCode($typeCode);
        } elseif ($isRegenerate) {
            do {
                $qrCode = 'QR-'.str_pad((string) $equipment->equipment_id, 6, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6));
            } while (
                DB::table('equipment_table')
                    ->where('equipment_qr_code', $qrCode)
                    ->where('equipment_id', '!=', $equipment->equipment_id)
                    ->exists()
            );
        } else {
            $qrCode = 'QR-'.str_pad(
                $equipment->equipment_id,
                6,
                '0',
                STR_PAD_LEFT
            );
        }

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->update([

                'equipment_qr_code' => $qrCode

            ]);

        return back()->with(
            'success',
            $isRegenerate
                ? 'QR regenerated. Print a new label; the old code will no longer match this equipment.'
                : 'QR generated successfully.'
        );
    }

    // =====================================================
    // SCAN EQUIPMENT QR CODE
    // DASHBOARD CAMERA SCANNER USES THIS METHOD
    // =====================================================

    public function scanQr(Request $request)
    {
        // =====================================================
        // VALIDATE SCANNED QR VALUE
        // =====================================================

        $request->validate([
            'qr_code' => [
                'required',
                'string',
                'max:255',
            ],
        ]);


        // =====================================================
        // CLEAN QR VALUE
        //
        // NORMAL QR FORMAT:
        // QR-000004
        //
        // SOME EXISTING QR LABELS MAY CONTAIN A FULL URL:
        // /equipment/qr/QR-000004
        //
        // THIS ALLOWS BOTH FORMATS TO WORK.
        // =====================================================

        $scannedValue = trim($request->qr_code);

        $qrCode = $scannedValue;


        // =====================================================
        // IF SCANNER READS A URL, GET ONLY THE QR ID
        // =====================================================

        if (filter_var($scannedValue, FILTER_VALIDATE_URL)) {

            $path = parse_url(
                $scannedValue,
                PHP_URL_PATH
            );

            $qrCode = basename($path);
        }


        // =====================================================
        // FIND EQUIPMENT USING QR CODE
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )

            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->where(
                'equipment_table.equipment_qr_code',
                $qrCode
            )

            ->select(
                'equipment_table.*',

                'equipment_categories_table.equipment_category_name',

                'rooms_table.room_name'
            )

            ->first();


        // =====================================================
        // QR CODE DOES NOT BELONG TO EQUIPMENT
        // =====================================================

        if (!$equipment) {

            return response()->json([
                'success' => false,

                'message' =>
                    'No equipment was found for this QR code.',
            ], 404);
        }


        // =====================================================
        // RECORD SUCCESSFUL QR SCAN
        // =====================================================

        DB::table('qr_code_logs_table')->insert([

            'qr_code_equipment_id' =>
                $equipment->equipment_id,

            'qr_code_scanned_by' =>
                Auth::id(),

            // CURRENT IP ADDRESS
            'qr_code_scan_location' =>
                $request->ip(),

            // BROWSER / DEVICE INFORMATION
            'qr_code_scan_device' =>
                substr(
                    (string) $request->userAgent(),
                    0,
                    255
                ),

            'qr_code_scanned_at' =>
                now(),

        ]);


        // =====================================================
        // RETURN EQUIPMENT INFORMATION TO DASHBOARD
        // =====================================================

        return response()->json([

            'success' => true,

            'message' =>
                'Equipment found successfully.',

            'equipment' => [

                'id' =>
                    $equipment->equipment_id,

                'qr_code' =>
                    $equipment->equipment_qr_code,

                'asset_tag' =>
                    $equipment->equipment_asset_tag,

                'name' =>
                    $equipment->equipment_name,

                'brand' =>
                    $equipment->equipment_brand_name,

                'model' =>
                    $equipment->equipment_model,

                'serial_number' =>
                    $equipment->equipment_serial_number,

                'category' =>
                    $equipment->equipment_category_name,

                'room' =>
                    $equipment->room_name,

                'quantity' =>
                    $equipment->equipment_quantity,

                'condition' =>
                    $equipment->equipment_condition_status,

                'status' =>
                    $equipment->equipment_inventory_status,

                'purchase_date' =>
                    $equipment->equipment_purchase_date,

                'warranty_expiration' =>
                    $equipment->equipment_warranty_expiration,

                'borrowable' =>
                    (bool) $equipment->equipment_is_borrowable,

            ],

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QR IMAGE
    |--------------------------------------------------------------------------
    */

    public function qrImage($code)
    {
        // =====================================
        // QR PAYLOAD
        // THE MOBILE APP WILL READ THIS VALUE
        // =====================================

        $qrPayload = $code;


        // =====================================
        // GENERATE QR PREVIEW IMAGE
        // =====================================

        return response(

            QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->generate($qrPayload)

        )
        ->header(
            'Content-Type',
            'image/svg+xml'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT QR LABEL (SINGLE)
    |--------------------------------------------------------------------------
    */

    public function printLabel($code)
    {
        $equipment = $this->findEquipmentForQrPrint($code);

        abort_if(!$equipment, 404);

        return view(
            'maintenance-personnel.equipment.qr-label-print',
            [
                'equipments' => collect([$equipment]),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT QR LABELS (BATCH / MULTI ON A4)
    |--------------------------------------------------------------------------
    */

    public function printLabels(Request $request)
    {
        $codes = collect(explode(',', (string) $request->query('codes', '')))
            ->map(fn ($code) => trim($code))
            ->filter()
            ->unique()
            ->values();

        abort_if($codes->isEmpty(), 404);

        $equipments = $this->findEquipmentsForQrPrint($codes->all());

        abort_if($equipments->isEmpty(), 404);

        $ordered = $codes
            ->map(function ($code) use ($equipments) {
                return $equipments->firstWhere('equipment_qr_code', $code);
            })
            ->filter()
            ->values();

        return view(
            'maintenance-personnel.equipment.qr-label-print',
            [
                'equipments' => $ordered,
            ]
        );
    }

    private function findEquipmentForQrPrint(string $code)
    {
        return $this->qrPrintEquipmentQuery()
            ->where('equipment_table.equipment_qr_code', $code)
            ->first();
    }

    private function findEquipmentsForQrPrint(array $codes)
    {
        return $this->qrPrintEquipmentQuery()
            ->whereIn('equipment_table.equipment_qr_code', $codes)
            ->get();
    }

    private function qrPrintEquipmentQuery()
    {
        return DB::table('equipment_table')
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'floors_table',
                'rooms_table.room_floor_id',
                '=',
                'floors_table.floor_id'
            )
            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )
            ->select(
                'equipment_table.*',
                'buildings_table.building_name'
            );
    }
    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF LABEL
    |--------------------------------------------------------------------------
    */

    public function downloadQrPdf($code)
    {
        // =====================================
        // FIND EQUIPMENT AND ITS BUILDING
        // =====================================

        $equipment = DB::table('equipment_table')

            // =====================================
            // CONNECT EQUIPMENT TO ROOM
            // =====================================

            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )


            // =====================================
            // CONNECT ROOM TO FLOOR
            // =====================================

            ->leftJoin(
                'floors_table',
                'rooms_table.room_floor_id',
                '=',
                'floors_table.floor_id'
            )


            // =====================================
            // CONNECT FLOOR TO BUILDING
            // =====================================

            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )


            // =====================================
            // FIND EQUIPMENT USING QR CODE
            // =====================================

            ->where(
                'equipment_table.equipment_qr_code',
                $code
            )


            // =====================================
            // SELECT REQUIRED DATA
            // =====================================

            ->select(
                'equipment_table.*',
                'buildings_table.building_name'
            )

            ->first();


        // =====================================
        // STOP IF EQUIPMENT DOES NOT EXIST
        // =====================================

        abort_if(!$equipment, 404);


        // =====================================
        // GENERATE QR SVG
        // =====================================

        $qrSvg = QrCode::format('svg')

            ->size(600)

            ->margin(1)

            ->generate(
                $equipment->equipment_qr_code
            );


        // =====================================
        // CONVERT SVG TO BASE64 DATA URI
        // DOMPDF CAN EMBED IT DIRECTLY
        // =====================================

        $qrImage =
            'data:image/svg+xml;base64,' .
            base64_encode($qrSvg);


        // =====================================
        // LOAD PDF LABEL VIEW
        // =====================================

        $pdf = Pdf::loadView(

            'maintenance-personnel.equipment.qr-label-pdf',

            compact(
                'equipment',
                'qrImage'
            )

        );


        // =====================================
        // SET EXACT LABEL PAPER SIZE
        //
        // DOMPDF USES POINTS
        // 1 MM IS ABOUT 2.83465 POINTS
        //
        // 80 MM = 226.77 POINTS
        // 40 MM = 113.39 POINTS
        // =====================================

        $pdf->setPaper([
            0,
            0,
            226.77,
            113.39
        ]);


        // =====================================
        // DOWNLOAD PDF
        // =====================================

        return $pdf->download(

            $equipment->equipment_qr_code .
            '-label.pdf'

        );
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD QR LABEL AS PNG
    |--------------------------------------------------------------------------
    */

    public function downloadQrPng($code)
    {
        // =====================================
        // FIND EQUIPMENT USING QR CODE
        // =====================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_qr_code',
                $code
            )

            ->first();


        // =====================================
        // STOP IF EQUIPMENT DOES NOT EXIST
        // =====================================

        abort_if(!$equipment, 404);


        // =====================================
        // GET CURRENT BUILDING
        // CURRENT SYSTEM HAS ONE CAMPUS BUILDING
        // =====================================

        $building = DB::table('buildings_table')

            ->orderBy('building_id')

            ->first();


        // =====================================
        // BUILDING NAME FOR QR LABEL
        // =====================================

        $buildingName = $building?->building_name
            ?? 'PRISM';


        


        // =====================================
        // PNG LABEL DIMENSIONS
        // 2:1 RATIO FOR 80MM × 40MM LABEL
        // =====================================

        $labelWidth = 1200;

        $labelHeight = 600;


        // =====================================
        // CREATE PNG CANVAS
        // =====================================

        $canvas = imagecreatetruecolor(
            $labelWidth,
            $labelHeight
        );


        // =====================================
        // DEFINE COLORS
        // =====================================

        $white = imagecolorallocate(
            $canvas,
            255,
            255,
            255
        );

        $black = imagecolorallocate(
            $canvas,
            15,
            23,
            42
        );

        $gray = imagecolorallocate(
            $canvas,
            100,
            116,
            139
        );

        $border = imagecolorallocate(
            $canvas,
            203,
            213,
            225
        );


        // =====================================
        // WHITE BACKGROUND
        // =====================================

        imagefill(
            $canvas,
            0,
            0,
            $white
        );


        // =====================================
        // OUTER CUTTING BORDER
        // =====================================

        imagerectangle(
            $canvas,
            0,
            0,
            $labelWidth - 1,
            $labelHeight - 1,
            $border
        );


        // =====================================
        // GENERATE QR CODE AS PNG
        // =====================================

        $qrPng = QrCode::format('png')

            ->size(430)

            ->margin(1)

            ->generate(
                url(
                    '/equipment/qr/' .
                    $equipment->equipment_qr_code
                )
            );


        // =====================================
        // CREATE IMAGE RESOURCE FROM QR DATA
        // =====================================

        $qrImage = imagecreatefromstring(
            $qrPng
        );


        // =====================================
        // PLACE QR CODE
        // =====================================

        imagecopy(
            $canvas,
            $qrImage,

            65,
            85,

            0,
            0,

            imagesx($qrImage),
            imagesy($qrImage)
        );


        // =====================================
        // DIVIDER LINE
        // =====================================

        imageline(
            $canvas,

            560,
            0,

            560,
            $labelHeight,

            $black
        );


        // =====================================
        // FONT PATHS
        // TEMPORARILY USE WINDOWS ARIAL FONTS
        // =====================================

        $fontRegular =
            'C:/Windows/Fonts/arial.ttf';

        $fontSemiBold =
            'C:/Windows/Fonts/arialbd.ttf';

        $fontBold =
            'C:/Windows/Fonts/arialbd.ttf';


        // =====================================
        // VALIDATE FONT FILES
        // =====================================

        abort_unless(
            file_exists($fontRegular),
            500,
            'Regular font file not found: ' .
            $fontRegular
        );

        abort_unless(
            file_exists($fontSemiBold),
            500,
            'SemiBold font file not found: ' .
            $fontSemiBold
        );

        abort_unless(
            file_exists($fontBold),
            500,
            'Bold font file not found: ' .
            $fontBold
        );


        // =====================================
        // BUILDING NAME
        // =====================================

        


        imagettftext(
            $canvas,

            20,

            0,

            610,
            105,

            $black,

            $fontBold,

            strtoupper($buildingName)
        );


        // =====================================
        // STATIC EQUIPMENT TEXT
        // =====================================

        imagettftext(
            $canvas,

            18,

            0,

            610,
            140,

            $black,

            $fontBold,

            'EQUIPMENT'
        );


        // =====================================
        // EQUIPMENT NAME
        // =====================================

        imagettftext(
            $canvas,

            36,

            0,

            610,
            270,

            $black,

            $fontBold,

            $equipment->equipment_name
        );


        // =====================================
        // QR ID LABEL
        // =====================================

        imagettftext(
            $canvas,

            15,

            0,

            610,
            410,

            $gray,

            $fontRegular,

            'QR ID'
        );


        // =====================================
        // QR CODE VALUE
        // =====================================

        imagettftext(
            $canvas,

            18,

            0,

            610,
            450,

            $black,

            $fontSemiBold,

            $equipment->equipment_qr_code
        );


        // =====================================
        // CREATE TEMPORARY PNG FILE
        // =====================================

        $filename =
            $equipment->equipment_qr_code .
            '-label.png';


        $tempPath =
            storage_path(
                'app/' . $filename
            );


        imagepng(
            $canvas,
            $tempPath,
            9
        );


        // =====================================
        // FREE IMAGE MEMORY
        // =====================================

        imagedestroy($qrImage);

        imagedestroy($canvas);


        // =====================================
        // DOWNLOAD PNG AND DELETE TEMP FILE
        // =====================================

        return response()

            ->download(
                $tempPath,
                $filename,
                [
                    'Content-Type' => 'image/png'
                ]
            )

            ->deleteFileAfterSend(true);
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD SVG
    |--------------------------------------------------------------------------
    */

    public function downloadQrSvg($code)
    {
        // =====================================
        // VERIFY QR EXISTS
        // =====================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_qr_code',
                $code
            )

            ->first();


        abort_if(!$equipment, 404);


        // =====================================
        // QR PAYLOAD
        // =====================================

        $qrPayload = $equipment->equipment_qr_code;


        // =====================================
        // GENERATE SVG
        // =====================================

        $svg = QrCode::format('svg')

            ->size(600)

            ->margin(1)

            ->generate($qrPayload);


        // =====================================
        // DOWNLOAD FILE
        // =====================================

        return response($svg)

            ->header(
                'Content-Type',
                'image/svg+xml'
            )

            ->header(
                'Content-Disposition',
                'attachment; filename="' .
                $equipment->equipment_qr_code .
                '.svg"'
            );
    }
}
