<?php

namespace Yatilabs\ApiAccess\Commands;

use Illuminate\Console\Command;

class PublishConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-access:publish-config {--force : Force overwrite existing config file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the API Access configuration file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Publishing API Access configuration...');

        $params = [
            '--provider' => 'Yatilabs\ApiAccess\ApiAccessServiceProvider',
            '--tag' => 'config',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        $this->info('API Access configuration published successfully!');
        
        if ($this->option('force')) {
            $this->warn('Existing configuration file was overwritten.');
        }

        $this->line('');
        $this->line('You can now customize the configuration in: <comment>config/api-access.php</comment>');
        $this->line('');
        $this->line('Key configuration options:');
        $this->line('• <info>layout</info> - Set to your app layout file (e.g., "layouts.app") to integrate with your design');
        $this->line('• <info>routes.prefix</info> - Change the route prefix (default: "api-access")');
        $this->line('• <info>routes.middleware</info> - Set middleware for the management interface');
    }
}