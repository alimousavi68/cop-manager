<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX actions
add_action('wp_ajax_cop_test_selectors', 'cop_ajax_test_selectors');
add_action('wp_ajax_cop_suggest_selectors', 'cop_ajax_suggest_selectors');
add_action('wp_ajax_cop_discover_feed_url', 'cop_ajax_discover_feed_url');
add_action('wp_ajax_cop_suggest_escape_elements', 'cop_ajax_suggest_escape_elements');

/**
 * Helper to fetch HTML content and handle encodings
 */
function cop_fetch_html_for_assistant($url) {
    if (empty($url)) {
        return new WP_Error('empty_url', 'آدرس نمونه خالی است.');
    }

    $encoded_url = preg_replace_callback('/[^\x20-\x7f]/', function ($matches) {
        return rawurlencode($matches[0]);
    }, $url);

    $response = wp_remote_get($encoded_url, array(
        'timeout' => 20,
        'redirection' => 3,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code != 200) {
        return new WP_Error('http_error', 'کد وضعیت HTTP ناموفق: ' . $status_code);
    }

    $html = wp_remote_retrieve_body($response);
    
    // Convert to UTF-8
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    if (stripos($content_type, 'windows-1256') !== false) {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'windows-1256');
    } else {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    }

    return $html;
}



/**
 * Extract single element by CSS/XPath selector
 */
function cop_extract_by_selector($xpath_obj, $selector, $attr = '') {
    $xpath_query = cop_css_to_xpath($selector);
    if (empty($xpath_query)) {
        return '';
    }

    $nodes = $xpath_obj->query($xpath_query);
    if ($nodes === false || $nodes->length === 0) {
        return '';
    }

    $node = $nodes->item(0);
    $tag_name = strtolower($node->nodeName);

    // Auto extract content attribute for meta tags if no specific attribute is requested
    if ($tag_name === 'meta' && empty($attr)) {
        return trim($node->getAttribute('content'));
    }

    if (!empty($attr)) {
        return trim($node->getAttribute($attr));
    }

    return trim($node->textContent);
}

/**
 * Action: Test selectors in real-time
 */
