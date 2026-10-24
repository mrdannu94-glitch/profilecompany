<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Exceptions\Cms\ContentWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentItemRequest;
use App\Models\ContentItem;
use App\Services\Cms\ContentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan konten website publik.
 *
 * Controller ini hanya mengatur formulir dan tampilan. Aturan transisi
 * status berada di {@see ContentWorkflowService} agar tidak dapat dilewati
 * melalui manipulasi request.
 */
class ContentController extends Controller
{
    public function __construct(
        protected readonly ContentWorkflowService $workflow,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContentItem::class);

        $user = $request->user();

        $items = ContentItem::query()
            ->with(['latestRevision', 'publishedRevision'])
            ->when(! $user->hasPermission(['manage_content', 'review_content', 'publish_content']), function ($query) use ($user): void {
                $query->whereHas('revisions', fn ($revision) => $revision->where('author_id', $user->getKey()));
            })
            ->when($request->filled('type'), fn ($query) => $query->where('content_type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.cms.index', [
            'items' => $items,
            'types' => ContentItem::TYPES,
            'statuses' => ContentStatus::cases(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', ContentItem::class);

        $requestedType = $request->string('type')->toString();

        if ($requestedType !== '' && in_array($requestedType, ContentItem::SINGLE_PAGE_TYPES, true)) {
            $existing = ContentItem::query()->where('content_type', $requestedType)->first();

            if ($existing instanceof ContentItem) {
                return redirect()
                    ->route('admin.cms.edit', $existing)
                    ->with('status', 'Halaman ini sudah ada. Silakan sunting konten yang tersedia.');
            }
        }

        return view('admin.cms.form', [
            'item' => null,
            'revision' => null,
            'types' => ContentItem::TYPES,
            'selectedType' => $requestedType,
        ]);
    }

    public function store(ContentItemRequest $request): RedirectResponse
    {
        $this->authorize('create', ContentItem::class);

        try {
            $item = $this->workflow->create($request->user(), $request->validated());
        } catch (ContentWorkflowException $exception) {
            return back()
                ->withInput()
                ->withErrors(['content_type' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.edit', $item)
            ->with('status', 'Draf konten berhasil dibuat.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorize('update', $item);

        $revision = $item->latestRevision()->first();

        return view('admin.cms.form', [
            'item' => $item,
            'revision' => $revision,
            'types' => ContentItem::TYPES,
            'selectedType' => $item->content_type,
            'reviews' => $item->revisions()->with('reviewer')->orderByDesc('revision_number')->get(),
        ]);
    }

    public function update(ContentItemRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        try {
            $revision = $this->workflow->update($item, $request->user(), $request->safe()->except('content_type'));
        } catch (ContentWorkflowException $exception) {
            return back()
                ->withInput()
                ->withErrors(['content' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.edit', $item)
            ->with('status', $revision->workflow_status === ContentStatus::Draft && ! $revision->wasRecentlyCreated
                ? 'Perubahan draf disimpan.'
                : 'Revisi baru berhasil disimpan.');
    }

    public function submit(Request $request, ContentItem $item): RedirectResponse
    {
        $this->authorize('submit', $item);

        try {
            $this->workflow->submit($item, $request->user());
        } catch (ContentWorkflowException $exception) {
            return back()->withErrors(['content' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.edit', $item)
            ->with('status', 'Konten diajukan dan menunggu review.');
    }

    public function review(ContentItem $item): View
    {
        $this->authorize('review', $item);

        return view('admin.cms.review', [
            'item' => $item->load(['revisions.author', 'revisions.reviewer', 'latestRevision', 'publishedRevision']),
            'revision' => $item->latestRevision()->first(),
        ]);
    }

    public function decide(Request $request, ContentItem $item): RedirectResponse
    {
        $this->authorize('review', $item);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,request_revision'],
            'note' => ['nullable', 'string', 'max:2000', 'required_if:action,request_revision'],
        ], [
            'note.required_if' => 'Catatan revisi wajib diisi saat mengembalikan konten.',
        ]);

        try {
            $this->workflow->review(
                $item,
                $request->user(),
                $validated['action'] === 'approve',
                $validated['note'] ?? null,
            );
        } catch (ContentWorkflowException $exception) {
            return back()->withErrors(['content' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.review', $item)
            ->with('status', $validated['action'] === 'approve'
                ? 'Konten disetujui dan siap diterbitkan.'
                : 'Konten dikembalikan kepada penulis untuk direvisi.');
    }

    public function publish(Request $request, ContentItem $item): RedirectResponse
    {
        $this->authorize('publish', $item);

        try {
            $this->workflow->publish($item, $request->user());
        } catch (ContentWorkflowException $exception) {
            return back()->withErrors(['content' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.edit', $item)
            ->with('status', 'Konten berhasil diterbitkan ke website publik.');
    }

    public function archive(Request $request, ContentItem $item): RedirectResponse
    {
        $this->authorize('archive', $item);

        $this->workflow->archive($item, $request->user());

        return redirect()
            ->route('admin.cms.index')
            ->with('status', 'Konten diarsipkan dan tidak lagi tampil di website publik.');
    }
}
