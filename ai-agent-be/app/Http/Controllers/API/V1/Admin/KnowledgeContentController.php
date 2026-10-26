<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\API\V1\BaseApiController;
use App\Http\Requests\V1\Admin\ListKnowledgeContentRequest;
use App\Http\Requests\V1\Admin\StoreKnowledgeContentRequest;
use App\Http\Requests\V1\Admin\UpdateKnowledgeContentRequest;
use App\Http\Resources\V1\KnowledgeContentResource;
use App\Models\KnowledgeContent;
use App\Services\V1\KnowledgeContentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class KnowledgeContentController extends BaseApiController
{
    public function index(ListKnowledgeContentRequest $request): JsonResponse
    {
        $contents = KnowledgeContent::query()
            ->with('createdBy')
            ->withCount('indexRuns')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return $this->apiResponse('Success', [
            'items' => KnowledgeContentResource::collection($contents->getCollection())->resolve(),
            'pagination' => [
                'current_page' => $contents->currentPage(),
                'last_page' => $contents->lastPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ],
        ]);
    }

    public function store(StoreKnowledgeContentRequest $request, KnowledgeContentService $knowledgeContentService): JsonResponse
    {
        $content = $knowledgeContentService->create($request->user(), $request->validated());

        return $this->apiResponse(
            'Content created and queued for indexing.',
            new KnowledgeContentResource($content->load('createdBy')->loadCount('indexRuns')),
            Response::HTTP_CREATED,
        );
    }

    public function show(KnowledgeContent $knowledgeContent): JsonResponse
    {
        return $this->apiResponse('Success', new KnowledgeContentResource($knowledgeContent->load('createdBy')->loadCount('indexRuns')));
    }

    public function update(UpdateKnowledgeContentRequest $request, KnowledgeContent $knowledgeContent, KnowledgeContentService $knowledgeContentService): JsonResponse
    {
        $content = $knowledgeContentService->update($knowledgeContent, $request->validated());

        return $this->apiResponse('Content updated and queued for reindexing.', new KnowledgeContentResource($content->load('createdBy')->loadCount('indexRuns')));
    }

    public function destroy(KnowledgeContent $knowledgeContent, KnowledgeContentService $knowledgeContentService): JsonResponse
    {
        $knowledgeContentService->delete($knowledgeContent);

        return $this->apiResponse('Content deleted.');
    }
}
