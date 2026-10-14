<?php
// PDF resumes made from the website content.
//
// Layout follows a standard North American resume, kept ATS-friendly (applicant tracking
// systems read it cleanly): one column, plain section names, real text and links.
//   NAME / Headline / Location · Email · Phone · LinkedIn · Portfolio · GitHub
//   Professional Summary, Technical Skills, Professional Experience, Projects, Education, Certifications
//
// The main resume uses the "Main resume" settings; a custom resume (the "resumes" collection)
// changes only what it fills in. resume.php serves them; PDFs are cached until content changes.

function resume_orders(): array
{
    return [
        'experienced' => 'Experienced: Summary, Skills, Experience, Projects, Education, Certifications',
        'student' => 'Student / new graduate: Summary, Education, Skills, Projects, Experience, Certifications',
    ];
}

function resume_styles(): array
{
    return [
        'standard' => 'Standard (ATS-friendly, North American)',
        'harvard' => 'Harvard (Education first, classic serif, one page)',
    ];
}

// The style being drawn right now; set at the start of resume_html().
function resume_style(?string $style = null): string
{
    static $current = 'standard';
    if ($style !== null) {
        $current = $style === 'harvard' ? 'harvard' : 'standard';
    }
    return $current;
}

function resume_fonts(): array
{
    return ['sans' => 'Modern (Helvetica / Arial style)', 'serif' => 'Classic (Times style)'];
}

/* ---------------------------------------------------------------------------
   Matching a job posting
--------------------------------------------------------------------------- */

// Meaningful words of a job posting: "PHP", "WordPress", "c#", "ui/ux" — without filler words.
function resume_keywords(string $text): array
{
    static $stop = null;
    $stop ??= array_flip(explode(' ', 'a an and are as at be been but by can for from has have how if in into is it its of on or our so such that the their them then there these they this to was we were what when where which who will with you your about across after all also am any able ability apply applicant applicants based benefits best both candidate candidates company daily day degree demonstrated desired do does duties each eg etc ex excellent experience experienced familiarity full good great help high highly ideal including job join just knowledge least level like looking make manage may more most must new not one opportunity other others own part per plus position preferred proficiency proficient project projects provide related required requirements responsibilities role salary should skill skills some strong team teams time title understanding using variety via well work working year years'));
    preg_match_all('/[a-z][a-z0-9+#.\/-]*[a-z0-9+#]|[a-z]/', strtolower($text), $matches);
    $words = [];
    foreach ($matches[0] as $word) {
        if (strlen($word) >= 2 && !isset($stop[$word]) && !ctype_digit($word)) {
            $words[$word] = true;
        }
    }
    return array_keys($words);
}

// The searchable text of a resume entry or a website project.
function resume_row_text(array $row): string
{
    $fields = ['title', 'place', 'text', 'location', 'name', 'type', 'tech', 'description', 'services', 'category', 'heading'];
    return strtolower(implode(' ', array_map(fn($field) => (string) ($row[$field] ?? ''), $fields)));
}

// Which of the keywords appear in the text, as whole words.
function resume_matches(array $keywords, string $text): array
{
    return array_values(array_filter($keywords, fn($word) => preg_match('/(?<![a-z0-9])' . preg_quote($word, '/') . '(?![a-z0-9])/', $text)));
}

// Choices for a "checklist" field: entries of the listed collections, as id => label.
// With $field['keywords'], each label also says which job-posting words it matches.
function checklist_options(array $field, bool $withMatches = true): array
{
    $options = [];
    foreach ($field['source'] ?? [] as $collection => $columns) {
        foreach (items($collection, false) as $row) {
            if (is_array($columns) && !in_array($row['column'] ?? '', $columns, true)) {
                continue;
            }
            $label = $collection === 'projects'
                ? $row['name'] . ' (website project)'
                : trim($row['title'] . ($row['place'] !== '' ? ' — ' . $row['place'] : ''));
            $label .= $row['visible'] ? '' : ' (hidden)';
            if ($withMatches && !empty($field['keywords'])) {
                $found = resume_matches($field['keywords'], resume_row_text($row));
                $label .= $found ? '  ✓ matches: ' . implode(', ', array_slice($found, 0, 6)) : '';
            }
            $options[$row['id']] = $label;
        }
    }
    return $options;
}

