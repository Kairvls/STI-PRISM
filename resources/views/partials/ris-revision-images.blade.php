{{--
  Proof images attached to a revision note (RIS note or document_revision_notes_table row).
  Expects: $revision. Optional: $routeName (defaults to the matching purchaser route), $size = sm|md
  Include partials.ris-revision-image-viewer once on the page (outside Alpine templates) for the enlarge view.
--}}
@php
    $revisionImages = \App\Support\RisRevisionImages::decode($revision ?? null);
    $isDocumentRevision = isset($revision->document_revision_id);
    $revisionKey = $isDocumentRevision ? $revision->document_revision_id : ($revision->ris_revision_id ?? null);
    $routeName = $routeName ?? ($isDocumentRevision ? 'purchaser.document-revision-image' : 'purchaser.ris.revision-image');
    $thumbClass = ($size ?? 'md') === 'sm' ? 'h-16 w-16' : 'h-24 w-24 sm:h-28 sm:w-28';
@endphp
@if ($revisionImages !== [] && $revisionKey && \Illuminate\Support\Facades\Route::has($routeName))
    <div class="mt-3">
        <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">
            Attached images ({{ count($revisionImages) }})
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($revisionImages as $index => $image)
                @php
                    $imageUrl = route($routeName, ['revisionId' => $revisionKey, 'index' => $index]);
                    $imageName = $image['name'] ?? ('Image ' . ($index + 1));
                @endphp
                <a
                    href="{{ $imageUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="group relative {{ $thumbClass }} shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 transition hover:border-orange-300 hover:ring-2 hover:ring-orange-100"
                    title="View {{ $imageName }}"
                    data-name="{{ $imageName }}"
                    onclick="if (window.openRisRevisionImage) { event.preventDefault(); window.openRisRevisionImage(this); }"
                >
                    <img src="{{ $imageUrl }}" alt="{{ $imageName }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                </a>
            @endforeach
        </div>
    </div>
@endif
