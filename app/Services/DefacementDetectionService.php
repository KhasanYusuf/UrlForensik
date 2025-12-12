<?php

namespace App\Services;

use App\Models\MonitoredSite;
use App\Models\Kasus;
use App\Models\BuktiDigital;
use App\Models\ActivityLog;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class DefacementDetectionService
{
    protected Client $client;

    // Smart defaults for static selectors (layout elements that rarely change)
    private const DEFAULT_STATIC_SELECTORS = 'nav, footer, header, .navbar, .main-header, .site-footer';

    // Auto-discovery priority order for dynamic content zones
    private const DEFAULT_DYNAMIC_SELECTORS = ['article', 'main', '#content', '#main', '.post-content', 'body'];

    // Dangerous tags that should not appear in dynamic zones
    private const DANGEROUS_TAGS = ['script', 'iframe', 'object', 'embed', 'applet'];

    // Bad keywords (Indonesian context)
    private const BAD_KEYWORDS = [
        'hacked by', 'defaced by', 'owned by', 'pwned by', 'rooted by',
        'slot gacor', 'rtp live', 'judi online', 'poker', 'togel',
        'your site has been hacked', 'indonesian cyber army'
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 15,
            'verify' => false,
            'http_errors' => false
        ]);
    }

    /**
     * Normalize HTML by removing dynamic elements that change frequently
     * to avoid false positives from CSRF tokens, session IDs, timestamps, etc.
     */
    private function normalizeHtml(string $html): string
    {
        // Remove CSRF tokens (common patterns)
        $html = preg_replace('/<input[^>]*name=["\'](_token|csrf_token|authenticity_token)[^>]*>/i', '', $html);
        $html = preg_replace('/<meta[^>]*name=["\']csrf-token[^>]*>/i', '', $html);

        // Remove session/nonce attributes
        $html = preg_replace('/\s+data-nonce=["\'][^"\'>]*["\']/', '', $html);
        $html = preg_replace('/\s+data-session-id=["\'][^"\'>]*["\']/', '', $html);

        // Remove timestamp/cache busting parameters from URLs
        $html = preg_replace('/([?&])_=[0-9]+/', '', $html);
        $html = preg_replace('/([?&])v=[0-9]+/', '', $html);
        $html = preg_replace('/([?&])timestamp=[0-9]+/', '', $html);

        // Normalize whitespace (multiple spaces/newlines to single space)
        $html = preg_replace('/\s+/', ' ', $html);

        // Remove HTML comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\[if).*?-->/', '', $html);

        return trim($html);
    }

    /**
     * Normalize HTML for DYNAMIC content zones - remove actual content, keep structure only
     * This prevents false positives when legitimate content changes (e.g., new posts, announcements)
     */
    private function normalizeDynamicHtml(string $html): string
    {
        // First apply standard normalization
        $html = $this->normalizeHtml($html);

        // Remove actual text content but keep HTML structure
        // Remove content inside common text containers while preserving tags
        $html = preg_replace('/<(p|h1|h2|h3|h4|h5|h6|span|div|li|td|th|a)[^>]*>.*?<\/\1>/is', '<$1></$1>', $html);

        // Remove dates and timestamps (common in announcements/posts)
        $html = preg_replace('/\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}/', '', $html);
        $html = preg_replace('/\d{4}[\/-]\d{1,2}[\/-]\d{1,2}/', '', $html);
        $html = preg_replace('/\d{1,2}:\d{2}(:\d{2})?/', '', $html);

        // Remove numbers (IDs, counts, etc.)
        $html = preg_replace('/\b\d+\b/', '', $html);

        // Normalize whitespace again after content removal
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }

    /**
     * Check if content appears to be legitimate user-generated content
     * (as opposed to malicious injection)
     */
    private function isLegitimateContent(string $html): bool
    {
        // Decode HTML entities for analysis
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $text = trim($text);

        // Empty content is safe
        if (empty($text)) {
            return true;
        }

        // Check for bad keywords (defacement signatures)
        foreach (self::BAD_KEYWORDS as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return false; // Contains defacement keyword
            }
        }

        // Check for excessive special characters (often indicates obfuscation)
        $specialCharCount = preg_match_all('/[^a-zA-Z0-9\s\.,!?\-_()]/', $text);
        $totalLength = strlen($text);
        if ($totalLength > 0 && ($specialCharCount / $totalLength) > 0.3) {
            return false; // More than 30% special characters
        }

        // Check for base64-like patterns (often used in malware)
        if (preg_match('/[A-Za-z0-9+\/]{50,}={0,2}/', $text)) {
            return false;
        }

        // If it contains reasonable text content, consider it legitimate
        // Check for common words, sentences, etc.
        $wordCount = str_word_count($text);
        if ($wordCount > 3) {
            return true; // Has multiple words, likely legitimate content
        }

        // Short content without red flags is also legitimate
        return true;
    }

    /**
     * Extract all external domains from HTML content
     * Returns array of unique domain names from script, iframe, link, img tags
     * AND from inline script content (window.location, etc.)
     */
    private function extractDomainsFromHtml(\DOMDocument $dom, string $siteUrl): array
    {
        $domains = [];
        $xpath = new \DOMXPath($dom);
        $siteHost = strtolower(parse_url($siteUrl, PHP_URL_HOST) ?? '');

        // Extract from script tags (src attribute)
        $scriptNodes = $xpath->query('//script[@src]');
        foreach ($scriptNodes as $node) {
            $src = $node->getAttribute('src');
            if ($src) {
                $host = parse_url($src, PHP_URL_HOST);
                if ($host && strtolower($host) !== $siteHost) {
                    $domains[] = strtolower($host);
                }
            }
        }

        // Extract from INLINE script content (NEW!)
        // Detect URLs in script like: window.location.href = "http://evil.com"
        $allScriptNodes = $xpath->query('//script');
        foreach ($allScriptNodes as $node) {
            $scriptContent = $node->textContent;
            if (!empty($scriptContent)) {
                // Pattern to match URLs in JavaScript code
                // Matches: "http://domain.com", 'http://domain.com', `http://domain.com`
                // Also: window.location.href = "...", document.location = "...", etc.
                $urlPatterns = [
                    // Match quoted URLs (single, double, backtick quotes)
                    '/["\'\`](https?:\/\/[^"\'\`\s]+)["\'\`]/i',
                    // Match window.location.href/replace/assign patterns
                    '/(?:window|document)\.location(?:\.[a-z]+)?\s*=\s*["\'\`](https?:\/\/[^"\'\`]+)["\'\`]/i',
                    // Match window.open patterns
                    '/window\.open\s*\(\s*["\'\`](https?:\/\/[^"\'\`]+)["\'\`]/i',
                ];

                foreach ($urlPatterns as $pattern) {
                    if (preg_match_all($pattern, $scriptContent, $matches)) {
                        foreach ($matches[1] as $url) {
                            $host = parse_url($url, PHP_URL_HOST);
                            if ($host && strtolower($host) !== $siteHost) {
                                $domains[] = strtolower($host);
                            }
                        }
                    }
                }
            }
        }

        // Extract from iframe tags
        $iframeNodes = $xpath->query('//iframe[@src]');
        foreach ($iframeNodes as $node) {
            $src = $node->getAttribute('src');
            if ($src) {
                $host = parse_url($src, PHP_URL_HOST);
                if ($host && strtolower($host) !== $siteHost) {
                    $domains[] = strtolower($host);
                }
            }
        }

        // Extract from link tags (CSS, fonts, etc.)
        $linkNodes = $xpath->query('//link[@href]');
        foreach ($linkNodes as $node) {
            $href = $node->getAttribute('href');
            if ($href) {
                $host = parse_url($href, PHP_URL_HOST);
                if ($host && strtolower($host) !== $siteHost) {
                    $domains[] = strtolower($host);
                }
            }
        }

        // Extract from img tags
        $imgNodes = $xpath->query('//img[@src]');
        foreach ($imgNodes as $node) {
            $src = $node->getAttribute('src');
            if ($src) {
                $host = parse_url($src, PHP_URL_HOST);
                if ($host && strtolower($host) !== $siteHost) {
                    $domains[] = strtolower($host);
                }
            }
        }

        // Extract from anchor tags with suspicious onclick/href
        $anchorNodes = $xpath->query('//a[@onclick or @href]');
        foreach ($anchorNodes as $node) {
            // Check onclick attribute for URLs
            $onclick = $node->getAttribute('onclick');
            if ($onclick) {
                if (preg_match_all('/["\'\`](https?:\/\/[^"\'\`\s]+)["\'\`]/i', $onclick, $matches)) {
                    foreach ($matches[1] as $url) {
                        $host = parse_url($url, PHP_URL_HOST);
                        if ($host && strtolower($host) !== $siteHost) {
                            $domains[] = strtolower($host);
                        }
                    }
                }
            }
        }

        return array_values(array_unique($domains));
    }

    /**
     * Check for unauthorized domains (domains not in allowed_domains list)
     * Returns array of unauthorized domains found
     */
    private function checkUnauthorizedDomains(\DOMDocument $dom, MonitoredSite $site): array
    {
        // Get allowed domains from site
        $allowedDomains = [];
        if (!empty($site->allowed_domains)) {
            if (is_array($site->allowed_domains)) {
                $allowedDomains = $site->allowed_domains;
            } else {
                $decoded = json_decode($site->allowed_domains, true);
                if (is_array($decoded)) {
                    $allowedDomains = $decoded;
                }
            }
        }
        $allowedDomains = array_map('strtolower', $allowedDomains);

        // Extract current domains from HTML
        $currentDomains = $this->extractDomainsFromHtml($dom, $site->site_url);

        // Find unauthorized domains (present in current but not in allowed)
        $unauthorizedDomains = [];
        foreach ($currentDomains as $domain) {
            $isAllowed = false;

            // Check exact match
            if (in_array($domain, $allowedDomains)) {
                $isAllowed = true;
            }

            // Check subdomain match (e.g., sub.example.com matches example.com)
            if (!$isAllowed) {
                foreach ($allowedDomains as $allowed) {
                    if (str_ends_with($domain, '.' . $allowed)) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if (!$isAllowed) {
                $unauthorizedDomains[] = $domain;
            }
        }

        return $unauthorizedDomains;
    }

    /**
     * Detect dangerous injections (scripts, iframes with suspicious sources)
     * Returns array of detected threats
     */
    private function detectDangerousInjections(\DOMDocument $dom): array
    {
        $threats = [];
        $xpath = new \DOMXPath($dom);

        // Detect inline scripts with suspicious patterns
        $scriptNodes = $xpath->query('//script');
        foreach ($scriptNodes as $scriptNode) {
            $scriptContent = strtolower($scriptNode->textContent);
            $src = $scriptNode->getAttribute('src');

            // Check for suspicious script sources (not from same domain)
            if ($src && !empty($src)) {
                $host = parse_url($src, PHP_URL_HOST);
                // Skip if it's a known CDN or same domain
                $safeCDNs = ['cdnjs.cloudflare.com', 'cdn.jsdelivr.net', 'ajax.googleapis.com', 'code.jquery.com'];
                $isSafeCDN = false;
                foreach ($safeCDNs as $cdn) {
                    if (stripos($host, $cdn) !== false) {
                        $isSafeCDN = true;
                        break;
                    }
                }

                if (!$isSafeCDN && $host) {
                    $threats[] = "Suspicious external script: {$src}";
                }
            }

            // Detect malicious script patterns
            $maliciousPatterns = [
                'eval(',
                'base64_decode(',
                'atob(',
                'document.write(',
                'fromcharcode',
                'window.location.replace',
            ];

            foreach ($maliciousPatterns as $pattern) {
                if (stripos($scriptContent, $pattern) !== false) {
                    $threats[] = "Malicious script pattern detected: {$pattern}";
                    break;
                }
            }
        }

        // Detect suspicious iframes (not YouTube, Vimeo, Maps, etc.)
        $iframeNodes = $xpath->query('//iframe');
        foreach ($iframeNodes as $iframeNode) {
            $src = $iframeNode->getAttribute('src');
            if ($src) {
                $host = parse_url($src, PHP_URL_HOST);
                $safeIframeSources = ['youtube.com', 'youtu.be', 'vimeo.com', 'google.com', 'maps.google.com', 'facebook.com'];
                $isSafe = false;
                foreach ($safeIframeSources as $safe) {
                    if (stripos($host, $safe) !== false) {
                        $isSafe = true;
                        break;
                    }
                }

                if (!$isSafe && $host) {
                    $threats[] = "Suspicious iframe source: {$src}";
                }
            }
        }

        // Detect object/embed tags (rarely used legitimately)
        foreach (['object', 'embed', 'applet'] as $tag) {
            $nodes = $xpath->query('//' . $tag);
            if ($nodes->length > 0) {
                $threats[] = "Dangerous tag detected: <{$tag}>";
            }
        }

        return $threats;
    }

    /**
     * Check a single monitored site for defacement using DOM-based content-aware detection.
     * Smart auto-discovery when selectors are NULL.
     *
     * @param MonitoredSite $site
     * @return void
     */
    public function checkSite(MonitoredSite $site): void
    {
        try {
            $response = $this->client->get($site->site_url);
            $body = (string) $response->getBody();

            // If there is no baseline file yet, initialize it from the current body and mark UP
            if (empty($site->baseline_file_path) || ! Storage::disk('public')->exists($site->baseline_file_path)) {
                $baselinePath = "baselines/{$site->id_site}_baseline.html";
                Storage::disk('public')->put($baselinePath, $body);
                $site->baseline_file_path = $baselinePath;
                $site->baseline_hash = hash('sha256', $body);
                $site->last_checked_at = now();
                $site->status = 'UP';
                $site->save();
                Log::info("Initialized baseline for site {$site->id_site}");
                return;
            }

            // Load baseline content
            $baselineContent = Storage::disk('public')->get($site->baseline_file_path);

            // Helpers for DOM parsing
            $domFor = function (string $html) {
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                return $dom;
            };

            $getNodesHtml = function (\DOMDocument $dom, array $selectors) {
                $xpath = new \DOMXPath($dom);
                $htmlParts = [];
                foreach ($selectors as $sel) {
                    $sel = trim($sel);
                    if ($sel === '') continue;
                    // Support simple selectors: tag, .class, #id
                    if (strpos($sel, '#') === 0) {
                        $id = substr($sel, 1);
                        $nodes = $xpath->query("//*[@id='" . addslashes($id) . "']");
                    } elseif (strpos($sel, '.') === 0) {
                        $class = substr($sel, 1);
                        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' " . addslashes($class) . " ')]");
                    } else {
                        // tag name
                        $nodes = $xpath->query('//' . $sel);
                    }

                    if ($nodes instanceof \DOMNodeList) {
                        foreach ($nodes as $node) {
                            $inner = '';
                            foreach ($node->childNodes as $child) {
                                $inner .= $dom->saveHTML($child);
                            }
                            $htmlParts[] = trim($inner);
                        }
                    }
                }
                return implode("\n", $htmlParts);
            };

            // Parse baseline and current DOMs
            $domBaseline = $domFor($baselineContent);
            $domCurrent = $domFor($body);

            // --- Step 0: Unauthorized Domain Detection (Critical Security Check) ---
            // Check if current HTML contains domains not in allowed_domains list
            $unauthorizedDomains = $this->checkUnauthorizedDomains($domCurrent, $site);

            if (!empty($unauthorizedDomains)) {
                $site->status = 'DEFACED';
                $site->save();

                $kasus = Kasus::create([
                    'id_site' => $site->id_site,
                    'jenis_kasus' => 'Unauthorized Domain Injection',
                    'tanggal_kejadian' => now(),
                    'deskripsi_kasus' => 'Automatic detection: Unauthorized external domain(s) detected: ' . implode(', ', $unauthorizedDomains) . ' (not in allowed_domains whitelist)',
                    'status_kasus' => 'Open',
                    'detection_source' => 'System Monitoring',
                    'impact_level' => 'High',
                ]);

                // Save evidence
                $timestamp = now()->format('Ymd_His');
                $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                $htmlPath = $basePath . "unauthorized_domain_{$timestamp}.html";
                Storage::disk('public')->put($htmlPath, $body);
                $headersPath = $basePath . 'response_headers.json';
                $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                Storage::disk('public')->put($headersPath, $headersJson);

                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'source_html',
                    'file_url' => $htmlPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured snapshot: Unauthorized domains detected - ' . implode(', ', $unauthorizedDomains),
                ]);
                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'response_headers',
                    'file_url' => $headersPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured response headers',
                ]);

                if (!empty($site->baseline_file_path) && Storage::disk('public')->exists($site->baseline_file_path)) {
                    BuktiDigital::create([
                        'id_kasus' => $kasus->id_kasus,
                        'jenis_bukti' => 'baseline_html',
                        'file_url' => $site->baseline_file_path,
                        'created_date' => now(),
                        'keterangan' => 'Baseline HTML for comparison',
                    ]);
                }

                ActivityLog::create([
                    'user_id' => null,
                    'action' => 'auto_detect_unauthorized_domain',
                    'target_type' => 'monitored_site',
                    'target_id' => $site->id_site,
                    'case_id' => $kasus->id_kasus,
                    'ip_address' => null,
                    'changes' => [
                        'unauthorized_domains' => $unauthorizedDomains,
                        'allowed_domains' => $site->allowed_domains,
                    ],
                ]);

                Log::critical("Unauthorized domain(s) detected for {$site->site_url} (id: {$site->id_site}): " . implode(', ', $unauthorizedDomains));
                return;
            }

            // Read selectors from MonitoredSite (comma-separated) or apply Smart Defaults
            // Default Static Selector if not provided
            if (! empty($site->selector_static)) {
                $staticSelectors = array_values(array_filter(array_map('trim', explode(',', $site->selector_static))));
            } else {
                // elements that rarely change structure
                $staticSelectors = ['nav', 'header', 'footer', 'aside', '.navbar', '.footer'];
            }

            // Dynamic selectors: if user provided, use them; otherwise attempt auto-discovery
            if (! empty($site->selector_dynamic)) {
                $dynamicSelectors = array_values(array_filter(array_map('trim', explode(',', $site->selector_dynamic))));
            } else {
                // Candidate selectors in order to find the main content area
                $candidates = ['main', 'article', '#content', '#main', '.content', 'body'];
                $dynamicSelectors = [];
                foreach ($candidates as $cand) {
                    $foundHtml = $getNodesHtml($domCurrent, [$cand]);
                    if (trim($foundHtml) !== '') {
                        $dynamicSelectors[] = $cand;
                        break; // use the first candidate that yields content
                    }
                }
                // If none matched, fallback to 'body' but will trigger global blacklist scan if empty
                if (empty($dynamicSelectors)) {
                    $dynamicSelectors = ['body'];
                }
            }

            // --- Step 1: Static Zone Integrity Check (with normalization) ---
            $baselineStaticHtml = $getNodesHtml($domBaseline, $staticSelectors);
            $currentStaticHtml = $getNodesHtml($domCurrent, $staticSelectors);

            // Normalize both to remove dynamic elements (CSRF, session, timestamps)
            $baselineStaticNormalized = $this->normalizeHtml($baselineStaticHtml);
            $currentStaticNormalized = $this->normalizeHtml($currentStaticHtml);

            // If selectors did not find elements in baseline or current, fallback to a global blacklist scan
            if (trim($baselineStaticNormalized) === '' || trim($currentStaticNormalized) === '') {
                Log::warning("Static selectors did not match elements for site {$site->site_url} (id: {$site->id_site}). Falling back to global blacklist scan.");

                // perform global blacklist scan across the whole current DOM
                $xpathGlobal = new \DOMXPath($domCurrent);
                $forbiddenTagsGlobal = ['script', 'object', 'embed', 'iframe', 'meta', 'style'];
                $foundForbiddenGlobal = [];
                foreach ($forbiddenTagsGlobal as $tag) {
                    $nodesG = $xpathGlobal->query('//' . $tag);
                    if ($nodesG->length > 0) $foundForbiddenGlobal[] = $tag;
                }

                $nodesWithStyleGlobal = $xpathGlobal->query('//*[@style]');
                $suspiciousStylesGlobal = [];
                foreach ($nodesWithStyleGlobal as $node) {
                    $style = strtolower($node->getAttribute('style'));
                    if (strpos($style, 'display:none') !== false) {
                        $suspiciousStylesGlobal[] = 'display:none';
                    }
                    if (preg_match('/background(-image)?:.*url\(([^)]+)\)/i', $style, $m)) {
                        $url = trim($m[2], "'\" ");
                        $host = parse_url($url, PHP_URL_HOST);
                        if ($host) {
                            $suspiciousStylesGlobal[] = 'background-image:' . $host;
                        } else {
                            $suspiciousStylesGlobal[] = 'background-image:external';
                        }
                    }
                }

                $formNodesGlobal = $xpathGlobal->query('//form|//input');
                $foundFormGlobal = ($formNodesGlobal->length > 0);

                if (! empty($foundForbiddenGlobal) || ! empty($suspiciousStylesGlobal) || $foundFormGlobal) {
                    $site->status = 'DEFACED';
                    $site->save();

                    $descParts = [];
                    if (! empty($foundForbiddenGlobal)) $descParts[] = 'Forbidden tags found globally: ' . implode(', ', $foundForbiddenGlobal);
                    if (! empty($suspiciousStylesGlobal)) $descParts[] = 'Suspicious global inline styles: ' . implode(', ', $suspiciousStylesGlobal);
                    if ($foundFormGlobal) $descParts[] = 'Form/input elements detected globally';

                    $kasus = Kasus::create([
                        'id_site' => $site->id_site,
                        'jenis_kasus' => 'Defacement',
                        'tanggal_kejadian' => now(),
                        'deskripsi_kasus' => 'Automatic detection (global fallback): ' . implode(' ; ', $descParts),
                        'status_kasus' => 'Open',
                        'detection_source' => 'System Monitoring',
                        'impact_level' => 'High',
                    ]);

                    // Save evidence
                    $timestamp = now()->format('Ymd_His');
                    $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                    $htmlPath = $basePath . "global_injection_{$timestamp}.html";
                    Storage::disk('public')->put($htmlPath, $body);
                    $headersPath = $basePath . 'response_headers.json';
                    $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                    Storage::disk('public')->put($headersPath, $headersJson);

                    BuktiDigital::create([
                        'id_kasus' => $kasus->id_kasus,
                        'jenis_bukti' => 'source_html',
                        'file_url' => $htmlPath,
                        'created_date' => now(),
                        'keterangan' => 'Auto-captured snapshot from global blacklist fallback',
                    ]);

                    ActivityLog::create([
                        'user_id' => null,
                        'action' => 'auto_detect_global_injection',
                        'target_type' => 'monitored_site',
                        'target_id' => $site->id_site,
                        'case_id' => $kasus->id_kasus,
                        'ip_address' => null,
                        'changes' => [
                            'found_forbidden_global' => $foundForbiddenGlobal,
                            'suspicious_styles_global' => $suspiciousStylesGlobal,
                            'found_form_global' => $foundFormGlobal,
                        ],
                    ]);

                    Log::warning("Global blacklist fallback detected issues for site {$site->site_url} (id: {$site->id_site})");
                    return;
                }

                // if global scan found nothing, continue with next checks (e.g., link hijack / legacy scans)
            }

            // Hash comparison on NORMALIZED content
            $baselineStaticHash = hash('sha256', $baselineStaticNormalized);
            $currentStaticHash = hash('sha256', $currentStaticNormalized);

            if ($baselineStaticHash !== $currentStaticHash) {
                // Calculate similarity to avoid false positives on minor changes
                $similarity = 0;
                similar_text($baselineStaticNormalized, $currentStaticNormalized, $similarity);

                // Check for dangerous injections FIRST before marking as defaced
                $threats = $this->detectDangerousInjections($domCurrent);

                // Decision logic:
                // - Has threats (malicious script/iframe) → DEFACED (Critical)
                // - No threats + similarity < 70% → DEFACED (High - major layout change)
                // - No threats + similarity 70-80% → SUSPECT (Monitor closely)
                // - No threats + similarity ≥ 80% → SAFE (Continue monitoring)

                $shouldMarkDefaced = false;
                $impactLevel = 'High';

                if (!empty($threats)) {
                    // High: Malicious injection detected
                    $shouldMarkDefaced = true;
                    $impactLevel = 'High';
                } elseif ($similarity < 70) {
                    // High: Major structural change without obvious threats
                    $shouldMarkDefaced = true;
                    $impactLevel = 'High';
                } elseif ($similarity < 80) {
                    // Medium: Moderate change, mark as SUSPECT for investigation
                    $site->status = 'SUSPECT';
                    $site->save();
                    Log::warning("Moderate layout change on {$site->site_url} - Similarity: {$similarity}% - Marked as SUSPECT");
                } else {
                    // Safe: Minor change, no threats
                    Log::info("Safe content update on {$site->site_url} - Similarity: {$similarity}% - No threats");
                }

                if ($shouldMarkDefaced) {
                    $site->status = 'DEFACED';
                    $site->save();

                    $threatDescription = !empty($threats) ? implode(', ', $threats) : 'Major layout change';
                    $kasus = Kasus::create([
                        'id_site' => $site->id_site,
                        'jenis_kasus' => 'Defacement',
                        'tanggal_kejadian' => now(),
                        'deskripsi_kasus' => 'Automatic detection: ' . $threatDescription . ' (similarity: ' . round($similarity, 2) . '%)',
                        'status_kasus' => 'Open',
                        'detection_source' => 'System Monitoring',
                        'impact_level' => $impactLevel,
                    ]);

                    // Save evidence
                    $timestamp = now()->format('Ymd_His');
                    $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                    $htmlPath = $basePath . "threat_detected_{$timestamp}.html";
                    Storage::disk('public')->put($htmlPath, $body);
                    $headersPath = $basePath . 'response_headers.json';
                    $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                    Storage::disk('public')->put($headersPath, $headersJson);

                    BuktiDigital::create([
                        'id_kasus' => $kasus->id_kasus,
                        'jenis_bukti' => 'source_html',
                        'file_url' => $htmlPath,
                        'created_date' => now(),
                        'keterangan' => 'Auto-captured snapshot: ' . $threatDescription,
                    ]);
                    BuktiDigital::create([
                        'id_kasus' => $kasus->id_kasus,
                        'jenis_bukti' => 'response_headers',
                        'file_url' => $headersPath,
                        'created_date' => now(),
                        'keterangan' => 'Auto-captured response headers',
                    ]);

                    if (! empty($site->baseline_file_path) && Storage::disk('public')->exists($site->baseline_file_path)) {
                        BuktiDigital::create([
                            'id_kasus' => $kasus->id_kasus,
                            'jenis_bukti' => 'baseline_html',
                            'file_url' => $site->baseline_file_path,
                            'created_date' => now(),
                            'keterangan' => 'Existing baseline from monitored site',
                        ]);
                    }

                    ActivityLog::create([
                        'user_id' => null,
                        'action' => 'auto_detect_threat',
                        'target_type' => 'monitored_site',
                        'target_id' => $site->id_site,
                        'case_id' => $kasus->id_kasus,
                        'ip_address' => null,
                        'changes' => [
                            'baseline_static_hash' => $baselineStaticHash,
                            'current_static_hash' => $currentStaticHash,
                            'similarity_percent' => round($similarity, 2),
                            'threats_detected' => $threats,
                            'threat_count' => count($threats),
                            'impact_level' => $impactLevel,
                        ],
                    ]);

                    Log::warning("Defacement detected for {$site->site_url} - Impact: {$impactLevel} - Threats: " . count($threats) . " - Similarity: " . round($similarity, 2) . "%");
                    return;
                }
            }

            // --- Step 2: Dynamic Zone Safety Check (Smart Detection - Structure Only) ---
            // For dynamic zones (announcements, posts, etc.), we DON'T compare content changes
            // Instead, we ONLY look for malicious INJECTIONS while allowing legitimate content updates

            $baselineDynamicHtml = $getNodesHtml($domBaseline, $dynamicSelectors);
            $currentDynamicHtml = $getNodesHtml($domCurrent, $dynamicSelectors);

            // Create DOM fragments for dynamic areas to allow searching for tags
            $domBaselineDynamic = $domFor($baselineDynamicHtml ?: '<div></div>');
            $domCurrentDynamic = $domFor($currentDynamicHtml ?: '<div></div>');
            $xpathCurrentDynamic = new \DOMXPath($domCurrentDynamic);
            $xpathBaselineDynamic = new \DOMXPath($domBaselineDynamic);

            // Count dangerous tags in BASELINE vs CURRENT
            // Only flag if there's a SIGNIFICANT INCREASE (not just existence)
            $dangerousTagsToMonitor = ['script', 'iframe', 'object', 'embed'];
            $baselineTagCounts = [];
            $currentTagCounts = [];

            foreach ($dangerousTagsToMonitor as $tag) {
                $baselineTagCounts[$tag] = $xpathBaselineDynamic->query('//' . $tag)->length;
                $currentTagCounts[$tag] = $xpathCurrentDynamic->query('//' . $tag)->length;
            }

            // Detect NEW or INCREASED dangerous tags (potential injection)
            $newDangerousTags = [];
            foreach ($dangerousTagsToMonitor as $tag) {
                $increase = $currentTagCounts[$tag] - $baselineTagCounts[$tag];
                if ($increase > 0) {
                    // NEW tags appeared - verify if they're malicious
                    $nodes = $xpathCurrentDynamic->query('//' . $tag);
                    $isMalicious = false;

                    foreach ($nodes as $node) {
                        if ($tag === 'script') {
                            $src = $node->getAttribute('src');
                            $content = $node->textContent;

                            // Check for external suspicious scripts
                            if ($src) {
                                $host = parse_url($src, PHP_URL_HOST);
                                $safeCDNs = ['cdnjs.cloudflare.com', 'cdn.jsdelivr.net', 'ajax.googleapis.com', 'code.jquery.com', 'unpkg.com'];
                                $isSafeCDN = false;
                                foreach ($safeCDNs as $cdn) {
                                    if (stripos($host, $cdn) !== false) {
                                        $isSafeCDN = true;
                                        break;
                                    }
                                }
                                if (!$isSafeCDN && $host) {
                                    $isMalicious = true;
                                    break;
                                }
                            }

                            // Check for malicious patterns in inline scripts
                            $maliciousPatterns = ['eval(', 'base64_decode(', 'atob(', 'fromcharcode'];
                            foreach ($maliciousPatterns as $pattern) {
                                if (stripos($content, $pattern) !== false) {
                                    $isMalicious = true;
                                    break 2;
                                }
                            }
                        } elseif ($tag === 'iframe') {
                            $src = $node->getAttribute('src');
                            if ($src) {
                                $host = parse_url($src, PHP_URL_HOST);
                                $safeIframes = ['youtube.com', 'youtu.be', 'vimeo.com', 'google.com', 'maps.google.com'];
                                $isSafe = false;
                                foreach ($safeIframes as $safe) {
                                    if (stripos($host, $safe) !== false) {
                                        $isSafe = true;
                                        break;
                                    }
                                }
                                if (!$isSafe && $host) {
                                    $isMalicious = true;
                                    break;
                                }
                            }
                        } else {
                            // object, embed are rarely legitimate
                            $isMalicious = true;
                            break;
                        }
                    }

                    if ($isMalicious) {
                        $newDangerousTags[] = $tag . ' (+' . $increase . ')';
                    }
                }
            }

            // Check for suspicious NEW inline styles (only if they weren't in baseline)
            $suspiciousStyles = [];
            $currentStyleNodes = $xpathCurrentDynamic->query('//*[@style]');
            foreach ($currentStyleNodes as $node) {
                $style = strtolower($node->getAttribute('style'));

                // Only flag truly suspicious patterns
                if (preg_match('/position\s*:\s*fixed/i', $style) && preg_match('/z-index\s*:\s*999/i', $style)) {
                    $suspiciousStyles[] = 'fixed-overlay (possible hijack)';
                }
                if (preg_match('/opacity\s*:\s*0/i', $style) || strpos($style, 'visibility:hidden') !== false) {
                    $suspiciousStyles[] = 'hidden-content';
                }
            }

            // Only report if MALICIOUS injection detected (not just content change)
            if (! empty($newDangerousTags) || ! empty($suspiciousStyles)) {
                $site->status = 'DEFACED';
                $site->save();

                $descParts = [];
                if (! empty($newDangerousTags)) $descParts[] = 'New malicious tags detected: ' . implode(', ', $newDangerousTags);
                if (! empty($suspiciousStyles)) $descParts[] = 'Suspicious styles: ' . implode(', ', $suspiciousStyles);

                $kasus = Kasus::create([
                    'id_site' => $site->id_site,
                    'jenis_kasus' => 'Defacement',
                    'tanggal_kejadian' => now(),
                    'deskripsi_kasus' => 'Automatic detection (malicious injection): ' . implode(' ; ', $descParts),
                    'status_kasus' => 'Open',
                    'detection_source' => 'System Monitoring',
                    'impact_level' => 'High',
                ]);

                // Save evidence
                $timestamp = now()->format('Ymd_His');
                $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                $htmlPath = $basePath . "injection_{$timestamp}.html";
                Storage::disk('public')->put($htmlPath, $body);
                $headersPath = $basePath . 'response_headers.json';
                $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                Storage::disk('public')->put($headersPath, $headersJson);

                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'source_html',
                    'file_url' => $htmlPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured snapshot for malicious injection in dynamic zone',
                ]);
                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'response_headers',
                    'file_url' => $headersPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured response headers',
                ]);

                ActivityLog::create([
                    'user_id' => null,
                    'action' => 'auto_detect_dynamic_injection',
                    'target_type' => 'monitored_site',
                    'target_id' => $site->id_site,
                    'case_id' => $kasus->id_kasus,
                    'ip_address' => null,
                    'changes' => [
                        'new_dangerous_tags' => $newDangerousTags,
                        'suspicious_styles' => $suspiciousStyles,
                        'baseline_counts' => $baselineTagCounts,
                        'current_counts' => $currentTagCounts,
                    ],
                ]);

                Log::warning("Malicious injection detected in dynamic zone for {$site->site_url} (id: {$site->id_site})");
                return;
            }

            // Content in dynamic zone changed but no malicious injection detected
            // This is NORMAL and EXPECTED (e.g., new announcements, posts, etc.)
            Log::info("Dynamic content updated safely on {$site->site_url} - No threats detected");

            // --- Step 3: Button/Link Hijacking Detection ---
            $xpathCurrent = new \DOMXPath($domCurrent);
            $anchorNodes = $xpathCurrent->query('//a[@href]');

            // Allowed/whitelist domains from DB (may be stored as array or json)
            $allowed = [];
            if (! empty($site->allowed_domains)) {
                if (is_array($site->allowed_domains)) {
                    $allowed = $site->allowed_domains;
                } else {
                    $decoded = json_decode($site->allowed_domains, true);
                    if (is_array($decoded)) $allowed = $decoded;
                }
            }
            $allowed = array_map('strtolower', $allowed);

            $suspectAnchors = [];
            foreach ($anchorNodes as $a) {
                // Only consider anchors within dynamic selectors
                $parent = $a;
                $foundInDynamic = false;
                while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
                    foreach ($dynamicSelectors as $sel) {
                        if (strpos($sel, '#') === 0 && $parent->hasAttribute('id') && $parent->getAttribute('id') === substr($sel,1)) {
                            $foundInDynamic = true; break 2;
                        }
                        if (strpos($sel, '.') === 0 && $parent->hasAttribute('class') && strpos(' '. $parent->getAttribute('class') .' ', ' '. substr($sel,1) .' ') !== false) {
                            $foundInDynamic = true; break 2;
                        }
                        if ($parent->nodeName === $sel) { $foundInDynamic = true; break 2; }
                    }
                    $parent = $parent->parentNode;
                }
                if (! $foundInDynamic) continue;

                $href = $a->getAttribute('href');
                $host = parse_url($href, PHP_URL_HOST);
                $classes = strtolower($a->getAttribute('class') ?? '');
                // consider as button-like if class contains 'btn' or 'button'
                if ($host && preg_match('/\b(btn|button|btn-)/', $classes)) {
                    $hostLower = strtolower($host);
                    if (! in_array($hostLower, $allowed) && $hostLower !== parse_url($site->site_url, PHP_URL_HOST)) {
                        $suspectAnchors[] = $href;
                    }
                }
            }

            if (! empty($suspectAnchors)) {
                $site->status = 'SUSPECT';
                $site->save();

                $kasus = Kasus::create([
                    'id_site' => $site->id_site,
                    'jenis_kasus' => 'Suspect Defacement',
                    'tanggal_kejadian' => now(),
                    'deskripsi_kasus' => 'Automatic detection: suspected button/link hijacking to external domains: ' . implode(', ', $suspectAnchors),
                    'status_kasus' => 'Open',
                    'detection_source' => 'System Monitoring',
                    'impact_level' => 'Medium',
                ]);

                // Save snapshot and evidence
                $timestamp = now()->format('Ymd_His');
                $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                $htmlPath = $basePath . "suspect_link_{$timestamp}.html";
                Storage::disk('public')->put($htmlPath, $body);
                $headersPath = $basePath . 'response_headers.json';
                $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                Storage::disk('public')->put($headersPath, $headersJson);

                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'source_html',
                    'file_url' => $htmlPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured snapshot for suspect external links/buttons',
                ]);

                ActivityLog::create([
                    'user_id' => null,
                    'action' => 'auto_detect_link_hijack',
                    'target_type' => 'monitored_site',
                    'target_id' => $site->id_site,
                    'case_id' => $kasus->id_kasus,
                    'ip_address' => null,
                    'changes' => [
                        'suspect_anchors' => $suspectAnchors,
                    ],
                ]);

                Log::warning("Suspect link/button hijack detected for site {$site->site_url} (id: {$site->id_site})");
                return;
            }

            // --- Step 4: Keyword & Malware Scan (legacy checks preserved) ---
            // Extract domains from src attributes in current content (script and iframe)
            $srcPattern = '/<(?:script|iframe)\\b[^>]*\\ssrc=["\']([^"\']+)["\'][^>]*>/i';
            preg_match_all($srcPattern, $body, $srcMatches);
            $currentDomains = [];
            foreach ($srcMatches[1] ?? [] as $src) {
                $host = parse_url($src, PHP_URL_HOST);
                if ($host) {
                    $currentDomains[] = strtolower($host);
                }
            }
            $currentDomains = array_values(array_unique($currentDomains));

            // Allowed/whitelist domains already resolved above as $allowed
            $unknownDomains = array_values(array_diff($currentDomains, $allowed));

            // Count script and iframe tags in current content
            $scriptPattern = '#<script\\b[^>]*>(.*?)</script>#is';
            $iframePattern = '#<iframe\\b[^>]*>(.*?)</iframe>#is';
            preg_match_all($scriptPattern, $body, $scriptMatches);
            preg_match_all($iframePattern, $body, $iframeMatches);
            $currentScriptCount = count($scriptMatches[0] ?? []) + count($iframeMatches[0] ?? []);

            // Keyword scan inside script contents (case-insensitive)
            $dangerKeywords = [
                'eval(base64_decode',
                'coin-hive',
                'document.write(unescape',
            ];
            $foundKeywords = [];
            foreach ($scriptMatches[1] ?? [] as $scriptContent) {
                $lower = strtolower($scriptContent);
                foreach ($dangerKeywords as $kw) {
                    if (strpos($lower, $kw) !== false) {
                        $foundKeywords[] = $kw;
                    }
                }
            }
            $foundKeywords = array_values(array_unique($foundKeywords));

            // Bad Keyword Scanner (local Indonesian keywords)
            $badKeywords = [
                'Hacked by',
                'Pwned',
                'Slot Gacor',
                'RTP Live',
                'Judi Online',
                'Poker',
                'Togel',
            ];
            $foundBadKeywords = [];
            $lowerBody = strtolower($body);
            foreach ($badKeywords as $bk) {
                if (strpos($lowerBody, strtolower($bk)) !== false) {
                    $foundBadKeywords[] = $bk;
                }
            }
            $foundBadKeywords = array_values(array_unique($foundBadKeywords));

            if (! empty($foundBadKeywords)) {
                $site->status = 'SUSPECT';
                $site->save();

                $kasus = Kasus::create([
                    'id_site' => $site->id_site,
                    'jenis_kasus' => 'Suspect Defacement',
                    'tanggal_kejadian' => now(),
                    'deskripsi_kasus' => 'Automatic detection: suspicious keywords found: ' . implode(', ', $foundBadKeywords),
                    'status_kasus' => 'Open',
                    'detection_source' => 'System Monitoring',
                    'impact_level' => 'Medium',
                ]);

                // save evidence snapshot
                $timestamp = now()->format('Ymd_His');
                $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                $htmlPath = $basePath . "suspect_{$timestamp}.html";
                Storage::disk('public')->put($htmlPath, $body);
                $headersPath = $basePath . 'response_headers.json';
                $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                Storage::disk('public')->put($headersPath, $headersJson);

                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'source_html',
                    'file_url' => $htmlPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured snapshot for suspect keyword detection',
                ]);

                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'response_headers',
                    'file_url' => $headersPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured response headers',
                ]);

                ActivityLog::create([
                    'user_id' => null,
                    'action' => 'auto_detect_bad_keyword',
                    'target_type' => 'monitored_site',
                    'target_id' => $site->id_site,
                    'case_id' => $kasus->id_kasus,
                    'ip_address' => null,
                    'changes' => [
                        'found_bad_keywords' => $foundBadKeywords,
                    ],
                ]);

                Log::warning("Suspect defacement keywords detected for site {$site->site_url} (id: {$site->id_site}) - " . implode(',', $foundBadKeywords));

                return;
            }

            // If unknown domains or dangerous keywords found -> mark as Malware Injection incident
            if (! empty($unknownDomains) || ! empty($foundKeywords)) {
                $site->status = 'DEFACED';
                $site->save();

                $kasus = Kasus::create([
                    'id_site' => $site->id_site,
                    'jenis_kasus' => 'Malware Injection',
                    'tanggal_kejadian' => now(),
                    'deskripsi_kasus' => 'Automatic detection: ' . (
                        ! empty($unknownDomains) ? 'Detected unknown domain(s): ' . implode(', ', $unknownDomains) : ''
                    ) . (! empty($foundKeywords) ? ' Detected suspicious script patterns: ' . implode(', ', $foundKeywords) : ''),
                    'status_kasus' => 'Open',
                    'detection_source' => 'System Monitoring',
                    'impact_level' => 'High',
                ]);

                // Save snapshot and headers as evidence
                $timestamp = now()->format('Ymd_His');
                $basePath = "bukti_digital/monitored_site_{$site->id_site}/" . $timestamp . '/';
                $htmlPath = $basePath . "malware_{$timestamp}.html";
                Storage::disk('public')->put($htmlPath, $body);
                $headersPath = $basePath . 'response_headers.json';
                $headersJson = json_encode($response->getHeaders(), JSON_PRETTY_PRINT);
                Storage::disk('public')->put($headersPath, $headersJson);

                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'source_html',
                    'file_url' => $htmlPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured snapshot for malware detection',
                ]);
                BuktiDigital::create([
                    'id_kasus' => $kasus->id_kasus,
                    'jenis_bukti' => 'response_headers',
                    'file_url' => $headersPath,
                    'created_date' => now(),
                    'keterangan' => 'Auto-captured response headers',
                ]);

                // Include baseline as evidence if available
                if (! empty($site->baseline_file_path) && Storage::disk('public')->exists($site->baseline_file_path)) {
                    BuktiDigital::create([
                        'id_kasus' => $kasus->id_kasus,
                        'jenis_bukti' => 'baseline_html',
                        'file_url' => $site->baseline_file_path,
                        'created_date' => now(),
                        'keterangan' => 'Existing baseline from monitored site',
                    ]);
                }

                ActivityLog::create([
                    'user_id' => null,
                    'action' => 'auto_detect_malware',
                    'target_type' => 'monitored_site',
                    'target_id' => $site->id_site,
                    'case_id' => $kasus->id_kasus,
                    'ip_address' => null,
                    'changes' => [
                        'unknown_domains' => $unknownDomains,
                        'found_keywords' => $foundKeywords,
                    ],
                ]);

                Log::warning("Malware injection detected for site {$site->site_url} (id: {$site->id_site}) - unknown domains: " . implode(',', $unknownDomains));

                return;
            }

            // If everything passed -> mark UP and update baseline for safe drift
            // Update baseline to include legitimate content changes (announcements, posts, etc.)
            $site->status = 'UP';
            $site->last_checked_at = now();
            try {
                Storage::disk('public')->put($site->baseline_file_path, $body);
                $site->baseline_hash = hash('sha256', $body);
                Log::info("Baseline updated for {$site->site_url} - Safe content drift detected");
            } catch (\Exception $e) {
                Log::error('Failed to update baseline file for site ' . $site->id_site . ': ' . $e->getMessage());
            }
            $site->save();
        } catch (\Exception $e) {
            Log::error('Error checking site ' . $site->site_url . ': ' . $e->getMessage());
            // Optionally, mark as DOWN if connection issues occur
            $site->status = 'DOWN';
            $site->save();
        }
    }
}