// Before a custom resume is saved: tick the entries that match its job posting.
// A list with no matches is left empty, which means "include everything".
function resume_auto_pick(array $data, array $fields): array
{
    if (($data['auto_pick'] ?? '') !== '1' || trim($data['job_posting'] ?? '') === '') {
        return $data;
    }
    $keywords = resume_keywords($data['job_posting']);
    foreach (['experience', 'projects'] as $name) {
        $scores = [];
        foreach ($fields[$name]['source'] as $collection => $columns) {
            foreach (items($collection, false) as $row) {
                if ((is_array($columns) && !in_array($row['column'] ?? '', $columns, true)) || !$row['visible']) {
                    continue;
                }
                $found = count(resume_matches($keywords, resume_row_text($row)));
                if ($found) {
                    $scores[$row['id']] = $found;
                }
            }
        }
        // Projects: only the best matches, up to "Maximum projects" from the main resume.
        if ($name === 'projects') {
            arsort($scores);
            $scores = array_slice($scores, 0, max(1, (int) s('pdf_project_count')), true);
        }
        $data[$name] = implode(',', array_keys($scores));
    }
    return $data;
}

function resume_version(int $id): ?array
{
    foreach (items('resumes', false) as $version) {
        if ($version['id'] === $id) {
            return $version;
        }
    }
    return null;
}

// Everything that decides how one resume looks; a custom version overrides the main settings.
function resume_config(?array $version = null): array
{
    $pick = fn(string $field) => $version && $version[$field] !== '' ? array_map('intval', explode(',', $version[$field])) : null;
    $name = s('pdf_name') ?: s('meta_author') ?: s('site_name');
    $filename = ($version['filename'] ?? '') ?: s('pdf_filename');
    if ($filename === '') {
        $filename = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-') . '-Resume.pdf';
    }
    $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);

    return [
        'key' => $version ? 'v' . $version['id'] : 'main',
        'name' => $name,
        'headline' => ($version['headline'] ?? '') ?: s('pdf_headline'),
        'summary' => ($version['summary'] ?? '') ?: (s('pdf_summary') ?: s('about_text')),
        'skills' => ($version['skills'] ?? '') ?: s('pdf_skills'),
        'style' => ($version['style'] ?? '') ?: s('pdf_style'),
        'order' => ($version['order'] ?? '') ?: s('pdf_order'),
        'font' => ($version['font'] ?? '') ?: s('pdf_font'),
        'paper' => s('pdf_paper') === 'a4' ? 'A4' : 'letter',
        'filename' => str_ends_with(strtolower($filename), '.pdf') ? $filename : $filename . '.pdf',
        'experience' => $pick('experience'),
        'projects' => $pick('projects'),
        'education' => $pick('education'),
        'certifications' => $pick('certifications'),
    ];
}

function resume_filename(): string
{
    return resume_config()['filename'];
}

// "https://www.linkedin.com/in/j-nembang/" => "linkedin.com/in/j-nembang"
function short_url(string $url): string
{
    return rtrim(preg_replace('#^(https?://)?(www\.)?#i', '', $url), '/');
}

function resume_link(string $url, string $text = ''): string
{
    $href = preg_match('#^(https?:|mailto:|tel:)#i', $url) ? $url : 'https://' . $url;
    return '<a href="' . e($href) . '">' . e($text !== '' ? $text : short_url($url)) . '</a>';
}

// Tidies typed text for print without changing what is saved: no space before commas,
// none inside brackets, single spaces, and a capital first letter.
function resume_tidy(string $text): string
{
    $text = preg_replace('/\s+([,.;:!?)])/u', '$1', $text);
    $text = preg_replace('/([(])\s+/u', '$1', $text);
    $text = preg_replace('/(\w)\(/u', '$1 (', $text);
    $text = resume_terms(trim(preg_replace('/\s+/u', ' ', $text)));
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
}

