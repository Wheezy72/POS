<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockCountService
{
    public function createSnapshot(User $user, string $name, ?string $categoryId = null, ?string $notes = null): StockCount
    {
        return DB::transaction(function () use ($user, $name, $categoryId, $notes): StockCount {
            $stockCount = StockCount::query()->create([
                'created_by' => $user->id,
                'category_id' => $categoryId,
                'name' => $name,
                'status' => 'draft',
                'snapshot_taken_at' => now(),
                'notes' => $notes,
            ]);

            Product::query()
                ->when($categoryId !== null, fn ($builder) => $builder->where('category_id', $categoryId))
                ->orderBy('name')
                ->get(['id', 'stock_quantity'])
                ->each(function (Product $product) use ($stockCount): void {
                    $stockCount->items()->create([
                        'product_id' => $product->id,
                        'expected_quantity_snapshot' => round((float) $product->stock_quantity, 2),
                        'actual_quantity' => null,
                        'variance_quantity' => null,
                    ]);
                });

            return $stockCount->load('items.product');
        });
    }

    public function recordCount(StockCount $stockCount, array $actualQuantities): StockCount
    {
        return DB::transaction(function () use ($stockCount, $actualQuantities): StockCount {
            $stockCount->loadMissing('items');

            foreach ($stockCount->items as $item) {
                if (! array_key_exists($item->product_id, $actualQuantities)) {
                    continue;
                }

                $actualQuantity = round((float) $actualQuantities[$item->product_id], 2);

                $item->update([
                    'actual_quantity' => $actualQuantity,
                    'variance_quantity' => round($actualQuantity - (float) $item->expected_quantity_snapshot, 2),
                ]);
            }

            $stockCount->update([
                'status' => 'counted',
                'counted_at' => now(),
            ]);

            return $stockCount->fresh('items.product');
        });
    }
}
