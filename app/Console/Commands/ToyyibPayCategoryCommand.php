<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ToyyibPayCategoryCommand extends Command
{
    protected $signature = 'toyyibpay:category 
                            {--create : Create a new category automatically}
                            {--name=SparkLab Store : Category name}
                            {--desc=SparkLab Science Kits and Products : Category description}';

    protected $description = 'Fetch or create ToyyibPay Category Code using the configured User Secret Key';

    public function handle()
    {
        $secretKey = config('toyyibpay.user_secret_key');
        $isSandbox = config('toyyibpay.sandbox', true);
        $baseUrl = $isSandbox
            ? config('toyyibpay.sandbox_url', 'https://dev.toyyibpay.com')
            : config('toyyibpay.production_url', 'https://toyyibpay.com');

        if (empty($secretKey)) {
            $this->error('TOYYIBPAY_USER_SECRET_KEY is empty in .env.');
            return 1;
        }

        $this->info("Checking ToyyibPay gateway (" . ($isSandbox ? 'Sandbox: dev.toyyibpay.com' : 'Production: toyyibpay.com') . ")...");

        // Try to fetch existing categories
        $this->line("Fetching existing categories from ToyyibPay...");
        try {
            $response = Http::asForm()->timeout(10)->post("{$baseUrl}/index.php/api/getCategory", [
                'userSecretKey' => $secretKey,
            ]);

            $categories = $response->json();

            if (is_array($categories) && !empty($categories) && isset($categories[0]['categoryName'])) {
                $this->info("Found existing categories in your ToyyibPay account:");
                $headers = ['Category Code', 'Category Name', 'Category Description', 'Status'];
                $rows = [];
                foreach ($categories as $cat) {
                    $rows[] = [
                        $cat['categoryCode'] ?? $cat['CategoryCode'] ?? '-',
                        $cat['categoryName'] ?? '-',
                        $cat['categoryDescription'] ?? '-',
                        $cat['categoryStatus'] ?? '-',
                    ];
                }
                $this->table($headers, $rows);

                $firstCode = $categories[0]['categoryCode'] ?? $categories[0]['CategoryCode'] ?? null;
                if ($firstCode && $this->confirm("Would you like to set TOYYIBPAY_CATEGORY_CODE={$firstCode} in your .env?", true)) {
                    $this->updateEnvFile('TOYYIBPAY_CATEGORY_CODE', $firstCode);
                    $this->info("Successfully updated TOYYIBPAY_CATEGORY_CODE={$firstCode} in .env!");
                }
                return 0;
            }
        } catch (\Exception $e) {
            $this->warn("Could not fetch categories: " . $e->getMessage());
        }

        // If no existing category found or --create requested
        $this->info("Creating a new category on ToyyibPay...");
        $name = $this->option('name');
        $desc = $this->option('desc');

        try {
            $response = Http::asForm()->timeout(15)->post("{$baseUrl}/index.php/api/createCategory", [
                'catname'        => $name,
                'catdescription' => $desc,
                'userSecretKey'  => $secretKey,
            ]);

            $result = $response->json();
            $categoryCode = null;

            if (is_array($result)) {
                if (isset($result[0]['CategoryCode'])) {
                    $categoryCode = $result[0]['CategoryCode'];
                } elseif (isset($result['CategoryCode'])) {
                    $categoryCode = $result['CategoryCode'];
                }
            }

            if ($categoryCode) {
                $this->info("Successfully created Category '{$name}' with Category Code: {$categoryCode}");
                $this->updateEnvFile('TOYYIBPAY_CATEGORY_CODE', $categoryCode);
                $this->info("Saved TOYYIBPAY_CATEGORY_CODE={$categoryCode} into your .env file.");
                return 0;
            } else {
                $this->error("Failed to create category. Response from ToyyibPay: " . $response->body());
                $this->line("Tip: Make sure you are using the correct mode (Sandbox vs Production). If your secret key is from live toyyibpay.com, set TOYYIBPAY_SANDBOX=false.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error creating category: " . $e->getMessage());
            return 1;
        }
    }

    protected function updateEnvFile(string $key, string $value)
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}\n";
        }

        File::put($envPath, $envContent);
    }
}
