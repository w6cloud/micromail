<?php

namespace App\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

use LaravelZero\Framework\Commands\Command;

/**
 * CampaignCreate
 */
class CampaignCreate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'campaign:create

                            {name? : The campaign name (optional)}

                            {--subject= : The email subject (optional)}

                            {--category= : The stats category (optional)}

                            {--from_name= : The name of the sender (optional)}

                            {--from_email= : The email of the sender (optional)}';

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
        $config = [];

        $config['name'] = $this->argument('name');

        while (empty($config['name']) || File::exists(base_path("campaigns/".Str::snake($config['name'], '-')))) {
            if (!empty($config['name'])) {
                $this->error("A campaign is already set in the folder campaigns/".Str::snake($config['name'], '-'));
            }
            $config['name'] = $this->ask('Please choose a unique name for your campaign');
        }

        $config['alias'] = Str::snake($config['name'], '-');
        $config['path'] = base_path("campaigns/{$config['alias']}");

        $config['subject'] = $this->option('subject');
        if (empty($config['subject'])) {
            $config['subject'] = $this->ask('Email object?', $config['name']);
        }

        $config['category'] = $this->option('category');
        if (empty($config['category'])) {
            $config['category'] = $this->ask('Stats category?', $config['alias']);
        }

        $config['from_name'] = $this->option('from_name');
        if (empty($config['from_name'])) {
            $config['from_name'] = $this->ask('From name?', config('campaign_defaults.from_name'));
        }

        $config['from_email'] = $this->option('from_email');
        if (empty($config['from_email'])) {
            $config['from_email'] = $this->ask('From email?', config('campaign_defaults.from_email'));
        }

        $this->table(
            ['Param', 'Value'],
            [
                ['Campaign name', $config['name']],
                ['Campaign alias', $config['alias']],
                ['Config path', $config['path']],
                ['Email subject', $config['subject']],
                ['Stats category', $config['category']],
                ['Sender name', $config['from_name']],
                ['Sender email', $config['from_email']]
            ]
        );

        if ($this->confirm('Do you wish to continue?', true)) {
            $this->task(
                "Creating campaign folder",
                function () use ($config) {
                    return mkdir($config['path']);
                }
            );
            $this->task(
                "Creating campaign config file",
                function () use ($config) {
                    $fileTpl = <<<'PHP'
                    <?php
                    /**
                     * Campaign config file
                     *
                     * @created {date}
                     */

                    return [
                        'name'       => '{name}',
                        'subject'    => '{subject}',
                        'category'   => '{category}',
                        'from_name'  => '{from_name}',
                        'from_email' => '{from_email}',
                    ];

                    PHP;

                    $config['date'] = date('Y-m-d');

                    $tags = array_map(
                        function ($s) {
                            return '{'.$s.'}';
                        },
                        array_keys($config)
                    );

                    $fileContent = str_replace($tags, array_values($config), $fileTpl);

                    return File::put($config['path'].'/config.php', $fileContent);
                }
            );
            $this->task(
                "Creating contact list",
                function () use ($config) {
                    $fileContent = <<<'CSV'
                    email;name

                    CSV;

                    return File::put($config['path'].'/contacts.csv', $fileContent);
                }
            );
            $this->info("Campaign created.");
        } else {
            $this->info("Campaign creation cancelled.");
        }
    }
}
