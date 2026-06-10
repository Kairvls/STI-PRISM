<form method="POST"
      action="/store-report"
      enctype="multipart/form-data"
      class="space-y-5">

    @csrf

    <!-- MAIN CONTAINER -->
    <div class="w-full max-w-6xl mx-auto px-2">

        <!-- RESPONSIVE GRID -->
        
        <!-- LANDSCAPE FORM -->
        <div class="bg-white rounded-[32px] overflow-hidden shadow-2xl border border-gray-200">

            <div class="grid grid-cols-1 lg:grid-cols-12 lg:min-h-[560px]">

                <!-- LEFT SIDE -->
                <div class="lg:col-span-8 p-4 sm:p-5 lg:p-7">

                    <!-- TITLE -->
                    <div class="flex items-center gap-4 mb-6">

                        <div class="w-14 h-14 rounded-3xl bg-blue-100 flex items-center justify-center">

                            <i data-lucide="clipboard-signature"
                            class="w-7 h-7 text-blue-700"></i>

                        </div>

                        <div>

                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-[#081238] leading-tight">

                                Maintenance Report

                            </h1>

                            <p class="text-gray-500 text-sm mt-1">

                                Report room, facility, or equipment concerns.

                            </p>

                        </div>

                    </div>

                    <!-- EMPLOYEE -->
                    <div class="mb-5">

                        <label class="block text-sm font-bold text-gray-700 mb-2">

                            Employee ID

                        </label>

                        <div class="relative">

                            <i data-lucide="scan-face"
                            class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>

                            <input
                                type="text"
                                id="employeeIdInput"
                                name="report_reporter_employee_id"
                                value="{{ old('report_reporter_employee_id') }}"
                                placeholder="e.g. OMC0****"
                                class="w-full h-12 border border-gray-300 rounded-2xl pl-12 pr-4 text-sm focus:ring-2 focus:ring-blue-700 outline-none text-black"
                                required>

                        </div>

                    </div>

                    <!-- REPORTER BOX -->
                    <div id="reporterInfoBox"
                        class="hidden bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-3xl p-5 mb-5">

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

                            <div>

                                <p class="text-xs text-gray-500 mb-1">

                                    Full Name

                                </p>

                                <h1 id="reporterName"
                                    class="font-bold text-[#081238]">

                                    -

                                </h1>

                            </div>

                            <div>

                                <p class="text-xs text-gray-500 mb-1">

                                    Email Address

                                </p>

                                <h1 id="reporterEmail"
                                    class="font-bold text-[#081238] break-all">

                                    -

                                </h1>

                            </div>

                            <div>

                                <p class="text-xs text-gray-500 mb-1">

                                    Contact Number

                                </p>

                                <h1 id="reporterContact"
                                    class="font-bold text-[#081238]">

                                    -

                                </h1>

                            </div>

                        </div>

                    </div>

                    <!-- ERROR -->
                    <p id="employeeError"
                    class="hidden text-red-500 text-sm font-semibold mb-4">

                        Employee ID not found.

                    </p>

                    <!-- ROOM + EQUIPMENT -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

                        
                        <!-- ROOM -->
                        <div>

                            <!-- LABEL + TOGGLE -->
                            <div class="flex items-center justify-between mb-2">

                                <label class="block text-sm font-bold text-gray-700">

                                    Location

                                </label>

                                <!-- TOGGLE BUTTON -->
                                <button
                                    type="button"
                                    id="toggleRoomInput"
                                    class="text-xs font-bold text-blue-700 hover:text-blue-900 transition">

                                    Location not listed?

                                </button>

                            </div>

                            <!-- DROPDOWN MODE -->
                            <div id="roomDropdownContainer">

                                <select
                                    name="report_room_id"
                                    id="roomSelect"
                                    class="w-full h-12 border border-gray-300 rounded-2xl px-4 text-sm focus:ring-2 focus:ring-blue-700 outline-none text-black">

                                    <option value="">

                                        Select Location

                                    </option>

                                    
                                    @foreach($rooms as $room)

                                        <option value="{{ $room->room_id }}">

                                            {{ $room->floor_level }}
                                            -
                                            {{ $room->room_name }}

                                        </option>

                                    @endforeach



                                </select>

                            </div>

                            <!-- MANUAL INPUT MODE -->
                            <div
                                id="roomManualContainer"
                                class="hidden">

                                <input
                                    type="text"
                                    name="report_room_manual"
                                    id="roomManualInput"
                                    placeholder="Enter room manually..."
                                    class="w-full h-12 border border-gray-300 rounded-2xl px-4 text-sm focus:ring-2 focus:ring-blue-700 outline-none text-black">

                            </div>

                            <!-- HELPER -->
                            <p class="text-xs text-gray-400 mt-2">

                                Select from existing rooms or manually enter unavailable room.

                            </p>

                        </div>



                        
                        <!-- EQUIPMENT -->
                        <div>

                            <!-- LABEL + TOGGLE -->
                            <div class="flex items-center justify-between mb-2">

                                <label class="block text-sm font-bold text-gray-700">

                                    Equipment

                                </label>

                                <!-- TOGGLE BUTTON -->
                                <button
                                    type="button"
                                    id="toggleEquipmentInput"
                                    class="text-xs font-bold text-blue-700 hover:text-blue-900 transition">

                                    Other equipment?

                                </button>

                            </div>

                            <!-- DROPDOWN MODE -->
                            <div id="equipmentDropdownContainer">

                                <select
                                    name="report_equipment_id"
                                    id="equipmentSelect"
                                    class="w-full h-12 border border-gray-300 rounded-2xl px-4 text-sm focus:ring-2 focus:ring-blue-700 outline-none text-black">

                                    <option value="">

                                        Select Equipment

                                    </option>

                                </select>

                            </div>

                            <!-- MANUAL INPUT MODE -->
                            <div
                                id="equipmentManualContainer"
                                class="hidden">

                                <input
                                    type="text"
                                    name="report_equipment_manual"
                                    id="equipmentManualInput"
                                    placeholder="Enter equipment name manually..."
                                    class="w-full h-12 border border-gray-300 rounded-2xl px-4 text-sm focus:ring-2 focus:ring-blue-700 outline-none text-black">

                            </div>

                            <!-- HELPER -->
                            <p class="text-xs text-gray-400 mt-2">

                                Select from existing equipment or manually enter unavailable equipment.

                            </p>

                        </div>



                    </div>

                    <!-- DESCRIPTION -->
                    <div>

                        <label class="block text-sm font-bold text-gray-700 mb-2">

                            Problem Description

                        </label>

                        <textarea
                            name="report_problem_description"
                            rows="4"
                            placeholder="Describe the issue or damage..."
                            class="w-full border border-gray-300 rounded-3xl px-5 py-4 text-sm resize-none focus:ring-2 focus:ring-blue-700 outline-none text-black"
                            required>{{ old('report_problem_description') }}</textarea>

                    </div>

                    <!-- SUGGESTIONS -->
                    
                    <!-- SUGGESTED ISSUES -->
                    <div class="mt-5">

                        <!-- HEADER -->
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">

                            <p class="text-sm font-bold text-gray-700">

                                Suggested Issues

                            </p>

                            <!-- ARROWS -->
                            <div class="flex items-center gap-2">

                                <!-- LEFT -->
                                <button
                                    type="button"
                                    id="scrollLeftBtn"
                                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-blue-100 hover:bg-blue-200 flex items-center justify-center transition">

                                    <i data-lucide="chevron-left"
                                    class="w-5 h-5 text-blue-700"></i>

                                </button>

                                <!-- RIGHT -->
                                <button
                                    type="button"
                                    id="scrollRightBtn"
                                    class="w-9 h-9 rounded-full bg-blue-100 hover:bg-blue-200 flex items-center justify-center transition">

                                    <i data-lucide="chevron-right"
                                    class="w-5 h-5 text-blue-700"></i>

                                </button>

                            </div>

                        </div>

                        <!-- SLIDER -->
                        <div
                            id="issueCarousel"
                            class="flex gap-3 overflow-x-auto scroll-smooth scrollbar-hide pb-2">

                            <button type="button" class="issue-btn">

                                No Power

                            </button>

                            <button type="button" class="issue-btn">

                                Broken Monitor

                            </button>

                            <button type="button" class="issue-btn">

                                Aircon Malfunction

                            </button>

                            <button type="button" class="issue-btn">

                                Network Issue

                            </button>

                            <button type="button" class="issue-btn">

                                Not Functioning

                            </button>

                            <button type="button" class="issue-btn">

                                Keyboard Not Working

                            </button>

                            <button type="button" class="issue-btn">

                                Mouse Defective

                            </button>

                            <button type="button" class="issue-btn">

                                Projector Flickering

                            </button>

                            <button type="button" class="issue-btn">

                                Internet Connection Lost

                            </button>

                            <button type="button" class="issue-btn">

                                Broken Chair

                            </button>

                            <button type="button" class="issue-btn">

                                Electrical Issue

                            </button>

                            <button type="button" class="issue-btn">

                                Water Leakage

                            </button>

                            <button type="button" class="issue-btn">

                                Ceiling Damage

                            </button>

                            <button type="button" class="issue-btn">

                                Printer Not Working

                            </button>

                            <button type="button" class="issue-btn">

                                System Unit Failure

                            </button>

                        </div>

                    </div>






                </div>

                <!-- RIGHT PANEL -->
                <div class="lg:col-span-4 bg-[#081238] p-4 sm:p-5 lg:p-7 text-white flex flex-col justify-between gap-5">

                    <div>

                        <!-- URGENCY -->
                        <h1 class="text-2xl font-black mb-5">

                            Priority Level

                        </h1>

                        <div class="space-y-3 mb-6">

                            <label class="flex gap-3 bg-green-500/10 border border-green-400/20 rounded-3xl p-4 cursor-pointer">

                                <input
                                    type="radio"
                                    name="report_urgency_level"
                                    value="Non-Urgent"
                                    checked
                                    class="mt-1">

                                <div>

                                    <h1 class="font-bold text-green-300">

                                        Non-Urgent

                                    </h1>

                                    <p class="text-xs text-green-100 mt-1">

                                        Minor issue or repair concern

                                    </p>

                                </div>

                            </label>

                            <label class="flex gap-3 bg-red-500/10 border border-red-400/20 rounded-3xl p-4 cursor-pointer">

                                <input
                                    type="radio"
                                    name="report_urgency_level"
                                    value="Urgent"
                                    class="mt-1">

                                <div>

                                    <h1 class="font-bold text-red-300">

                                        Urgent

                                    </h1>

                                    <p class="text-xs text-red-100 mt-1">

                                        Immediate maintenance required

                                    </p>

                                </div>

                            </label>

                        </div>

                        <!-- IMAGE -->
                        <div>

                            <h1 class="text-lg font-bold mb-3">

                                Upload Proof Image

                            </h1>

                            <input
                                type="file"
                                name="report_uploaded_image"
                                class="w-full border border-white/20 bg-white/5 rounded-3xl px-4 py-5 text-sm">
                        </div>

                    </div>

                    <!-- BUTTONS -->
                    <div class="mt-8 space-y-3">

                        <button
                            type="submit"
                            class="w-full bg-[#0037C7] hover:bg-[#0044f0] py-3 sm:py-4 rounded-2xl sm:rounded-3xl font-black text-base sm:text-lg transition">

                            Submit Report

                        </button>

                        <button
                            type="button"
                            onclick="closeReportModal()"
                            class="w-full bg-white/10 hover:bg-white/20 py-3 sm:py-4 rounded-2xl sm:rounded-3xl font-black text-base sm:text-lg transition">

                            Cancel

                        </button>

                    </div>

                </div>

            </div>

        </div>


    </div>

</form>

</style>

<style>

.issue-btn{

    background:#EFF6FF;
    color:#1D4ED8;
    padding:11px 18px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
    white-space:nowrap;
    transition:.2s;
    border:1px solid #DBEAFE;
    flex-shrink:0;

}

.issue-btn:hover{

    background:#DBEAFE;

}

.scrollbar-hide::-webkit-scrollbar{

    display:none;

}

.scrollbar-hide{

    -ms-overflow-style:none;
    scrollbar-width:none;

}

</style>

<style>

@media(max-width:640px){

    #reportModal{

        align-items:flex-start;

    }

}

