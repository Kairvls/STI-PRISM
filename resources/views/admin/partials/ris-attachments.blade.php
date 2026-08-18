@php
    $files = $ris->risAttachments ?? collect();
    $downloadRoute = $downloadRoute ?? 'admin.ris.attachments.download';
@endphp
@if($files->isNotEmpty())
    <div class="mt-1 space-y-0.5">
        @foreach($files as $file)
            <a
                href="{{ route($downloadRoute, $file->ris_attachment_id) }}"
                class="block max-w-[220px] truncate text-xs text-blue-600 hover:underline"
                title="{{ $file->ris_attachment_original_name }}"
                target="_blank"
                rel="noopener"
            >
                {{ $file->ris_attachment_original_name }}
            </a>
        @endforeach
    </div>
@endif
