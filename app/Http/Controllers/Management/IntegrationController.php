<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Services\Payments\WompiSandbox;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function __invoke(WompiSandbox $wompi): Response
    {
        return Inertia::render('management/Integrations', [
            'communications' => ['mailEnabled' => (bool) config('communications.operational_mail_enabled'), 'mailer' => config('mail.default'), 'queue' => config('queue.default')],
            'wompi' => ['ready' => $wompi->ready(), 'enabled' => (bool) config('integrations.wompi.enabled'), 'missing' => $wompi->missingConfiguration(), 'webhook' => route('webhooks.wompi')],
            'travelProviders' => [
                ['name' => 'Netactica Colombia', 'baseUrlConfigured' => filled(config('integrations.netactica.base_url')), 'documentation' => 'https://www.netactica.co/'],
                ['name' => 'Avianca Link', 'baseUrlConfigured' => filled(config('integrations.avianca_link.base_url')), 'documentation' => 'https://www.avianca.com/'],
            ],
        ]);
    }
}