// "Jan 2020 - and so on" => "Jan 2020 – Present"; "Jan 2021 - Dec 2022" => "Jan 2021 – Dec 2022".
// "February 5th, 2019" => "Feb 2019"; "April 2023" => "Apr 2023".
function resume_date(string $date): string
{
    $months = ['jan' => 'Jan', 'feb' => 'Feb', 'mar' => 'Mar', 'apr' => 'Apr', 'may' => 'May', 'jun' => 'Jun',
        'jul' => 'Jul', 'aug' => 'Aug', 'sep' => 'Sep', 'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Dec'];
    $date = preg_replace('/\b(and so on|till now|until now|to date|now|current|ongoing)\b/i', 'Present', $date);
    // Month name, optional day ("5th,"), year => "Mon YYYY".
    $date = preg_replace_callback(
        '/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?(?:\s+\d{1,2}(?:st|nd|rd|th)?,?)?\s+(\d{4})\b/i',
        fn($m) => $months[strtolower($m[1])] . ' ' . $m[2],
        $date
    );
    return trim(preg_replace('/\s*[-–—]+\s*/u', ' – ', trim($date)));
}

// Technology names as their makers write them: "Html Css Js" => "HTML CSS JS".
function resume_terms(string $text): string
{
    static $terms = [
        'html' => 'HTML', 'html5' => 'HTML5', 'css' => 'CSS', 'css3' => 'CSS3', 'js' => 'JS', 'javascript' => 'JavaScript',
        'typescript' => 'TypeScript', 'php' => 'PHP', 'mysql' => 'MySQL', 'sql' => 'SQL', 'wordpress' => 'WordPress',
        'seo' => 'SEO', 'ui' => 'UI', 'ux' => 'UX', 'ui/ux' => 'UI/UX', 'api' => 'API', 'apis' => 'APIs',
        'laravel' => 'Laravel', 'django' => 'Django', 'python' => 'Python', 'react' => 'React', 'reactjs' => 'ReactJS',
        'nodejs' => 'Node.js', 'node.js' => 'Node.js', 'figma' => 'Figma', 'github' => 'GitHub', 'git' => 'Git',
        'flutter' => 'Flutter', 'jquery' => 'jQuery', 'bootstrap' => 'Bootstrap', 'tailwind' => 'Tailwind',
        'webapp' => 'web app', 'xd' => 'XD', 'it' => 'IT', 'csit' => 'CSIT', 'bsc' => 'BSc',
    ];
    return preg_replace_callback('/(?<![\w.\/@-])([a-z0-9]+(?:[.\/][a-z0-9]+)?)(?![\w\/@-]|\.\w)/i', function ($m) use ($terms) {
        $word = strtolower($m[1]);
        // "it" is only the field (IT) when written in capitals already or as "it services"; leave the pronoun alone.
        if ($word === 'it' && $m[1] !== 'IT') {
            return $m[1];
        }
        return $terms[$word] ?? $m[1];
    }, $text);
}

// Lines of a details box as bullet points, without any bullet characters typed in,
// tidied, and without a full stop at the end so every bullet ends the same way.
function resume_bullets(string $text): array
{
    $lines = array_map(fn($line) => rtrim(resume_tidy(preg_replace('/^\s*[•\-*·]\s*/u', '', $line)), '.'), explode("\n", $text));
    return array_values(array_filter($lines, 'strlen'));
}

// One entry: bold title line with the dates on the right, an optional italic second line
// (location, technologies), then bullet points.
function resume_entry(string $heading, array $meta, array $bullets, string $links = '', string $date = ''): string
{
    $html = '<div class="entry"><p class="entry-title">'
        . ($date !== '' ? '<span class="entry-date">' . e(resume_date($date)) . '</span>' : '')
        . $heading . '</p>';
    $meta = array_filter($meta, 'strlen');
    if ($meta) {
        $html .= '<p class="entry-meta">' . implode(' &nbsp;|&nbsp; ', $meta) . '</p>';
    }
    if ($bullets || $links !== '') {
        $html .= '<ul>';
        foreach ($bullets as $bullet) {
            $html .= '<li>' . e($bullet) . '</li>';
        }
        if ($links !== '') {
            $html .= '<li>' . $links . '</li>';
        }
        $html .= '</ul>';
    }
    return $html . '</div>';
}

