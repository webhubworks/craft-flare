<?php

namespace webhubworks\flare\middleware;

use Spatie\FlareClient\Report;

class CensorQueriesMiddleware
{
    public function handle(Report $report, $next)
    {
        $context = $report->allContext();

        if(isset($context['exception']['raw_sql'])){
            $report->message($this->extractSqlExceptionMessage($report->getMessage()));
            $context['exception']['raw_sql'] = "<CENSORED>";
        }

        $report->userProvidedContext($context);

        return $next($report);
    }

    private function extractSqlExceptionMessage(string $fullExceptionMessage): ?string
    {
        preg_match('/^SQLSTATE\[.*?\]: .*?:(.*?)(?= \(Connection:)/', $fullExceptionMessage, $matches);

        return $matches[1] ?? null;
    }
}
