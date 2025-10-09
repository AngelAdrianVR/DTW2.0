<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebContent;
use Carbon\Carbon;

class UnpublishExpiredContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:unpublish-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unpublishes web content items that have passed their expiration date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired web content...');

        // Busca contenido que esté publicado, tenga fecha de finalización y esta ya haya pasado.
        $query = WebContent::where('is_published', true)
                           ->whereNotNull('end_date')
                           ->where('end_date', '<', Carbon::now());

        $count = $query->count();

        if ($count > 0) {
            // Actualiza el estado a "no publicado".
            $query->update(['is_published' => false]);
            $this->info("Successfully unpublished {$count} items.");
        } else {
            $this->info('No expired content found.');
        }

        return Command::SUCCESS;
    }
}
