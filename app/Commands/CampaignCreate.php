<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use LaravelZero\Framework\Commands\Command;

class CampaignCreate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'campaign:create

                            {name? : The campaign name (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new campaign.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        while ( empty($name) || File::exists(base_path("campaigns/".Str::snake($name, '-'))) ){
            if( !empty($name) ){
                $this->error("A campaign is already set in the folder campaigns/".Str::snake($name, '-'));
            }
            $name = $this->ask('Please choose a unique name for your campaign');
        }

        $folder = Str::snake($name, '-');
        $path = base_path("campaigns/".Str::snake($name, '-'));

        $this->table(
            ['Param', 'Value'],
            [
                ['Campaign name', $name],
                ['Campaign alias', $folder],
                ['Config folder', $path]
            ]
        );

        if ($this->confirm('Do you wish to continue?', true)) {
            $this->task("Creating campaign folder", function () use ($path) {
                return mkdir($path);
            });
            $this->task("Creating campaign config file", function () use ($path, $name, $folder) {
                $fileTpl = <<<'PHP'
                <?php
                /**
                 * Campaign config file
                 *
                 * @created %1$s
                 */

                return [
                    'name'    => '%2$s',
                    'subject' => '%2$s'
                ];

                PHP;

                $fileContent = sprintf($fileTpl, date('Y-m-d'), $name);

                return File::put($path.'/config.php', $fileContent);
            });
            $this->info("Campaign created.");
        } else {
            $this->info("Campaign creation cancelled.");
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
