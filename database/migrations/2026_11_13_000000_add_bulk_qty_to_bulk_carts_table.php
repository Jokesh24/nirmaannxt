<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_carts')) {
            return;
        }

        if (!Schema::hasColumn('bulk_carts', 'bulk_qty')) {
            Schema::table('bulk_carts', function (Blueprint $table) {
                $table->integer('bulk_qty')->default(0)->after('variant_id');
            });
        }

        // Existing cart rows predate bulk_qty. Derive the pack size from the
        // already stored total quantity and number of packs.
        DB::table('bulk_carts')
            ->where(function ($query) {
                $query->whereNull('bulk_qty')->orWhere('bulk_qty', 0);
            })
            ->where('qty', '>', 0)
            ->update([
                'bulk_qty' => DB::raw('CASE WHEN order_qty > 0 THEN FLOOR(qty / order_qty) ELSE qty END'),
            ]);

        // The old unique key included qty. With multiple pack tiers for the
        // same variant, increasing a tier can produce a qty that another tier
        // already uses, so the cart flow now needs uniqueness on bulk_qty.
        $oldUnique = $this->findUniqueIndex(['user_id', 'variant_id', 'qty']);

        if ($oldUnique !== null) {
            Schema::table('bulk_carts', function (Blueprint $table) use ($oldUnique) {
                $table->dropUnique($oldUnique);
            });
        }

        $this->mergeDuplicateRows();

        if ($this->findUniqueIndex(['user_id', 'variant_id', 'bulk_qty']) === null) {
            Schema::table('bulk_carts', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'variant_id', 'bulk_qty'],
                    'bulk_carts_user_variant_bulk_qty_unique'
                );
            });
        }
    }

    protected function findUniqueIndex(array $columns): ?string
    {
        foreach (Schema::getIndexes('bulk_carts') as $index) {
            if (($index['unique'] ?? false) && $index['columns'] === $columns) {
                return $index['name'];
            }
        }

        return null;
    }

    protected function mergeDuplicateRows(): void
    {
        $duplicates = DB::table('bulk_carts')
            ->select('user_id', 'variant_id', 'bulk_qty')
            ->selectRaw('MIN(id) as keep_id, SUM(order_qty) as total_order_qty, SUM(qty) as total_qty')
            ->groupBy('user_id', 'variant_id', 'bulk_qty')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('bulk_carts')
                ->where('id', $duplicate->keep_id)
                ->update([
                    'order_qty' => (int) $duplicate->total_order_qty,
                    'qty' => (int) $duplicate->total_qty,
                ]);

            DB::table('bulk_carts')
                ->where('user_id', $duplicate->user_id)
                ->where('variant_id', $duplicate->variant_id)
                ->where('bulk_qty', $duplicate->bulk_qty)
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bulk_carts')) {
            return;
        }

        $newUnique = $this->findUniqueIndex(['user_id', 'variant_id', 'bulk_qty']);

        if ($newUnique !== null) {
            Schema::table('bulk_carts', function (Blueprint $table) use ($newUnique) {
                $table->dropUnique($newUnique);
            });
        }

        if (Schema::hasColumn('bulk_carts', 'bulk_qty')) {
            Schema::table('bulk_carts', function (Blueprint $table) {
                $table->dropColumn('bulk_qty');
            });
        }

        if ($this->findUniqueIndex(['user_id', 'variant_id', 'qty']) === null) {
            Schema::table('bulk_carts', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'variant_id', 'qty'],
                    'bulk_carts_user_variant_qty_unique'
                );
            });
        }
    }
};