</style>



<script>

/*
|--------------------------------------------------------------------------
| SUGGESTED ISSUE AUTO FILL
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.issue-btn')

    .forEach(button => {

        button.addEventListener('click', function(){

            document.querySelector(
                'textarea[name="report_problem_description"]'
            ).value = this.innerText;

        });

    });

</script>


<script>

/*
|--------------------------------------------------------------------------
| REPORT SUBMISSION INTELLIGENCE LAYER
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const employeeInput =
        document.getElementById(
            'employeeIdInput'
        );

    const reporterBox =
        document.getElementById(
            'reporterInfoBox'
        );

    const employeeError =
        document.getElementById(
            'employeeError'
        );

    const roomSelect =
        document.getElementById(
            'roomSelect'
        );

    const equipmentSelect =
        document.getElementById(
            'equipmentSelect'
        );

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE VALIDATION
    |--------------------------------------------------------------------------
    */

    employeeInput.addEventListener('keyup', function () {

        let employeeId =
            this.value;

        /*
        |--------------------------------------------------------------------------
        | EMPTY INPUT
        |--------------------------------------------------------------------------
        */

        if(employeeId.length < 2){

            reporterBox.classList.add('hidden');

            employeeError.classList.add('hidden');

            return;

        }

        /*
        |--------------------------------------------------------------------------
        | FETCH REPORTER
        |--------------------------------------------------------------------------
        */

        fetch(`/get-reporter/${employeeId}`)

            .then(response => response.json())

            .then(data => {

                /*
                |--------------------------------------------------------------------------
                | REPORTER FOUND
                |--------------------------------------------------------------------------
                */

                if(data){

                    reporterBox.classList.remove('hidden');

                    employeeError.classList.add('hidden');

                    document.getElementById(
                        'reporterName'
                    ).innerText =
                        data.reporter_full_name ?? '-';

                    document.getElementById(
                        'reporterEmail'
                    ).innerText =
                        data.reporter_email_address ?? '-';

                    document.getElementById(
                        'reporterContact'
                    ).innerText =
                        data.reporter_contact_number ?? '-';

                }

                /*
                |--------------------------------------------------------------------------
                | REPORTER NOT FOUND
                |--------------------------------------------------------------------------
                */

                else{

                    reporterBox.classList.add('hidden');

                    employeeError.classList.remove('hidden');

                }

            });

    });

    /*
    |--------------------------------------------------------------------------
    | ROOM EQUIPMENT FILTER
    |--------------------------------------------------------------------------
    */

    roomSelect.addEventListener('change', function () {

        let roomId = this.value;

        equipmentSelect.innerHTML =
            '<option value="">Select Equipment</option>';

        /*
        |--------------------------------------------------------------------------
        | EMPTY ROOM
        |--------------------------------------------------------------------------
        */

        if(!roomId){

            return;

        }

        /*
        |--------------------------------------------------------------------------
        | FETCH EQUIPMENT
        |--------------------------------------------------------------------------
        */

        fetch(`/get-equipment/${roomId}`)

            .then(response => response.json())

            .then(data => {

                data.forEach(equipment => {

                    equipmentSelect.innerHTML += `

                        <option value="${equipment.equipment_id}">

                            ${equipment.equipment_name}

                        </option>

                    `;

                });

            });

    });

});

