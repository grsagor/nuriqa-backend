<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Console\Command;

class PruneProductsExcept extends Command
{
    protected $signature = 'products:prune-except
                            {--keep-id= : Product ID to keep}
                            {--keep-title= : Product title to keep (partial match)}
                            {--force : Skip confirmation}';

    protected $description = 'Delete all products except the one specified by ID or title';

    public function handle(): int
    {
        $keepId = $this->option('keep-id');
        $keepTitle = $this->option('keep-title');

        if (! $keepId && ! $keepTitle) {
            $this->error('Provide --keep-id or --keep-title.');

            return self::FAILURE;
        }

        $keepProduct = null;

        if ($keepId) {
            $keepProduct = Product::find($keepId);
        } elseif ($keepTitle) {
            $keepProduct = Product::where('title', 'like', '%'.$keepTitle.'%')->first();
        }

        if (! $keepProduct) {
            $this->error('Product to keep was not found.');

            return self::FAILURE;
        }

        $toDelete = Product::with('images')
            ->where('id', '!=', $keepProduct->id)
            ->get();

        if ($toDelete->isEmpty()) {
            $this->info("Only product #{$keepProduct->id} ({$keepProduct->title}) exists. Nothing to delete.");

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Type', 'Title', 'Price'],
            $toDelete->map(fn (Product $p) => [
                $p->id,
                $p->type ?? '—',
                $p->title,
                $p->price,
            ])->all()
        );

        $this->info("Keeping: #{$keepProduct->id} — {$keepProduct->title}");

        if (! $this->option('force') && ! $this->confirm('Delete '.$toDelete->count().' product(s)?')) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($toDelete as $product) {
            if ($product->thumbnail) {
                ImageService::delete($product->thumbnail);
            }

            foreach ($product->images as $image) {
                ImageService::delete($image->image);
                $image->delete();
            }

            $product->delete();
            $deleted++;
            $this->line("Deleted #{$product->id}: {$product->title}");
        }

        $this->info("Done. Deleted {$deleted} product(s). Kept #{$keepProduct->id} ({$keepProduct->title}).");

        return self::SUCCESS;
    }
}
