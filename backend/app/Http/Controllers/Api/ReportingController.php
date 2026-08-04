<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function audit(Request $request): JsonResponse
    {
        $query = AuditEvent::where('tenant_id', $request->user()->tenant_id)->latest('occurred_at');

        return response()->json($query->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function arAging(Request $request): JsonResponse
    {
        $rows = SalesInvoice::where('tenant_id', $request->user()->tenant_id)->whereIn('status', ['posted', 'partially_paid'])->get()->map(fn ($invoice) => ['id' => $invoice->id, 'number' => $invoice->number, 'customer_id' => $invoice->customer_id, 'total' => $invoice->total, 'bucket' => $this->bucket($invoice->due_date)]);

        return response()->json(['data' => $rows->groupBy('bucket')->map(fn ($items) => ['count' => $items->count(), 'total' => $items->sum('total'), 'items' => $items->values()])]);
    }

    public function apAging(Request $request): JsonResponse
    {
        $rows = PurchaseInvoice::where('tenant_id', $request->user()->tenant_id)->whereIn('status', ['matched', 'partially_paid'])->get()->map(fn ($invoice) => ['id' => $invoice->id, 'number' => $invoice->number, 'supplier_id' => $invoice->supplier_id, 'total' => $invoice->total, 'bucket' => $this->bucket($invoice->due_date)]);

        return response()->json(['data' => $rows->groupBy('bucket')->map(fn ($items) => ['count' => $items->count(), 'total' => $items->sum('total'), 'items' => $items->values()])]);
    }

    public function inventorySummary(Request $request): JsonResponse
    {
        return response()->json(['data' => StockBalance::where('tenant_id', $request->user()->tenant_id)->selectRaw('warehouse_id, count(*) as sku_count, sum(on_hand) as total_units, sum(on_hand * average_cost) as total_value')->groupBy('warehouse_id')->get()]);
    }

    private function bucket($dueDate): string
    {
        if (! $dueDate || $dueDate->isFuture()) {
            return 'current';
        }
        $days = $dueDate->diffInDays(now());

        return match (true) {
            $days <= 30 => '1-30', $days <= 60 => '31-60', $days <= 90 => '61-90', default => '90+'
        };
    }
}