function cop_ajax_test_selectors() {
    check_ajax_referer('cop_selector_assistant_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'دسترسی غیرمجاز.'));
    }

    $url = esc_url_raw($_POST['sample_url']);
    $title_sel = sanitize_text_field(wp_unslash($_POST['title_selector']));
    $lead_sel = sanitize_text_field(wp_unslash($_POST['lead_selector']));
    $body_sel = sanitize_text_field(wp_unslash($_POST['body_selector']));
    $img_sel = sanitize_text_field(wp_unslash($_POST['img_selector']));

    $html = cop_fetch_html_for_assistant($url);
    if (is_wp_error($html)) {
        wp_send_json_error(array('message' => 'خطا در واکشی صفحه خبر نمونه: ' . $html->get_error_message()));
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Robust image extraction
    $img_src = '';
    $xpath_query = cop_css_to_xpath($img_sel);
    if (!empty($xpath_query)) {
        $nodes = $xpath->query($xpath_query);
        if ($nodes && $nodes->length > 0) {
            $img_node = $nodes->item(0);
            $tag_name = strtolower($img_node->nodeName);
            if ($tag_name === 'meta') {
                $img_src = trim($img_node->getAttribute('content'));
            } elseif ($tag_name === 'img') {
                $src_candidates = array('src', 'data-src', 'data-lazy-src', 'data-original', 'srcset');
                foreach ($src_candidates as $attr_name) {
                    $val = trim($img_node->getAttribute($attr_name));
                    if (!empty($val) && strpos($val, 'data:image') !== 0) {
                        // Handle possible multiple URLs in srcset
                        if ($attr_name === 'srcset') {
                            $srcset_parts = preg_split('/\s+/', $val);
                            if (!empty($srcset_parts[0])) {
                                $img_src = $srcset_parts[0];
                                break;
                            }
                        } else {
                            $img_src = $val;
                            break;
                        }
                    }
                }
                if (empty($img_src)) {
                    $img_src = trim($img_node->getAttribute('src'));
                }
            } else {
                // If it is a container, look for the first img tag inside it
                $sub_imgs = $xpath->query('.//img', $img_node);
                if ($sub_imgs && $sub_imgs->length > 0) {
                    $first_sub_img = $sub_imgs->item(0);
                    $src_candidates = array('src', 'data-src', 'data-lazy-src', 'data-original', 'srcset');
                    foreach ($src_candidates as $attr_name) {
                        $val = trim($first_sub_img->getAttribute($attr_name));
                        if (!empty($val) && strpos($val, 'data:image') !== 0) {
                            if ($attr_name === 'srcset') {
                                $srcset_parts = preg_split('/\s+/', $val);
                                if (!empty($srcset_parts[0])) {
                                    $img_src = $srcset_parts[0];
                                    break;
                                }
                            } else {
                                $img_src = $val;
                                break;
                            }
                        }
                    }
                    if (empty($img_src)) {
                        $img_src = trim($first_sub_img->getAttribute('src'));
                    }
                }
            }
        }
    }

    $extracted = array(
        'title' => cop_extract_by_selector($xpath, $title_sel),
        'lead' => cop_extract_by_selector($xpath, $lead_sel),
        'body' => cop_extract_by_selector($xpath, $body_sel),
        'img_src' => $img_src,
    );

    // Validate results and send report
    $report = array();
    $report['title_status'] = !empty($extracted['title']) ? 'success' : 'failed';
    $report['lead_status']  = !empty($extracted['lead'])  ? 'success' : (!empty($lead_sel) ? 'failed' : 'skipped');
    $report['body_status']  = !empty($extracted['body'])  ? 'success' : 'failed';
    $report['img_status']   = !empty($extracted['img_src']) ? 'success' : (!empty($img_sel) ? 'failed' : 'skipped');
    $report['extracted'] = $extracted;

    wp_send_json_success($report);
}

/**
 * Action: Analyze page and auto-suggest selectors
 */
function cop_ajax_suggest_selectors() {
    check_ajax_referer('cop_selector_assistant_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'دسترسی غیرمجاز.'));
    }

    $url = esc_url_raw($_POST['sample_url']);

    $html = cop_fetch_html_for_assistant($url);
    if (is_wp_error($html)) {
        wp_send_json_error(array('message' => 'خطا در واکشی صفحه خبر نمونه: ' . $html->get_error_message()));
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $suggestions = array();

    // 1. Suggest Title Selectors
    $title_candidates = array();
    // Candidate 1: H1
    $h1_nodes = $xpath->query('//h1');
    if ($h1_nodes && $h1_nodes->length > 0) {
        foreach ($h1_nodes as $node) {
            $class = $node->getAttribute('class');
            $id = $node->getAttribute('id');
            if (!empty($id)) {
                $title_candidates[] = array('selector' => '#' . $id, 'confidence' => '95%', 'reason' => 'تگ H1 با شناسه منحصر‌به‌فرد');
            } elseif (!empty($class)) {
                // Take first class
                $first_class = strtok($class, ' ');
                $title_candidates[] = array('selector' => 'h1.' . $first_class, 'confidence' => '90%', 'reason' => 'تگ H1 با کلاس مشخص');
            }
        }
        $title_candidates[] = array('selector' => 'h1', 'confidence' => '85%', 'reason' => 'تگ عمومی H1 صفحه');
    }
    // Candidate 2: Common Title Classes
    $common_title_selectors = array('.entry-title', '.post-title', '.news-title', '.title');
    foreach ($common_title_selectors as $sel) {
        $nodes = $xpath->query(cop_css_to_xpath($sel));
        if ($nodes && $nodes->length > 0) {
            $title_candidates[] = array('selector' => $sel, 'confidence' => '80%', 'reason' => 'کلاس استاندارد متداول برای عنوان');
        }
    }
    $suggestions['title'] = array_slice($title_candidates, 0, 3);

    // 2. Suggest Image Selectors
    $image_candidates = array();
    // Candidate 1: OpenGraph Image
    $og_img_node = $xpath->query("//meta[@property='og:image']");
    if ($og_img_node && $og_img_node->length > 0) {
        $image_candidates[] = array('selector' => "//meta[@property='og:image']", 'confidence' => '95%', 'reason' => 'تصویر شاخص معرفی‌شده در متاتگ OpenGraph');
    }
    // Candidate 2: Common Featured Image classes
    $common_img_selectors = array('.post-thumbnail img', '.entry-content img', '.attachment-post-thumbnail img', '.featured-image img');
    foreach ($common_img_selectors as $sel) {
        $nodes = $xpath->query(cop_css_to_xpath($sel));
        if ($nodes && $nodes->length > 0) {
            $image_candidates[] = array('selector' => $sel, 'confidence' => '80%', 'reason' => 'سلکتور استاندارد تصویر شاخص');
        }
    }
    $suggestions['image'] = array_slice($image_candidates, 0, 3);

    // 3. Suggest Lead Selectors
    $lead_candidates = array();
    $common_lead_selectors = array('.lead', '.excerpt', '.summary', '.entry-summary', '.intro');
    foreach ($common_lead_selectors as $sel) {
        $nodes = $xpath->query(cop_css_to_xpath($sel));
        if ($nodes && $nodes->length > 0) {
            $lead_candidates[] = array('selector' => $sel, 'confidence' => '85%', 'reason' => 'کلاس متداول لید یا خلاصه خبر');
        }
    }
    // Candidate 2: Meta description
    $meta_desc = $xpath->query("//meta[@name='description']");
    if ($meta_desc && $meta_desc->length > 0) {
        $lead_candidates[] = array('selector' => "//meta[@name='description']", 'confidence' => '75%', 'reason' => 'خلاصه از متاتگ توضیحات صفحه');
    }
    $suggestions['lead'] = array_slice($lead_candidates, 0, 3);

    // 4. Suggest Body Selectors
    $body_candidates = array();
    $common_body_selectors = array('.entry-content', '.post-content', '.article-body', '.post-body', 'article', '.content');
    foreach ($common_body_selectors as $sel) {
        $nodes = $xpath->query(cop_css_to_xpath($sel));
        if ($nodes && $nodes->length > 0) {
            $body_candidates[] = array('selector' => $sel, 'confidence' => '90%', 'reason' => 'سلکتور استاندارد محتوای بدنه خبر');
        }
    }
    // Fallback: look for divs containing multiple <p> tags
    $div_nodes = $xpath->query('//div');
    if ($div_nodes) {
        $best_div = null;
        $max_p = 0;
        foreach ($div_nodes as $div) {
            $p_nodes = $xpath->query('.//p', $div);
            if ($p_nodes && $p_nodes->length > $max_p) {
                $max_p = $p_nodes->length;
                $best_div = $div;
            }
        }
        if ($best_div && $max_p > 2) {
            $class = $best_div->getAttribute('class');
            $id = $best_div->getAttribute('id');
            if (!empty($id)) {
                $body_candidates[] = array('selector' => '#' . $id, 'confidence' => '85%', 'reason' => 'بلوک با شناسه مشخص و بیشترین تعداد پاراگراف');
            } elseif (!empty($class)) {
                $first_class = strtok($class, ' ');
                $body_candidates[] = array('selector' => 'div.' . $first_class, 'confidence' => '80%', 'reason' => 'بلوک با کلاس مشخص و بیشترین تعداد پاراگراف');
            }
        }
    }
    $suggestions['body'] = array_slice($body_candidates, 0, 3);

    wp_send_json_success($suggestions);
}

/**
 * Action: Analyze page DOM and suggest escape/removal candidates
 * Uses 8 detection strategies based on common patterns of noise elements
 */
function cop_ajax_suggest_escape_elements() {
    check_ajax_referer('cop_selector_assistant_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'دسترسی غیرمجاز.'));
    }

    $url = esc_url_raw($_POST['sample_url']);
    $body_selector = isset($_POST['body_selector']) ? sanitize_text_field(wp_unslash($_POST['body_selector'])) : '';

    if (empty($url)) {
        wp_send_json_error(array('message' => 'آدرس نمونه خالی است.'));
    }

    if (empty($body_selector)) {
        wp_send_json_error(array('message' => 'لطفاً ابتدا Body Selector (سلکتور بدنه خبر) را وارد یا انتخاب کنید تا آنالیز المان‌های مزاحم درون آن انجام شود.'));
    }

    $html = cop_fetch_html_for_assistant($url);
    if (is_wp_error($html)) {
        wp_send_json_error(array('message' => 'خطا در واکشی صفحه: ' . $html->get_error_message()));
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // پیدا کردن نود بدنه اصلی خبر
    $body_xpath_query = cop_css_to_xpath($body_selector);
    if (empty($body_xpath_query)) {
        wp_send_json_error(array('message' => 'فرمت Body Selector وارد شده نامعتبر است.'));
    }

    $body_nodes = @$xpath->query($body_xpath_query);
    if (!$body_nodes || $body_nodes->length === 0) {
        wp_send_json_error(array('message' => 'المان بدنه خبر با سلکتور "' . esc_html($body_selector) . '" در این صفحه پیدا نشد. ابتدا از صحّت سلکتور بدنه خبر مطمئن شوید.'));
    }

    $body_node = $body_nodes->item(0);
    $candidates = array();

    /**
     * Helper: add a candidate if the selector finds at least one element WITHIN body_node
     */
    $add_if_found = function($selector, $label, $reason, $confidence) use (&$candidates, $xpath, $body_node) {
        $xp = cop_css_to_xpath($selector);
        if (empty($xp)) return;

        // کوئری نسبی فقط درون نود بدنه خبر
        $relative_xp = (strpos($xp, '//') === 0) ? '.' . $xp : './/' . ltrim($xp, '/');
        $nodes = @$xpath->query($relative_xp, $body_node);
        if ($nodes && $nodes->length > 0) {
            $candidates[] = array(
                'selector'   => $selector,
                'label'      => $label,
                'reason'     => $reason,
                'confidence' => $confidence,
                'count'      => $nodes->length,
            );
        }
    };

    // ─────────────────────────────────────────────
    // Strategy 1: تبلیغات — Ad Containers
    // ─────────────────────────────────────────────
    $ad_patterns = array(
        '.ad'            => array('تبلیغ عمومی', 'کلاس استاندارد کانتینر تبلیغاتی', '90%'),
        '.ads'           => array('تبلیغات', 'کلاس جمع تبلیغات', '90%'),
        '.advertisement' => array('تبلیغ (advertisement)', 'کلاس انگلیسی رسمی تبلیغ', '92%'),
        '.ad-container'  => array('کانتینر تبلیغ', 'کلاس رایج برای wrapper تبلیغات', '88%'),
        '.ad-banner'     => array('بنر تبلیغاتی', 'بنرهای تبلیغاتی معمولاً داخل بدنه خبر', '87%'),
        '.ad-box'        => array('باکس تبلیغاتی', 'باکس تبلیغاتی درون محتوا', '87%'),
        '.tedad'         => array('تبلیغ (تداد)', 'سرویس تبلیغاتی رایج در سایت‌های ایرانی', '85%'),
        '.taboola'       => array('تبلیغ (تابولا)', 'ویجت تبلیغاتی تابولا', '95%'),
        '.outbrain'      => array('تبلیغ (اوت‌براین)', 'ویجت تبلیغاتی اوت‌براین', '95%'),
        '[id*="advert"]' => array('کانتینر تبلیغ (id)', 'المان با شناسه حاوی کلمه advert', '80%'),
        '[class*="advert"]' => array('کانتینر تبلیغ (class)', 'المان با کلاس حاوی کلمه advert', '80%'),
        '.google-ad'     => array('تبلیغ گوگل', 'ویجت Google AdSense', '93%'),
        'ins.adsbygoogle' => array('AdSense', 'تبلیغ گوگل AdSense', '97%'),
    );
    foreach ($ad_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 2: اخبار مرتبط — Related News
    // ─────────────────────────────────────────────
    $related_patterns = array(
        '.related'          => array('اخبار مرتبط', 'کانتینر رایج اخبار مرتبط', '85%'),
        '.related-news'     => array('اخبار مرتبط', 'کلاس مستقیم اخبار مرتبط', '90%'),
        '.related-posts'    => array('پست‌های مرتبط', 'پست‌های مرتبط وردپرسی', '88%'),
        '.related-articles' => array('مقالات مرتبط', 'مقالات مرتبط استاندارد', '88%'),
        '.news-related'     => array('اخبار مرتبط', 'کلاس فارسی رایج', '85%'),
        '[class*="related"]' => array('مرتبط (عمومی)', 'هر المانی با کلاس حاوی related', '75%'),
        '.suggested-news'   => array('اخبار پیشنهادی', 'پیشنهاد محتوای مرتبط', '85%'),
        '.see-also'         => array('همچنین ببینید', 'بخش «همچنین ببینید»', '82%'),
        '.read-more'        => array('بیشتر بخوانید', 'لینک‌های بیشتر بخوانید', '80%'),
    );
    foreach ($related_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 3: اشتراک‌گذاری — Social Share
    // ─────────────────────────────────────────────
    $share_patterns = array(
        '.share'             => array('اشتراک‌گذاری', 'باکس دکمه‌های اشتراک‌گذاری', '85%'),
        '.social-share'      => array('شبکه اجتماعی', 'دکمه‌های شبکه اجتماعی', '88%'),
        '.share-box'         => array('باکس اشتراک‌گذاری', 'باکس اشتراک‌گذاری خبر', '88%'),
        '.share-buttons'     => array('دکمه‌های اشتراک', 'دکمه‌های share', '90%'),
        '.social-buttons'    => array('دکمه‌های اجتماعی', 'دکمه‌های شبکه‌های اجتماعی', '88%'),
        '[class*="share"]'   => array('اشتراک (عمومی)', 'هر المانی با کلاس حاوی share', '75%'),
        '.addtoany'          => array('AddToAny', 'پلاگین اشتراک‌گذاری AddToAny', '95%'),
        '.addthis_toolbox'   => array('AddThis', 'ابزار اشتراک‌گذاری AddThis', '97%'),
    );
    foreach ($share_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 4: نظرات — Comments
    // ─────────────────────────────────────────────
    $comment_patterns = array(
        '#comments'           => array('بخش نظرات (id)', 'بخش نظرات وردپرس', '92%'),
        '.comments-area'      => array('ناحیه نظرات', 'wrapper استاندارد نظرات', '90%'),
        '.comment-section'    => array('بخش نظرات', 'کلاس رایج بخش نظرات', '88%'),
        '#comment-respond'    => array('فرم نظر', 'فرم ارسال نظر وردپرس', '92%'),
        '.wp-comment-cookies-consent' => array('موافقت کوکی نظر', 'فرم موافقت کوکی نظرات', '90%'),
    );
    foreach ($comment_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 5: اطلاعات نویسنده — Author Box
    // ─────────────────────────────────────────────
    $author_patterns = array(
        '.author-box'      => array('باکس نویسنده', 'اطلاعات نویسنده', '85%'),
        '.about-author'    => array('درباره نویسنده', 'بخش درباره نویسنده', '85%'),
        '.author-profile'  => array('پروفایل نویسنده', 'پروفایل نویسنده خبر', '85%'),
        '[rel="author"]'   => array('نویسنده (rel)', 'لینک با rel=author', '78%'),
    );
    foreach ($author_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 6: ناوبری — Navigation/Breadcrumbs
    // ─────────────────────────────────────────────
    $nav_patterns = array(
        '.breadcrumb'         => array('مسیر پیمایشی', 'بردکرامب (مسیر ناوبری)', '82%'),
        '.breadcrumbs'        => array('مسیر پیمایشی', 'بردکرامب‌ها', '82%'),
        'nav.post-nav'        => array('ناوبری پست', 'ناوبری بین پست‌های قبل/بعد', '80%'),
        '.post-navigation'    => array('ناوبری خبر', 'بلوک ناوبری بین اخبار', '80%'),
        '.nav-links'          => array('لینک‌های ناوبری', 'لینک‌های ناوبری صفحات', '78%'),
        '.pagination'         => array('صفحه‌بندی', 'ناوبری صفحه‌بندی', '80%'),
    );
    foreach ($nav_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 7: عضویت و نوتیفیکیشن — Newsletter/Popups
    // ─────────────────────────────────────────────
    $newsletter_patterns = array(
        '.newsletter'         => array('خبرنامه', 'باکس عضویت در خبرنامه', '88%'),
        '.subscribe'          => array('اشتراک', 'فرم اشتراک', '85%'),
        '.notification-bar'   => array('نوار اطلاع‌رسانی', 'نوار اطلاع‌رسانی بالای صفحه', '83%'),
        '.cookie-notice'      => array('کوکی', 'اعلان حریم خصوصی کوکی', '90%'),
        '.popup'              => array('پاپ‌آپ', 'المان پاپ‌آپ', '85%'),
        '[class*="newsletter"]' => array('خبرنامه (عمومی)', 'کلاس حاوی newsletter', '80%'),
    );
    foreach ($newsletter_patterns as $sel => $info) {
        $add_if_found($sel, $info[0], $info[1], $info[2]);
    }

    // ─────────────────────────────────────────────
    // Strategy 8: پویای DOM — Class/ID Keyword Scan
    // بررسی فقط المان‌های درون بدنه خبر برای کلاس/ID‌های مشکوک
    // ─────────────────────────────────────────────
    $noise_keywords = array('ad', 'ads', 'adv', 'advert', 'sponsor', 'promo', 'banner',
        'popup', 'modal', 'overlay', 'widget', 'sidebar',
        'recommend', 'suggest', 'related', 'share', 'social', 'print', 'tag-cloud',
        'تبلیغ', 'تبلیغات', 'بنر', 'مرتبط', 'پیشنهاد', 'اشتراک',
        'adbox', 'adslot', 'adsense', 'tabligh', 'tablighat');

    $all_elements = @$xpath->query('.//*[@class or @id]', $body_node);
    $found_selectors = array();
    if ($all_elements) {
        foreach ($all_elements as $el) {
            $tag = strtolower($el->nodeName);
            if (in_array($tag, array('html', 'body', 'head', 'script', 'style', 'meta', 'link'))) continue;

            $el_class = strtolower($el->getAttribute('class'));
            $el_id    = strtolower($el->getAttribute('id'));

            foreach ($noise_keywords as $keyword) {
                $pattern = '/(^|[\s\-_])' . preg_quote($keyword, '/') . '([\s\-_]|$)/iu';
                $match_class = preg_match($pattern, $el_class);
                $match_id = preg_match($pattern, $el_id);

                if ($match_class || $match_id) {
                    // Build a simple selector
                    $class_parts = array_filter(array_map('trim', explode(' ', $el_class)));
                    // Prefer a class that contains the keyword
                    $matching_class = '';
                    foreach ($class_parts as $cp) {
                        if (preg_match($pattern, $cp)) {
                            $matching_class = $cp;
                            break;
                        }
                    }
                    if (!empty($el_id) && preg_match($pattern, $el_id)) {
                        $sel = $tag . '#' . preg_replace('/[^a-zA-Z0-9_-]/', '', $el_id);
                    } elseif (!empty($matching_class)) {
                        $sel = $tag . '.' . preg_replace('/[^a-zA-Z0-9_-]/', '', $matching_class);
                    } else {
                        continue;
                    }

                    if (!isset($found_selectors[$sel])) {
                        $found_selectors[$sel] = true;
                        $candidates[] = array(
                            'selector'   => $sel,
                            'label'      => 'شناسایی هوشمند (' . $keyword . ')',
                            'reason'     => 'المان ' . $tag . ' با کلمه کلیدی «' . $keyword . '» در کلاس یا شناسه',
                            'confidence' => '70%',
                            'count'      => 1,
                        );
                    }
                    break;
                }
            }
        }
    }

    // Sort by confidence descending
    usort($candidates, function($a, $b) {
        return intval($b['confidence']) - intval($a['confidence']);
    });

    // Remove duplicates by selector
    $seen   = array();
    $unique = array();
    foreach ($candidates as $c) {
        if (!isset($seen[$c['selector']])) {
            $seen[$c['selector']] = true;
            $unique[] = $c;
        }
    }

    wp_send_json_success(array(
        'escape_candidates' => array_values(array_slice($unique, 0, 20)),
    ));
}

// Register administrative action for proxied page
add_action('admin_post_cop_proxy_page', 'cop_handle_proxy_page');

function cop_handle_proxy_page() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز.');
    }

    $url = isset($_GET['sample_url']) ? esc_url_raw($_GET['sample_url']) : '';
    if (empty($url)) {
        wp_die('آدرس نمونه ارسال نشده است.');
    }

    $html = cop_fetch_html_for_assistant($url);
    if (is_wp_error($html)) {
        wp_die('خطا در بارگیری صفحه مقصد: ' . esc_html($html->get_error_message()));
    }

    // Rewrite relative URLs to absolute based on target domain to ensure CSS/Images load
    $parsed_url = parse_url($url);
    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
    
    $html = preg_replace('/href="\/([^\/])/i', 'href="' . $base_url . '/$1', $html);
    $html = preg_replace('/src="\/([^\/])/i', 'src="' . $base_url . '/$1', $html);
    
    // Prevent frame busting by replacing window.top and top.location with self equivalents
    $html = preg_replace('/window\.top\b/i', 'window.self', $html);
    $html = preg_replace('/top\.location\b/i', 'self.location', $html);
    
    // Inject custom Inspector styles and script
    $inspector_style = '
    <style>
        /* Highlight border on hover */
        .cop-inspector-hover {
            outline: 2px dashed #4f46e5 !important;
            outline-offset: -1px !important;
            background-color: rgba(79, 70, 229, 0.08) !important;
            cursor: crosshair !important;
        }
        /* Sticky indicator tooltip */
        #cop-inspector-tooltip {
            position: fixed;
            background: #1e293b;
            color: #f8fafc;
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
            z-index: 2147483647;
            pointer-events: none;
            font-family: monospace;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            direction: ltr;
        }
    </style>
    ';

    $inspector_script = '
    <script>
    (function() {
        var tooltip = document.createElement("div");
        tooltip.id = "cop-inspector-tooltip";
        tooltip.style.display = "none";
        document.body.appendChild(tooltip);

        var lastActiveElement = null;

        document.addEventListener("mouseover", function(e) {
            if (e.target.id === "cop-inspector-tooltip") return;
            
            if (lastActiveElement) {
                lastActiveElement.classList.remove("cop-inspector-hover");
            }
            
            lastActiveElement = e.target;
            lastActiveElement.classList.add("cop-inspector-hover");

            var alternatives = getAlternativeSelectors(lastActiveElement);
            var selector = alternatives[0] ? alternatives[0].selector : lastActiveElement.tagName.toLowerCase();
            tooltip.textContent = selector;
            tooltip.style.display = "block";
        }, true);

        document.addEventListener("mousemove", function(e) {
            tooltip.style.left = (e.clientX + 10) + "px";
            tooltip.style.top = (e.clientY + 10) + "px";
        }, true);

        document.addEventListener("mouseout", function(e) {
            if (lastActiveElement) {
                lastActiveElement.classList.remove("cop-inspector-hover");
            }
            tooltip.style.display = "none";
        }, true);

        document.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();

            var alternatives = getAlternativeSelectors(e.target);
            
            window.parent.postMessage({
                action: "cop_visual_clicked_options",
                selectors: alternatives
            }, "*");
        }, true);

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                window.parent.postMessage({
                    action: "cop_visual_cancel"
                }, "*");
            }
        }, true);

        function getAlternativeSelectors(el) {
            var options = [];
            var current = el;
            var depth = 0;
            
            var depthLabels = {
                0: "المان دقیق کلیک‌شده",
                1: "۱ مرحله بالاتر (والد مستقیم)",
                2: "۲ مرحله بالاتر (والد دوم)",
                3: "۳ مرحله بالاتر (والد سوم)"
            };

            while (current && current.nodeType === Node.ELEMENT_NODE && current.tagName.toLowerCase() !== "body" && depth < 4) {
                var suffix = " [" + (depthLabels[depth] || (depth + " مرحله بالاتر")) + " - " + current.tagName.toLowerCase() + "]";
                
                // 1. Direct ID
                if (current.id) {
                    options.push({
                        selector: "#" + current.id,
                        label: "شناسه مستقیم" + suffix,
                        desc: "آدرس منحصربه‌فرد بر اساس شناسه المان " + current.tagName.toLowerCase()
                    });
                }

                // 2. Semantic Class Path
                var semantic = buildSemanticPath(current);
                if (semantic) {
                    options.push({
                        selector: semantic,
                        label: "مسیر کلاس معنایی" + suffix,
                        desc: "مسیر بهینه‌سازی شده بدون کلاس‌های عمومی و سودوکلاس‌ها"
                    });
                }

                // 3. Full Class Path
                var full = buildFullPath(current, true);
                if (full && full !== semantic) {
                    options.push({
                        selector: full,
                        label: "مسیر کلاس‌های کامل" + suffix,
                        desc: "مسیر کامل شامل تمام کلاس‌های CSS المان و اجداد آن"
                    });
                }

                // 4. Hierarchical Tags Path
                var tagPath = buildFullPath(current, false);
                if (tagPath) {
                    options.push({
                        selector: tagPath,
                        label: "مسیر تگ‌های والد" + suffix,
                        desc: "مسیر درختی ساده مبتنی بر نام تگ‌های والد"
                    });
                }

                current = current.parentNode;
                depth++;
            }

            var unique = [];
            var seen = {};
            options.forEach(function(opt) {
                if (!seen[opt.selector]) {
                    seen[opt.selector] = true;
                    unique.push(opt);
                }
            });

            return unique;
        }

        function buildFullPath(el, includeClasses) {
            var path = [];
            while (el && el.nodeType === Node.ELEMENT_NODE) {
                var selector = el.tagName.toLowerCase();
                if (includeClasses) {
                    var className = el.className.replace(/\s+/g, " ").trim();
                    className = className.replace("cop-inspector-hover", "").trim();
                    if (className) {
                        selector += "." + className.split(" ").join(".");
                    }
                }
                
                var sibling = el, nth = 1;
                while (sibling = sibling.previousElementSibling) {
                    if (sibling.tagName.toLowerCase() === el.tagName.toLowerCase()) {
                        nth++;
                    }
                }
                if (nth > 1) {
                    selector += ":nth-of-type(" + nth + ")";
                }
                
                path.unshift(selector);
                el = el.parentNode;
            }
            return path.join(" > ");
        }

        function buildSemanticPath(el) {
            var path = [];
            while (el && el.nodeType === Node.ELEMENT_NODE) {
                var selector = el.tagName.toLowerCase();
                if (el.id) {
                    if (!/\d+/.test(el.id)) {
                        selector += "#" + el.id;
                        path.unshift(selector);
                        break;
                    }
                }
                var className = el.className.replace(/\s+/g, " ").trim();
                className = className.replace("cop-inspector-hover", "").trim();
                if (className) {
                    var classes = className.split(" ");
                    var semanticClasses = classes.filter(function(c) {
                        var utilityPatterns = /^(p|m|pt|pb|pl|pr|px|py|mt|mb|ml|mr|mx|my|text|bg|border|flex|grid|items|justify|w|h|max|min|rounded|shadow|transition|duration|ease|col|row)-|^(flex|grid|hidden|block|inline)$/;
                        return !utilityPatterns.test(c);
                    });
                    if (semanticClasses.length > 0) {
                        selector += "." + semanticClasses.join(".");
                    }
                }
                
                var sibling = el, nth = 1;
                while (sibling = sibling.previousElementSibling) {
                    if (sibling.tagName.toLowerCase() === el.tagName.toLowerCase()) {
                        nth++;
                    }
                }
                if (nth > 1) {
                    selector += ":nth-of-type(" + nth + ")";
                }
                
                path.unshift(selector);
                el = el.parentNode;
            }
            return path.join(" > ");
        }
    })();
    </script>
    ';

    if (strpos($html, "</body>") !== false) {
        $html = str_replace("</body>", $inspector_style . $inspector_script . "</body>", $html);
    } else {
        $html .= $inspector_style . $inspector_script;
    }

    echo $html;
    exit;
}

function cop_ajax_discover_feed_url() {
    check_ajax_referer('cop_selector_assistant_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'دسترسی غیرمجاز.'));
    }

    $root_url = isset($_POST['source_root_link']) ? esc_url_raw($_POST['source_root_link']) : '';
    if (empty($root_url)) {
        wp_send_json_error(array('message' => 'آدرس اصلی دامنه (Source root Link) وارد نشده است.'));
    }

    $root_url = rtrim($root_url, '/');
    $parsed_url = parse_url($root_url);
    if (!isset($parsed_url['scheme']) || !isset($parsed_url['host'])) {
        wp_send_json_error(array('message' => 'فرمت آدرس دامنه نامعتبر است.'));
    }
    
    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

    $html = cop_fetch_html_for_assistant($base_url);
    $candidates = [];

    if (!is_wp_error($html)) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $links = $xpath->query('//link[@rel="alternate"]');
        if ($links && $links->length > 0) {
            foreach ($links as $link) {
                $type = $link->getAttribute('type');
                $href = $link->getAttribute('href');
                if (in_array($type, array('application/rss+xml', 'application/atom+xml', 'text/xml')) && !empty($href)) {
                    if (strpos($href, 'http') !== 0) {
                        $href = $base_url . '/' . ltrim($href, '/');
                    }
                    $candidates[] = $href;
                }
            }
        }
    }

    $guess_paths = array(
        '/feed/',
        '/rss/',
        '/rss.xml',
        '/feed.xml',
        '/index.xml',
        '/fa/rss/',
        '/fa/news/rss/',
        '/rss/news/'
    );

    foreach ($guess_paths as $path) {
        $candidates[] = $base_url . $path;
    }

    $candidates = array_unique($candidates);
    $validated_feeds = [];

    foreach ($candidates as $feed_url) {
        if (cop_validate_rss_feed($feed_url)) {
            $validated_feeds[] = $feed_url;
        }
    }

    if (empty($validated_feeds)) {
        wp_send_json_error(array('message' => 'هیچ فید RSS معتبری در این سایت یافت نشد. لطفاً آدرس را به صورت دستی بررسی کنید.'));
    }

    wp_send_json_success(array('feeds' => array_values($validated_feeds)));
}

function cop_validate_rss_feed($url) {
    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $status = wp_remote_retrieve_response_code($response);
    if ($status != 200) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        return false;
    }

    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($body);
    if ($xml === false) {
        libxml_clear_errors();
        return false;
    }
    libxml_use_internal_errors(false);

    $has_items = false;
    if (isset($xml->channel->item)) {
        $has_items = count($xml->channel->item) > 0;
    } elseif (isset($xml->entry)) {
        $has_items = count($xml->entry) > 0;
    }

    return $has_items;
}

