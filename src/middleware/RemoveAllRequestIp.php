<?php

namespace webhubworks\flare\middleware;

use Spatie\FlareClient\Report;

class RemoveAllRequestIp
{
    public function handle(Report $report, $next)
    {
        $context = $report->allContext();

        $context['headers']['x-forwarded-for'] = '<CENSORED>';
        $context['headers']['x-real-ip'] = '<CENSORED>';
        $context['headers']['x-request-ip'] = '<CENSORED>';
        $context['headers']['x-client-ip'] = '<CENSORED>';
        $context['headers']['cf-connecting-ip'] = '<CENSORED>';
        $context['headers']['fastly-client-ip'] = '<CENSORED>';
        $context['headers']['true-client-ip'] = '<CENSORED>';
        $context['headers']['forwarded'] = '<CENSORED>';
        $context['headers']['proxy-client-ip'] = '<CENSORED>';
        $context['headers']['wl-proxy-client-ip'] = '<CENSORED>';

        $report->userProvidedContext($context);

        return $next($report);
    }
}
