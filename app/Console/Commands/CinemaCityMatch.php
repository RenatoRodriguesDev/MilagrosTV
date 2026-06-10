<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Serie;
use App\Services\CinemaCityService;
use Illuminate\Console\Command;
use ReflectionMethod;

class CinemaCityMatch extends Command
{
    protected $signature = 'cinemacity:match
                            {--type=all : What to match: series, movies, or all}
                            {--dry-run  : Show matches without saving}
                            {--force    : Overwrite existing cinemacity_id values}';

    protected $description = 'Auto-match cinemacity.cc content against local series/movies by title';

    public function handle(CinemaCityService $service): int
    {
        $type   = $this->option('type');
        $dryRun = $this->option('dry-run');
        $force  = $this->option('force');

        $doSeries = in_array($type, ['all', 'series']);
        $doMovies = in_array($type, ['all', 'movies']);

        $this->info('Scraping cinemacity.cc catalog...');

        $catalog = [];

        if ($doSeries) {
            $this->line('  Fetching series pages...');
            $catalog['series'] = $this->scrapeCatalog($service, 'tv-series');
            $this->line('  -> ' . count($catalog['series']) . ' series found on cinemacity.cc');
        }

        if ($doMovies) {
            $this->line('  Fetching movie pages...');
            $catalog['movies'] = $this->scrapeCatalog($service, 'movies');
            $this->line('  -> ' . count($catalog['movies']) . ' movies found on cinemacity.cc');
        }

        $matched = [];

        if ($doSeries && isset($catalog['series'])) {
            $dbItems = Serie::select('id', 'title', 'original_title', 'cinemacity_id')->get();
            $matches = $this->matchItems($catalog['series'], $dbItems, $force);
            $matched['series'] = $matches;
            $this->displayMatches('Series', $matches);
        }

        if ($doMovies && isset($catalog['movies'])) {
            $dbItems = Movie::select('id', 'title', 'original_title', 'cinemacity_id')->get();
            $matches = $this->matchItems($catalog['movies'], $dbItems, $force);
            $matched['movies'] = $matches;
            $this->displayMatches('Movies', $matches);
        }

        $totalNew = array_sum(array_map(fn($m) => count($m), $matched));

        if ($totalNew === 0) {
            $this->info('No new matches found.');
            return 0;
        }

        if ($dryRun) {
            $this->warn("Dry-run: {$totalNew} matches not saved.");
            return 0;
        }

        if ($this->input->isInteractive() && !$this->confirm("Save {$totalNew} matches to the database?", true)) {
            return 0;
        }

        $saved = 0;

        if (isset($matched['series'])) {
            foreach ($matched['series'] as ['db_id' => $dbId, 'cc_id' => $ccId]) {
                Serie::where('id', $dbId)->update(['cinemacity_id' => $ccId]);
                $saved++;
            }
        }

        if (isset($matched['movies'])) {
            foreach ($matched['movies'] as ['db_id' => $dbId, 'cc_id' => $ccId]) {
                Movie::where('id', $dbId)->update(['cinemacity_id' => $ccId]);
                $saved++;
            }
        }

        $this->info("Saved {$saved} matches.");

        return 0;
    }

    private function scrapeCatalog(CinemaCityService $service, string $section): array
    {
        $fetchPage = new ReflectionMethod($service, 'fetchPage');
        $fetchPage->setAccessible(true);

        $items   = [];
        $page    = 1;
        $maxPage = 1;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while ($page <= $maxPage) {
            $url  = $page === 1
                ? "https://cinemacity.cc/{$section}/"
                : "https://cinemacity.cc/{$section}/page/{$page}/";

            $html = $fetchPage->invoke($service, $url);

            if (!$html) {
                $this->newLine();
                $this->warn("  Failed to fetch page {$page}, stopping.");
                break;
            }

            preg_match_all(
                '|href="https://cinemacity\.cc/' . $section . '/([^"]+)\.html"[^>]*>([^<]+)</a>|',
                $html,
                $m
            );

            foreach ($m[1] as $i => $ccId) {
                $rawTitle = html_entity_decode(trim($m[2][$i]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                foreach ($this->titleVariants($rawTitle) as $variant) {
                    $items[$this->normalizeTitle($variant)] = $ccId;
                }
            }

            if ($page === 1) {
                preg_match_all('|/' . $section . '/page/(\d+)/|', $html, $pages);
                if (!empty($pages[1])) {
                    $maxPage = max(array_map('intval', $pages[1]));
                }
                $bar->setMaxSteps($maxPage);
            }

            $bar->advance();
            $page++;

            usleep(300000);
        }

        $bar->finish();
        $this->newLine();

        return $items;
    }

    private function titleVariants(string $raw): array
    {
        $base = preg_replace('/\s*\(\d{4}[^)]*\)\s*$/', '', $raw);
        $base = trim($base);

        $variants = [$base];

        if (str_contains($base, ' / ')) {
            foreach (explode(' / ', $base) as $part) {
                $variants[] = trim($part);
            }
        }

        return array_filter($variants, fn($v) => strlen($v) > 1);
    }

    private function normalizeTitle(string $title): string
    {
        $t = mb_strtolower($title);
        $t = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $t);
        $t = preg_replace('/\s+/', ' ', $t);
        return trim($t);
    }

    private function matchItems(array $catalog, $dbItems, bool $force): array
    {
        $matches = [];

        foreach ($dbItems as $item) {
            if (!$force && $item->cinemacity_id) continue;

            $titlesToTry = array_filter([
                $item->title,
                $item->original_title,
            ]);

            foreach ($titlesToTry as $title) {
                foreach ($this->titleVariants($title) as $variant) {
                    $key = $this->normalizeTitle($variant);
                    if (isset($catalog[$key])) {
                        $matches[] = [
                            'db_id'       => $item->id,
                            'db_title'    => $item->title,
                            'cc_id'       => $catalog[$key],
                            'matched_via' => $variant,
                        ];
                        break 2;
                    }
                }
            }
        }

        return $matches;
    }

    private function displayMatches(string $label, array $matches): void
    {
        if (empty($matches)) {
            $this->line("<fg=gray>{$label}: no matches</>");
            return;
        }

        $this->info("{$label}: " . count($matches) . ' matches');
        $this->table(
            ['DB Title', 'CinemaCity ID', 'Matched via'],
            array_map(fn($m) => [$m['db_title'], $m['cc_id'], $m['matched_via']], $matches)
        );
    }
}