// Harvard layout: bold organisation with the location on the right,
// then the job title or degree in italics with the dates on the right.
function resume_harvard_entry(string $org, string $location, string $title, string $date, array $bullets, string $links = ''): string
{
    $date = $date !== '' ? resume_date($date) : '';
    // Without a title line, the dates move up beside the organisation.
    $topRight = $location !== '' ? $location : ($title === '' ? $date : '');
    $html = '<div class="entry"><p class="entry-title">' . ($topRight !== '' ? '<span class="entry-date">' . e($topRight) . '</span>' : '')
        . '<strong>' . e($org) . '</strong></p>';
    if ($title !== '' || ($location !== '' && $date !== '')) {
        $html .= '<p class="entry-meta">' . ($date !== '' ? '<span class="entry-date">' . e($date) . '</span>' : '') . e($title) . '</p>';
    }
    if ($bullets || $links !== '') {
        $html .= '<ul>';
        foreach ($bullets as $bullet) {
            $html .= '<li>' . e($bullet) . '</li>';
        }
        if ($links !== '') {
            $html .= '<li>' . $links . '</li>';
        }
        $html .= '</ul>';
    }
    return $html . '</div>';
}

// A resume entry: "Job Title — Company" with the dates on the right, then the location.
// In the Harvard style the organisation leads instead.
function resume_item_html(array $entry): string
{
    $title = $entry['column'] === 'projects' ? preg_replace('/^\s*project\s*:\s*/i', '', $entry['title']) : $entry['title'];
    if (resume_style() === 'harvard') {
        $org = $entry['place'] !== '' ? $entry['place'] : $title;
        $role = $entry['place'] !== '' ? $title : '';
        return resume_harvard_entry(resume_tidy($org), $entry['location'], resume_tidy($role), $entry['date'], resume_bullets($entry['text']), $entry['url'] !== '' ? resume_link($entry['url']) : '');
    }
    $heading = '<strong>' . e(resume_tidy($title)) . '</strong>' . ($entry['place'] !== '' ? ' — ' . e(resume_tidy($entry['place'])) : '');
    $link = $entry['url'] !== '' ? resume_link($entry['url']) : '';
    return resume_entry($heading, [e($entry['location'])], resume_bullets($entry['text']), $link, $entry['date']);
}

// A website project: "Name — Type" with the date on the right, then technologies and client.
function resume_project_html(array $project): string
{
    $heading = '<strong>' . e(resume_tidy($project['name'])) . '</strong>' . ($project['type'] !== '' ? ' — ' . e($project['type']) : '');
    $bullets = resume_bullets($project['description']);
    if ($project['services'] !== '') {
        $bullets[] = 'Also delivered ' . resume_tidy(lcfirst($project['services']));
    }
    $links = array_filter([
        $project['link_url'] !== '' ? 'Live site: ' . resume_link($project['link_url']) : '',
        $project['source_url'] !== '' ? 'Source code: ' . resume_link($project['source_url']) : '',
    ]);
    if (resume_style() === 'harvard') {
        $role = implode(' — ', array_filter([$project['type'], $project['tech'] !== '' ? 'Technologies: ' . $project['tech'] : '']));
        return resume_harvard_entry(resume_tidy($project['name']), '', $role, $project['completion'], $bullets, implode(' &nbsp;·&nbsp; ', $links));
    }
    $meta = [
        $project['tech'] !== '' ? 'Technologies: ' . e(resume_terms($project['tech'])) : '',
        $project['client'] !== '' && strcasecmp($project['client'], $project['name']) !== 0 ? 'Client: ' . e($project['client']) : '',
    ];
    return resume_entry($heading, $meta, $bullets, implode(' &nbsp;·&nbsp; ', $links), $project['completion']);
}

