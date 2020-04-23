<?php

namespace App\Tiles\Statistics\Commands;

use Illuminate\Console\Command;
use App\Services\GitHub\GitHubApi;
use Illuminate\Support\Collection;
use App\Tiles\Statistics\StatisticsStore;

class FetchGitHubTotalsCommand extends Command
{
    protected $signature = 'dashboard:fetch-github-totals';

    protected $description = 'Fetch GitHub totals';

    public function handle(GitHubApi $gitHub)
    {
        $this->info('Fetching GitHub totals');

        $userName = config('services.github.username');

        $totals = $gitHub
            ->fetchPublicRepositories($userName)
            ->pipe(function (Collection $repos) use ($gitHub, $userName) {
                return [
                    'stars' => $repos->sum('stargazers_count'),
                    'issues' => $repos->sum('open_issues'),
                    'pullRequests' => $repos->sum(function ($repo) use ($gitHub, $userName) {
                        return count($gitHub->fetchPullRequests($userName, $repo['name']));
                    }),
                    'contributors' => $repos->sum(function ($repo) use ($gitHub, $userName) {
                        return count($gitHub->fetchContributors($userName, $repo['name']));
                    }),
                ];
            });

        StatisticsStore::make()->setGitHubTotals($totals);

        $this->info('All done!');
    }
}
