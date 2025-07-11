<?php

require "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use App\Strings;

$client = HttpClient::create();
$runners = [];
$bests = [
    '50km' => null,
    '50km_seconds' => 900000,
    '50mi' => null,
    '50mi_seconds' => 900000,
    '100km' => null,
    '100km_seconds' => 900000,
    '100mi' => null,
    '100mi_seconds' => 900000,
    '6h' => 0,
    '12h' => 0,
    '24h' => 0,
    '48h' => 0,
];
if ('1' === ($_POST['submit'] ?? false)) {
    if (!empty($_POST['data'])) {
        $lines = explode("\n", $_POST['data']);
        foreach ($lines as $line) {
            $homonyms = [];

            // clean delimiters
            $line = str_replace(["\r", ", ", ","], ["", " ", ""], $line);

            // clean accents
            $a = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ'];
            $b = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o'];
            $line = str_replace($a, $b, $line);

            // place coma
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

                // several results, get the first and extract homonyms
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
                    'bests' => $bests,
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
                    'gender' => '',
                    'bests' => $bests,
                    'homonyms' => [],
                ];
            }
        }
    }
}
$gender = ($_POST['gender'] ?? '');
if (!empty($gender)) {
    $runners = array_values(array_filter($runners, fn ($runner) => $gender === $runner['gender']));
}
$sort = ($_POST['distance'] ?? '100km');
if (!str_contains($sort, 'h')) {
    // sort by time in seconds ASC
    usort($runners, fn($a, $b) => $a['bests'][$sort . '_seconds'] === $b['bests'][$sort . '_seconds'] ? 0 : (($a['bests'][$sort . '_seconds'] < $b['bests'][$sort . '_seconds']) ? -1 : 1));
}
else {
    // sort by distance DESC
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
                    <label for="distance">Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Both</option>
                        <option value="♂️"<?= ('♂️' === $_POST['gender'] ? " selected='selected'" : '')?>>Men</option>
                        <option value="♀️"<?= ('♀️' === $_POST['gender'] ? " selected='selected'" : '')?>>Women</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="distance">Sort by</label>
                    <select name="distance" id="distance">
                        <?php foreach($bests as $key => $value): ?>
                            <?php if (!str_contains($key, '_seconds')): ?>
                                <th class="text-center" scope="col"><?= $key ?></th>
                                <option value="<?= $key ?>"<?= $key === ($_POST['distance'] ?? ('100km' === $key ? '100km': '')) ? " selected='selected'" : ''?>><?= $key ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
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
                            <?php foreach($bests as $key => $value): ?>
                                <?php if (!str_contains($key, '_seconds')): ?>
                                    <th class="text-center" scope="col"><?= $key ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
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
                                <?php foreach($bests as $key => $value): ?>
                                    <?php if (!str_contains($key, '_seconds')): ?>
                                        <td class="text-center"><?= !empty($data['bests'][$key]) ? (!str_contains($data['bests'][$key], ':') ? (number_format((float)$data['bests'][$key], 2) . ' k') : ((!empty($parts = explode(':', $data['bests'][$key])) && 3 === \count($parts)) ? $parts[0] . 'h' . $parts[1] . '\'' . $parts[2] : $data['bests'][$key])) : '-' ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
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