// A certification on one line: "Name — Issuer" with the date on the right.
function resume_certification_html(array $entry): string
{
    $line = ($entry['date'] !== '' ? '<span class="entry-date">' . e(resume_date($entry['date'])) . '</span>' : '')
        . '<strong>' . e(resume_tidy($entry['title'])) . '</strong>' . ($entry['place'] !== '' ? ' — ' . e($entry['place']) : '')
        . ($entry['url'] !== '' ? ' &nbsp;|&nbsp; ' . resume_link($entry['url']) : '');
    return '<p class="cert">' . $line . '</p>';
}

// The latest year in a date text, for ordering: "Present" counts as now, no year as 0.
function resume_latest_year(string $date): int
{
    if (preg_match('/present|now|current|ongoing|and so on/i', $date)) {
        return 9999;
    }
    preg_match_all('/\b(19|20)\d{2}\b/', $date, $years);
    return $years[0] ? max(array_map('intval', $years[0])) : 0;
}

// Keep only the chosen ids (in their content order); null means "no choice made".
// Hidden entries are left out unless a custom resume picks them by hand.
function resume_pick(array $rows, ?array $ids): array
{
    return array_values(array_filter($rows, fn($row) => $ids === null ? ($row['visible'] ?? 1) : in_array($row['id'], $ids, true)));
}

// A section: the heading is kept on the same page as its first block.
function resume_section(string $heading, array|string $blocks): string
{
    $blocks = array_values(array_filter((array) $blocks, 'strlen'));
    if (!$blocks) {
        return '';
    }
    $first = array_shift($blocks);
    return '<div class="section"><div class="keep"><h2>' . e($heading) . '</h2>' . $first . '</div>' . implode('', $blocks) . '</div>';
}