</script>

<script>

/*
|--------------------------------------------------------------------------
| ISSUE CAROUSEL
|--------------------------------------------------------------------------
*/

const issueCarousel =
    document.getElementById(
        'issueCarousel'
    );

document.getElementById(
    'scrollLeftBtn'
).addEventListener('click', () => {

    issueCarousel.scrollBy({

        left: -300,
        behavior: 'smooth'

    });

});

document.getElementById(
    'scrollRightBtn'
).addEventListener('click', () => {

    issueCarousel.scrollBy({

        left: 300,
        behavior: 'smooth'

    });

});

</script>


<script>

/*
|--------------------------------------------------------------------------
| ROOM INPUT TOGGLE
|--------------------------------------------------------------------------
*/

const toggleRoomBtn =
    document.getElementById(
        'toggleRoomInput'
    );

const roomDropdown =
    document.getElementById(
        'roomDropdownContainer'
    );

const roomManual =
    document.getElementById(
        'roomManualContainer'
    );

let roomManualMode = false;

toggleRoomBtn.addEventListener(
    'click',
    function(){

        roomManualMode =
            !roomManualMode;

        /*
        |--------------------------------------------------------------------------
        | MANUAL INPUT MODE
        |--------------------------------------------------------------------------
        */

        if(roomManualMode){

            roomDropdown
                .classList.add('hidden');

            roomManual
                .classList.remove('hidden');

            toggleRoomBtn.innerText =
                'Use location list instead';

        }

        /*
        |--------------------------------------------------------------------------
        | DROPDOWN MODE
        |--------------------------------------------------------------------------
        */

        else{

            roomDropdown
                .classList.remove('hidden');

            roomManual
                .classList.add('hidden');

            toggleRoomBtn.innerText =
                'Location not listed?';

        }

    }
);

