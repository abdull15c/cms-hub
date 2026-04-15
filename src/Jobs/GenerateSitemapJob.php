<?php
namespace Src\Jobs;
use Src\Services\RobotsGenerator;
use Src\Services\SitemapGenerator;

class GenerateSitemapJob {
    public function handle($data) {
        (new SitemapGenerator())->generate();
        (new RobotsGenerator())->generate();
        echo "[JOB] Sitemap and robots regenerated.\n";
    }
}