// $compact 0 = normal; 1 and 2 = slightly tighter type and spacing, used to fit one page.
function resume_html(?array $version = null, int $compact = 0): string
{
    $c = resume_config($version);
    $harvard = resume_style($c['style']) === 'harvard';
    $entries = ['left' => [], 'right' => [], 'projects' => [], 'certifications' => []];
    foreach (items('resume', false) as $entry) {
        $entries[$entry['column']][] = $entry;
    }

    // Header: name, headline, contact line.
    $contact = [];
    $location = s('pdf_location') ?: s('contact_location');
    if ($location !== '') {
        $contact[] = e($location);
    }
    if (s('contact_email') !== '') {
        $contact[] = resume_link('mailto:' . s('contact_email'), s('contact_email'));
    }
    if (s('contact_phone') !== '') {
        $contact[] = resume_link('tel:' . (str_starts_with(trim(s('contact_phone')), '+') ? '+' : '') . phone_digits(s('contact_phone')), s('contact_phone'));
    }
    $social = [];
    foreach (items('social') as $link) {
        $social[$link['network']] = $link['url'];
    }
    if (on('pdf_show_links') && !empty($social['linkedin'])) {
        $contact[] = resume_link($social['linkedin']);
    }
    $contact[] = resume_link(s('pdf_portfolio') ?: site_url());
    if (on('pdf_show_links')) {
        foreach (['github', 'behance'] as $network) {
            if (!empty($social[$network])) {
                $contact[] = resume_link($social[$network]);
            }
        }
    }

    // Harvard: the name as written, and no headline.
    $header = '<h1>' . e($harvard ? $c['name'] : mb_strtoupper($c['name'])) . '</h1>';
    if ($c['headline'] !== '' && !$harvard) {
        $header .= '<p class="headline">' . e($c['headline']) . '</p>';
    }
    $header .= '<p class="contact">' . implode(' &nbsp;·&nbsp; ', $contact) . '</p>';

    // Sections.
    $summary = resume_tidy(str_replace('*', '', $c['summary']));
    $sections = [];
    $sections['summary'] = resume_section('Professional Summary', $summary !== '' ? '<p>' . e($summary) . '</p>' : '');

    $skillLines = [];
    $lines = resume_bullets($c['skills']);
    if ($lines) {
        foreach ($lines as $line) {
            $skillLines[] = str_contains($line, ':')
                ? '<strong>' . e(trim(strstr($line, ':', true))) . ':</strong> ' . e(resume_terms(trim(substr(strstr($line, ':'), 1))))
                : e(resume_terms($line));
        }
    } elseif ($titles = array_column(items('skills'), 'title')) {
        $skillLines[] = '<strong>Skills:</strong> ' . e(resume_terms(implode(', ', $titles)));
    }
    if (s('pdf_languages') !== '') {
        $skillLines[] = '<strong>Languages:</strong> ' . e(s('pdf_languages'));
    }
    if ($harvard && s('pdf_interests') !== '') {
        $skillLines[] = '<strong>Interests:</strong> ' . e(s('pdf_interests'));
    }
    $sections['skills'] = resume_section($harvard ? 'Skills & Interests' : 'Technical Skills', $skillLines ? '<p class="skills">' . implode('<br>', $skillLines) . '</p>' : '');

    $sections['experience'] = resume_section($harvard ? 'Experience' : 'Professional Experience', array_map('resume_item_html', resume_pick($entries['right'], $c['experience'])));

    // Projects: resume entries marked "Project", then website projects (home-page ones first).
    $websiteProjects = array_values(projects_by_slug());
    usort($websiteProjects, fn($a, $b) => ($b['featured'] === '1') <=> ($a['featured'] === '1'));
    $pool = array_merge(
        array_map(fn($entry) => ['id' => $entry['id'], 'visible' => $entry['visible'], 'year' => resume_latest_year($entry['date']), 'html' => resume_item_html($entry)], $entries['projects']),
        array_map(fn($project) => ['id' => $project['id'], 'visible' => 1, 'year' => resume_latest_year($project['completion']), 'html' => resume_project_html($project)], $websiteProjects)
    );
    if ($c['projects'] !== null) {
        $pool = resume_pick($pool, $c['projects']);
    } elseif (on('pdf_show_projects')) {
        // The main resume keeps its choice (home-page projects first), up to the maximum.
        $pool = array_slice(resume_pick($pool, null), 0, (int) s('pdf_project_count'));
    } else {
        $pool = [];
    }
    // Shown newest first, as resumes are read; projects without a year go last.
    usort($pool, fn($a, $b) => $b['year'] <=> $a['year']);
    $sections['projects'] = resume_section('Projects', array_column($pool, 'html'));

    $sections['education'] = resume_section('Education', array_map('resume_item_html', resume_pick($entries['left'], $c['education'])));

    $certifications = $c['certifications'] !== null || on('pdf_show_certifications')
        ? resume_pick($entries['certifications'], $c['certifications'])
        : [];
    $sections['certifications'] = resume_section('Certifications', array_map('resume_certification_html', $certifications));

    if ($harvard) {
        // Harvard: Education first, no summary, Skills & Interests last.
        $order = ['education', 'experience', 'projects', 'certifications', 'skills'];
    } elseif ($c['order'] === 'student') {
        $order = ['summary', 'education', 'skills', 'projects', 'experience', 'certifications'];
    } else {
        $order = ['summary', 'skills', 'experience', 'projects', 'education', 'certifications'];
    }
    $body = $header . implode('', array_map(fn($key) => $sections[$key], $order));

    $serif = $harvard || $c['font'] === 'serif';
    $size = ($serif ? 10.5 : 9.6) - 0.3 * $compact;
    $gap = [7, 5.5, 4.5][$compact] ?? 4.5;
    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . e($c['name']) . ' — Resume</title><style>
@page { size: ' . $c['paper'] . '; margin: ' . ['0.55in 0.65in', '0.5in 0.6in', '0.45in 0.55in'][$compact] . '; }
body { margin: 0; color: #111; font-family: ' . ($serif ? '"Times New Roman", Times, serif' : 'Helvetica, Arial, sans-serif') . '; font-size: ' . $size . 'pt; line-height: ' . ($compact ? '1.26' : '1.3') . '; }
a { color: #111; text-decoration: none; }
p { margin: 0; }
h1 { margin: 0; font-size: 20pt; letter-spacing: ' . ($harvard ? '0.3pt' : '1.2pt') . '; text-align: center; }
.entry-meta .entry-date { font-style: normal; }
.headline { margin-top: 3pt; font-size: ' . ($serif ? '11pt' : '10.2pt') . '; text-align: center; }
.contact { margin-top: 4pt; font-size: ' . ($serif ? '9.5pt' : '8.8pt') . '; text-align: center; }
.section { margin-top: ' . ($gap + 3) . 'pt; }
.keep { page-break-inside: avoid; }
h2 { margin: 0 0 5pt; padding-bottom: 2pt; border-bottom: 0.8pt solid #111; font-size: ' . ($serif ? '11pt' : '10.2pt') . '; letter-spacing: 0.9pt; text-transform: uppercase; page-break-after: avoid; }
.entry { margin-bottom: ' . $gap . 'pt; page-break-inside: avoid; }
.entry-title { overflow: hidden; }
.entry-date { float: right; padding-left: 12pt; font-weight: normal; }
.entry-meta { margin-top: 0.5pt; color: #333; font-style: italic; }
.cert { margin-bottom: 3pt; overflow: hidden; }
.skills strong { font-weight: bold; }
ul { margin: 2pt 0 0; padding-left: 13pt; }
li { margin: 0 0 1pt; }
</style></head><body>' . $body . '</body></html>';
}

// Path of the PDF for the current content, made only when the content has changed.
function resume_pdf_file(?array $version = null): string
{
    $c = resume_config($version);
    $html = resume_html($version);
    $cacheDir = SITE_ROOT . '/uploads/cache';
    $fit = on('pdf_fit_page');
    $file = $cacheDir . '/resume-' . $c['key'] . '-' . substr(md5($html . ($fit ? '|fit' : '')), 0, 12) . '.pdf';
    if (is_file($file)) {
        return $file;
    }

    require_once SITE_ROOT . '/vendor/autoload.php';
    if (!is_dir($cacheDir . '/fonts')) {
        @mkdir($cacheDir . '/fonts', 0755, true);
    }
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('chroot', SITE_ROOT);
    $options->set('fontCache', $cacheDir . '/fonts');
    $options->set('tempDir', sys_get_temp_dir());

    // Normal layout first; if it runs onto a second page, try the two tighter layouts.
    $render = function (string $html) use ($options, $c): Dompdf\Dompdf {
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($c['paper']);
        $dompdf->render();
        return $dompdf;
    };
    $dompdf = $render($html);
    if ($fit && $dompdf->getCanvas()->get_page_count() > 1) {
        foreach ([1, 2] as $level) {
            $tighter = $render(resume_html($version, $level));
            if ($tighter->getCanvas()->get_page_count() === 1) {
                $dompdf = $tighter;
                break;
            }
        }
    }

    // Document properties, read by some applicant tracking systems.
    $skillText = $c['skills'] !== '' ? $c['skills'] : implode(', ', array_column(items('skills'), 'title'));
    $dompdf->addInfo('Title', $c['name'] . ' - Resume');
    $dompdf->addInfo('Author', $c['name']);
    $dompdf->addInfo('Subject', $c['headline'] !== '' ? $c['headline'] : 'Resume');
    $dompdf->addInfo('Keywords', resume_terms(str_replace("\n", ', ', preg_replace('/^[^:\n]*:\s*/m', '', $skillText))));
    $dompdf->addInfo('Creator', 'Portfolio resume builder');

    // Remove this resume's PDFs made from older content.
    foreach (glob($cacheDir . '/resume-' . $c['key'] . '-*.pdf') ?: [] as $old) {
        @unlink($old);
    }
    file_put_contents($file . '.tmp', $dompdf->output());
    rename($file . '.tmp', $file);
    return $file;
}

// Where the "Download Resume" buttons point.
function cv_url(): string
{
    return s('pdf_source') === 'file' ? s('resume_cv') : 'resume.php';
}

function cv_filename(): string
{
    return s('pdf_source') === 'file' ? s('resume_cv_filename') : resume_filename();
}
