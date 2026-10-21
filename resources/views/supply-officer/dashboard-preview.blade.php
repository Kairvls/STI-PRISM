@if (($kind ?? '') === 'ris')
    @include('partials.ris-signature-overlay-styles')
    <style>
        .ris-document { width: 100%; max-width: 960px; min-height: 0; padding: 0.28in 0.24in; margin: 0 auto; background: white; position: relative; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        .ris-document .header { position: relative; margin-top: 24px; margin-bottom: 10px; text-align: center; }
        .ris-document .school { font-size: 17px; font-weight: 700; letter-spacing: 0.5px; }
        .ris-document .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 18px; font-weight: 800; letter-spacing: 1px; }
        .ris-document .number { position: absolute; right: 0; bottom: -4px; font-size: 12px; }
        .ris-document .line { display: inline-block; min-width: 150px; border-bottom: 1px solid #111827; text-align: center; }
        .ris-document table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ris-document .ris-table th, .ris-document .ris-table td { border: 2px solid #374151; height: 36px; min-height: 36px; padding: 8px 6px; font-size: 11px; vertical-align: middle; word-break: break-word; overflow-wrap: anywhere; }
        .ris-document .ris-table th { text-align: center; font-weight: 700; }
        .ris-document .purpose { margin-top: 8px; font-size: 15px; font-weight: 700; }
        .ris-document .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; font-size: 14px; }
        .ris-document .signature-box { position: relative; }
        .ris-document .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
    </style>
    <div class="bg-white p-3 sm:p-5">
        @include('partials.ris-document-paper', [
            'ris' => $ris,
            'risItems' => $risItems ?? collect(),
            'presidentName' => 'President',
            'isScreenPreview' => true,
        ])
    </div>
    @if (isset($attachments) && $attachments->isNotEmpty())
        <div class="border-t border-slate-200 bg-white px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Canvas</h3>
            <ul class="mt-2 space-y-1.5">
                @foreach ($attachments as $file)
                    <li>
                        <a
                            href="{{ route('supply-officer.incoming-ris.attachment', [$ris->ris_id, $file->ris_attachment_id]) }}"
                            target="_blank"
                            rel="noopener"
                            class="text-sm font-semibold text-[#0025cc] hover:underline"
                        >{{ $file->ris_attachment_original_name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@else
    <div class="bg-slate-100 p-3 md:p-5">
        @include('partials.authority-to-purchase-paper', [
            'editable' => false,
            'atp' => $atp,
            'items' => $items ?? collect(),
        ])
    </div>
@endif
