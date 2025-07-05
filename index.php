<?php

require "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use App\Strings;

$client = HttpClient::create();
$runners = [];
if ('1' === ($_POST['submit'] ?? false)) {
    if (!empty($_POST['data'])) {
        $lines = explode("\n", $_POST['data']);
        foreach ($lines as $line) {
            $homonyms = [];
            $line = str_replace(["\r", ", ", ","], ["", " ", ""], $line);
            if (str_contains($line, "\t") && !empty($infos = explode("\t", $line)) && 1 < \count($infos)) {
                $line = $infos[0] . ' ' . $infos[1];
            }
            $line = Strings::substrBeforeLastDelimiter($line, " ") . ', ' . Strings::substrAfterLastDelimiter($line, " ") . ' ';
            if (empty($line)) {
                continue;
            }
            try {
                $response = $client->request('GET', sprintf('https://statistik.d-u-v.org/searchrunner.php?sname=%s&Submit.x=22&Submit.y=7', $line));
                $html = $response->getContent();

                // no result, try to reverse firstname & lastname
                if (str_contains($html, '0 search results')) {
                    $response = $client->request('GET', sprintf('https://statistik.d-u-v.org/searchrunner.php?sname=%s&Submit.x=22&Submit.y=7', join(', ', array_reverse(explode(', ', $line)))));
                    $html = $response->getContent();
                }

                // several results
                if (str_contains($html, 'Searching the Name field for')) {
                    $domCrawler = new Crawler('<html><body><h2>' . Strings::substrAfterFirstDelimiter($html, '<h2>Searching the Name field for:'));
                    $id = null;
                    $domCrawler->filterXPath('//table/tr[position() > 1]')->each(function (Crawler $node, int $index) use ($client, &$id, &$homonyms) {
                        if (0 === $index) {
                            $id = Strings::substrAfterLastDelimiter($node->filterXPath('//td[2]/a/@href')->text(), '=');
                        }
                        else {
                            $homonyms[] = Strings::substrAfterLastDelimiter($node->filterXPath('//td[2]/a/@href')->text(), '=');
                        }
                    });
                    $response = $client->request('GET', sprintf('https://statistik.d-u-v.org/getresultperson.php?runner=%s', $id));
                    $html = $response->getContent();
                }

                $domCrawler = new Crawler($html);
                $runner = [
                    'id' => trim($domCrawler->filterXPath('//table[3]/tr[7]/td[2]')->text()),
                    'name' => trim($domCrawler->filterXPath('//table[3]/tr[1]/td[2]')->text()),
                    'club' => trim($domCrawler->filterXPath('//table[3]/tr[2]/td[2]')->text()),
                    'birth' => trim($domCrawler->filterXPath('//table[3]/tr[4]/td[2]')->text()),
                    'categ' => trim($categ = str_contains($categ = $domCrawler->filterXPath('//table[3]/tr[4]/td[3]')->text(), ':') ? trim(Strings::substrBeforeFirstDelimiter(Strings::substrAfterLastDelimiter($categ, ':'), ')')) : $categ),
                    'gender' => str_contains($categ, 'F') || str_contains($categ, 'W') ? '♀️' : '♂️',
                    'country' => trim(Strings::substrBeforeFirstDelimiter($domCrawler->filterXPath('//table[3]/tr[6]/td[2]')->text(), ' ')),
                    'bests' => [
                        '50km' => null,
                        '50km_seconds' => 86400,
                        '100km' => null,
                        '100km_seconds' => 86400,
                        '6h' => 0,
                        '12h' => 0,
                        '24h' => 0,
                    ],
                    'homonyms' => $homonyms,
                ];

                $domCrawler->filterXPath('//table[6]/tr[position() > 1]')->each(function (Crawler $node) use (&$runner) {
                    if (array_key_exists($key = $node->filterXPath('//th[1]')->text(), $runner['bests'])) {
                        $runner['bests'][$key] = $node->filterXPath('//td[1]')->text();
                        if (str_contains($runner['bests'][$key], ':')) {
                            $runner['bests'][$key . '_seconds'] = new \DateTime(date('Y-m-d') . ' ' . $runner['bests'][$key])->getTimestamp() - new \DateTime(date('Y-m-d') . ' 00:00:00')->getTimestamp();
                        }
                    }
                });
                if (null === $runner) {
                    throw new \Exception('Runner not found');
                }
                $runners[] = $runner;
            }
            catch (\Exception $e) {
                $line = preg_replace('/\s/', ' ', $line);
                $runners[] = [
                    'name' => strtoupper($line),
                    'bests' => [
                        '50km' => null,
                        '50km_seconds' => 86400,
                        '100km' => null,
                        '100km_seconds' => 86400,
                        '6h' => 0,
                        '12h' => 0,
                        '24h' => 0,
                    ],
                    'homonyms' => [],
                ];
            }
        }
    }
}
$sort = ($_POST['distance'] ?? '100km');
if (str_contains($sort, 'km')) {
    usort($runners, fn($a, $b) => $a['bests'][$sort . '_seconds'] === $b['bests'][$sort . '_seconds'] ? 0 : (($a['bests'][$sort . '_seconds'] < $b['bests'][$sort . '_seconds']) ? -1 : 1));
}
else {
    usort($runners, fn($a, $b) => $a['bests'][$sort] === $b['bests'][$sort] ? 0 : (($a['bests'][$sort] < $b['bests'][$sort]) ? 1 : -1));
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DUV Ranking</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
</head>

<body>

    <div class="container">
        <h1>DUV Ranking</h1>
        <hr />

        <div class="row">
            <form method="POST" action="">
                <input type="hidden" name="submit" value="1" />
                <div class="form-group">
                    <label for="data">List of runners</label>
                    <textarea class="form-control" name="data" id="data" rows="20" placeholder="One per line"><?= $_POST['data'] ?? ''?></textarea>
                </div>
                <div class="form-group">
                    <label for="distance">Sort by</label>
                    <select name="distance" id="distance">
                        <option value="50km"<?= '50k' === ($_POST['distance'] ?? '') ? " selected='selected'" : ''?>>50k</option>
                        <option value="100km"<?= '100km' === ($_POST['distance'] ?? '100km') ? " selected='selected'" : ''?>>100k</option>
                        <option value="6h"<?= '6h' === ($_POST['distance'] ?? '') ? " selected='selected'" : ''?>>6H</option>
                        <option value="12h"<?= '12h' === ($_POST['distance'] ?? '') ? " selected='selected'" : ''?>>12H</option>
                        <option value="24h"<?= '24h' === ($_POST['distance'] ?? '') ? " selected='selected'" : ''?>>24H</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>

        <?php if (!empty($runners)): ?>
            <div class="row">
                <div class="table-responsive">
                    <table class="table table-hover table-striped caption-top">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" scope="col">Name</th>
                            <th class="text-center" scope="col">Gender</th>
                            <th class="text-center" scope="col">Birthdate</th>
                            <th class="text-center" scope="col">Category</th>
                            <th class="text-center" scope="col">Country</th>
                            <th class="text-center" scope="col">Club</th>
                            <th class="text-center" scope="col">50k</th>
                            <th class="text-center" scope="col">100k</th>
                            <th class="text-center" scope="col">6H</th>
                            <th class="text-center" scope="col">12H</th>
                            <th class="text-center" scope="col">24H</th>
                            <th class="text-center" scope="col">Homonyms</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($runners as $data): ?>
                            <tr>
                                <td class="text-center"><?= !empty($data['id']) ? '<a href="https://statistik.d-u-v.org/getresultperson.php?runner=' . $data['id'] . '" target="_blank">' . $data['name'] . '</a>' : $data['name'] ?></td>
                                <td class="text-center"><?= $data['gender'] ?? '-' ?></td>
                                <td class="text-center"><?= $data['birth'] ?? '-' ?></td>
                                <td class="text-center"><?= $data['categ'] ?? '-' ?></td>
                                <td class="text-center"><?= $data['country'] ?? '-' ?></td>
                                <td class="text-center"><?= $data['club'] ?? '-' ?></td>
                                <td class="text-center"><?= $data['bests']['50km'] ?? '-' ?></td>
                                <td class="text-center"><?= $data['bests']['100km'] ?? '-' ?></td>
                                <td class="text-center"><?= !empty($data['bests']['6h']) ? $data['bests']['6h'] . 'k' : '-' ?></td>
                                <td class="text-center"><?= !empty($data['bests']['12h']) ? $data['bests']['12h'] . 'k' : '-' ?></td>
                                <td class="text-center"><?= !empty($data['bests']['24h']) ? $data['bests']['24h'] . 'k' : '-' ?></td>
                                <td class="text-center">
                                    <?php foreach ($data['homonyms'] as $index => $homonym): ?>
                                        <?= '<a href="https://statistik.d-u-v.org/getresultperson.php?runner=' . $homonym . '" target="_blank">' . ((int)$index + 1) . '</a>' ?>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
