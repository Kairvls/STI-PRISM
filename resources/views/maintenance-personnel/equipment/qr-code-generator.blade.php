@extends('layouts.maintenance-layout')

@section('title', 'QR Code Tools')

@section('content')

<div class="space-y-6">

    <div>

        <h1 class="text-4xl font-black text-slate-900">
            QR Code Tools
        </h1>

        <p class="text-slate-500">
            Generate and manage equipment QR codes.
        </p>

    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <table class="w-full text-black">

            <thead class="bg-slate-50">

                <tr>

                    <th class="p-4 text-left">
                        Equipment
                    </th>

                    <th class="p-4 text-left">
                        Category
                    </th>

                    <th class="p-4 text-left">
                        QR Code
                    </th>

                    <th class="p-4 text-center">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach($equipment as $item)

                <tr class="border-t">

                    <td class="p-4">
                        {{ $item->equipment_name }}
                    </td>

                    <td class="p-4">
                        {{ $item->equipment_category_name }}
                    </td>

                    <td class="p-4">

                        {{ $item->equipment_qr_code ?? 'Not Generated' }}

                    </td>

                    <td>

                    <div class="flex justify-center gap-2">

                        <form
                            method="POST"
                            action="/maintenance/equipment/qr/generate/{{ $item->equipment_id }}">

                            @csrf

                            <button
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg">

                                Generate

                            </button>

                        </form>

                        <div class="mt-6 flex justify-center gap-3">

                            <button
                                onclick="printQr()"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-xl">

                                Print

                            </button>

                        </div>

                        <button
                            onclick="downloadQr()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl">

                            Download

                        </button>

                        <button
                            type="button"

                            onclick="openQrModal(
                                '{{ $item->equipment_name }}',
                                '{{ $item->equipment_qr_code }}'
                            )"

                            class="px-3 py-2 bg-indigo-600 text-white rounded-lg">

                            Preview

                        </button>

                    </div>
                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</div>

<!-- ===================================================== -->
<!-- QR PREVIEW MODAL -->
<!-- ===================================================== -->

<div
    id="qrModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-3xl p-6 max-w-md w-full">

        <div class="flex justify-between items-center mb-4">

            <h2
                id="qrEquipmentName"
                class="text-xl font-bold">

                QR Code

            </h2>

            <button
                onclick="closeQrModal()"
                class="text-2xl">

                &times;

            </button>

        </div>

        <div
            id="qrPreview"
            class="flex justify-center mb-4">

        </div>

        <div
            id="qrCodeText"
            class="text-center font-semibold text-slate-600">

        </div>

    </div>

</div>


<script>

function openQrModal(
    equipmentName,
    qrCode
){

    document.getElementById(
        'qrEquipmentName'
    ).innerText = equipmentName;

    document.getElementById(
        'qrCodeText'
    ).innerText = qrCode;

    document.getElementById(
        'qrPreview'
    ).innerHTML = `
        <img
            src="/maintenance/equipment/qr-image/${qrCode}"
            class="w-64 h-64">
    `;

    document.getElementById(
        'qrModal'
    ).classList.remove('hidden');

    document.getElementById(
        'qrModal'
    ).classList.add('flex');
}

function closeQrModal(){

    document.getElementById(
        'qrModal'
    ).classList.add('hidden');

    document.getElementById(
        'qrModal'
    ).classList.remove('flex');
}

function printQr(){

    const qrImage =
        document.getElementById(
            'qrPreview'
        ).innerHTML;

    const qrCode =
        document.getElementById(
            'qrCodeText'
        ).innerText;

    const printWindow =
        window.open(
            '',
            '',
            'width=800,height=600'
        );

    printWindow.document.write(`
        <html>
        <head>

            <title>
                ${qrCode}
            </title>

            <style>

                body{
                    display:flex;
                    justify-content:center;
                    align-items:center;
                    flex-direction:column;
                    height:100vh;
                    font-family:Arial;
                }

                img{
                    width:300px;
                }

            </style>

        </head>

        <body>

            ${qrImage}

            <h2>
                ${qrCode}
            </h2>

        </body>

        </html>
    `);

    printWindow.document.close();

    printWindow.focus();

    printWindow.print();
}

function downloadQr(){

    const img =
        document.querySelector(
            '#qrPreview img'
        );

    const link =
        document.createElement(
            'a'
        );

    link.href =
        img.src;

    link.download =
        document.getElementById(
            'qrCodeText'
        ).innerText + '.svg';

    link.click();
}

</script>

@endsection