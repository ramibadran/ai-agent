<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Http\Controllers\API\V1\BaseApiController;
use App\Http\Requests\V1\Commerce\LowStockProductsRequest;
use App\Http\Requests\V1\Commerce\SalesComparisonRequest;
use App\Http\Requests\V1\Commerce\SalesSummaryRequest;
use App\Http\Requests\V1\Commerce\TopProductsRequest;
use App\Services\CommerceReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommerceReportController extends BaseApiController
{
    public function salesSummary(SalesSummaryRequest $request, CommerceReportingService $reporting): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $reporting): JsonResponse {
            return $this->apiResponse('Success', $reporting->salesSummary(
                CarbonImmutable::parse($request->string('start_date')->toString()),
                CarbonImmutable::parse($request->string('end_date')->toString()),
            ));
        });
    }

    public function salesComparison(SalesComparisonRequest $request, CommerceReportingService $reporting): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $reporting): JsonResponse {
            return $this->apiResponse('Success', $reporting->compareSalesPeriods(
                CarbonImmutable::parse($request->string('current_start_date')->toString()),
                CarbonImmutable::parse($request->string('current_end_date')->toString()),
                CarbonImmutable::parse($request->string('previous_start_date')->toString()),
                CarbonImmutable::parse($request->string('previous_end_date')->toString()),
            ));
        });
    }

    public function topProducts(TopProductsRequest $request, CommerceReportingService $reporting): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $reporting): JsonResponse {
            return $this->apiResponse('Success', $reporting->topProducts(
                CarbonImmutable::parse($request->string('start_date')->toString()),
                CarbonImmutable::parse($request->string('end_date')->toString()),
                $request->string('metric', 'revenue')->toString(),
                $request->integer('limit', 5),
            ));
        });
    }

    public function productStock(string $sku, CommerceReportingService $reporting): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($sku, $reporting): JsonResponse {
            $stock = $reporting->productStock($sku);

            return $stock === null
                ? $this->apiResponse('Product stock was not found.', statusCode: Response::HTTP_NOT_FOUND)
                : $this->apiResponse('Success', $stock);
        });
    }

    public function lowStockProducts(LowStockProductsRequest $request, CommerceReportingService $reporting): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $reporting): JsonResponse {
            return $this->apiResponse('Success', $reporting->lowStockProducts(
                $request->integer('threshold', 20),
                $request->integer('limit', 20),
            ));
        });
    }
}
