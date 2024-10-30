<?php

namespace webhubworks\flare\middleware;

use Spatie\FlareClient\Report;

class RemoveAllRequestIp
{
    public function handle(Report $report, $next)
    {
        $context = $report->allContext();

        $context['request']['x-forwarded-for'] = null;
        $context['request']['x-real-ip'] = null;
        $context['request']['x-request-ip'] = null;
        $context['request']['x-client-ip'] = null;
        $context['request']['cf-connecting-ip'] = null;
        $context['request']['fastly-client-ip'] = null;
        $context['request']['true-client-ip'] = null;
        $context['request']['forwarded'] = null;
        $context['request']['proxy-client-ip'] = null;
        $context['request']['wl-proxy-client-ip'] = null;

        $report->userProvidedContext($context);

        return $next($report);
    }
}