</script>


<script>

/*
|--------------------------------------------------------------------------
| EQUIPMENT INPUT TOGGLE
|--------------------------------------------------------------------------
*/

const toggleEquipmentBtn =
    document.getElementById(
        'toggleEquipmentInput'
    );

const equipmentDropdown =
    document.getElementById(
        'equipmentDropdownContainer'
    );

const equipmentManual =
    document.getElementById(
        'equipmentManualContainer'
    );

let equipmentManualMode = false;

toggleEquipmentBtn.addEventListener(
    'click',
    function(){

        equipmentManualMode =
            !equipmentManualMode;

        /*
        |--------------------------------------------------------------------------
        | MANUAL INPUT MODE
        |--------------------------------------------------------------------------
        */

        if(equipmentManualMode){

            equipmentDropdown
                .classList.add('hidden');

            equipmentManual
                .classList.remove('hidden');

            toggleEquipmentBtn.innerText =
                'Use equipment list instead';

        }

        /*
        |--------------------------------------------------------------------------
        | DROPDOWN MODE
        |--------------------------------------------------------------------------
        */

        else{

            equipmentDropdown
                .classList.remove('hidden');

            equipmentManual
                .classList.add('hidden');

            toggleEquipmentBtn.innerText =
                'Other equipment?';

        }

    }
);

</script>






