<?php

namespace webhubworks\flare\middleware;

use Spatie\FlareClient\Report;

class RemoveCustomRequestIp
{
    public function handle(Report $report, $next)
    {
        $context = $report->allContext();

        $context['request']['x-forwarded-for'] = null;
        $context['request']['x-real-ip'] = null;
        $context['request']['x-request-ip'] = null;

        $report->userProvidedContext($context);

        return $next($report);
    }
}
