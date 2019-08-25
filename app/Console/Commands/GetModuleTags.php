<?php

namespace App\Console\Commands;

use App\Tag;
use Illuminate\Console\Command;
use App\Http\Helpers\InfusionsoftHelper;

class GetModuleTags extends Command
{
    public $infusionsoft;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tags:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves tags from Infusionsoft and stores them in the database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->infusionsoft = app()->make(InfusionsoftHelper::class);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        collect($this->infusionsoft->getAllTags())->each(function($tag) {
            Tag::firstOrCreate(['infusionsoft_id' => $tag['id']], [
                'infusionsoft_id' => $tag['id'],
                'name' => $tag['name'],
            ]);
        });

        $this->info('Tags updated. Locally stored tag count: ' . Tag::count());
    }
}
