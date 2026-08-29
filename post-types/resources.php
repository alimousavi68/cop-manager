<?php
// Register Custom Post Type
function resouces_post_type()
{

    $labels = array(
        'name' => _x('منابع', 'Post Type General Name', 'i8_publisher_copilot'),
        'singular_name' => _x('منبع', 'Post Type Singular Name', 'i8_publisher_copilot'),
        'menu_name' => __('منابع خبری', 'i8_publisher_copilot'),
        'name_admin_bar' => __('منابع خبری', 'i8_publisher_copilot'),
        'archives' => __('آرشیو منابع', 'i8_publisher_copilot'),
        'attributes' => __('خصوصیات منبع', 'i8_publisher_copilot'),
        'parent_item_colon' => __('مادر', 'i8_publisher_copilot'),
        'all_items' => __('همه منابع', 'i8_publisher_copilot'),
        'add_new_item' => __('افزودن منبع', 'i8_publisher_copilot'),
        'add_new' => __('افزودن جدید', 'i8_publisher_copilot'),
        'new_item' => __('منبع جدید', 'i8_publisher_copilot'),
        'edit_item' => __('ویرایش منبع', 'i8_publisher_copilot'),
        'update_item' => __('به روزرسانی منبع', 'i8_publisher_copilot'),
        'view_item' => __('نمایش منبع', 'i8_publisher_copilot'),
        'view_items' => __('نمایش منابع', 'i8_publisher_copilot'),
        'search_items' => __('جستجوی منبع', 'i8_publisher_copilot'),
        'not_found' => __('پیدا نشد', 'i8_publisher_copilot'),
        'not_found_in_trash' => __('در زباله دان پیدا نشد', 'i8_publisher_copilot'),
        'featured_image' => __('تصویر لوگو', 'i8_publisher_copilot'),
        'set_featured_image' => __('تنظیم تصویر لوگو', 'i8_publisher_copilot'),
        'remove_featured_image' => __('حذف تصویر لوگو', 'i8_publisher_copilot'),
        'use_featured_image' => __('استفاده از تصویر لوگو', 'i8_publisher_copilot'),
        'insert_into_item' => __('درج در منبع', 'i8_publisher_copilot'),
        'uploaded_to_this_item' => __('در این منبع آپلود شد', 'i8_publisher_copilot'),
        'items_list' => __('لیست منابع', 'i8_publisher_copilot'),
        'items_list_navigation' => __('پیمایش فهرست منابع', 'i8_publisher_copilot'),
        'filter_items_list' => __('لیست منابع را فیلتر کنید', 'i8_publisher_copilot'),
    );
    $args = array(
        'label' => __('resource', 'i8_publisher_copilot'),
        'description' => __('منابع خبری', 'i8_publisher_copilot'),
        'labels' => $labels,
        'supports' => array('title', 'thumbnail', 'custom-fields'),
        'taxonomies' => array('category', 'post_tag'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-site',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => true,
        'publicly_queryable' => true,
        'capability_type' => 'page',
    );
    register_post_type('resource', $args);

}
add_action('init', 'resouces_post_type', 0);


// Helper: Flatten and normalize escape elements
function cop_flatten_escape_elements($input) {
    $result = array();
    if (empty($input)) return $result;
    
    $items = is_array($input) ? $input : array($input);
    foreach ($items as $item) {
        if (!is_string($item)) continue;
        $item = trim(wp_unslash($item));
        if (empty($item)) continue;
        
        if (substr($item, 0, 1) === '[' && substr($item, -1) === ']') {
            $decoded = json_decode($item, true);
            if (is_array($decoded)) {
                $result = array_merge($result, cop_flatten_escape_elements($decoded));
                continue;
            }
        }
        
        if (strpos($item, "\n") !== false) {
            $lines = explode("\n", $item);
            $result = array_merge($result, cop_flatten_escape_elements($lines));
            continue;
        }
        
        $result[] = $item;
    }
    
    return array_values(array_unique(array_filter($result)));
}

// Post meta
function custom_meta_box()
{
    add_meta_box(
        'custom_meta_box',
        'Custom Fields',
        'display_custom_meta_box',
        'resource',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'custom_meta_box');

function display_custom_meta_box($post)
{
    // Retrieve saved meta values

    $title_selector = get_post_meta($post->ID, 'title_selector', true);
    $img_selector = get_post_meta($post->ID, 'img_selector', true);
    $lead_selector = get_post_meta($post->ID, 'lead_selector', true);
    $body_selector = get_post_meta($post->ID, 'body_selector', true);
    $bup_date_selector = get_post_meta($post->ID, 'bup_date_selector', true);
    $category_selector = get_post_meta($post->ID, 'category_selector', true);
    $tags_selector = get_post_meta($post->ID, 'tags_selector', true);
    $escape_elements = get_post_meta($post->ID, 'escape_elements', true);
    $source_root_link = get_post_meta($post->ID, 'source_root_link', true);
    $source_feed_link = get_post_meta($post->ID, 'source_feed_link', true);
    $cop_sample_url = get_post_meta($post->ID, 'cop_sample_url', true);

    wp_nonce_field('cop_save_resource_meta', 'cop_resource_nonce');
    ?>
    <!-- Injection of Selector Assistant -->
    <div class="cop-selector-assistant-box" style="direction: rtl; text-align: right; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px; margin-bottom: 25px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
        <h3 style="margin-top: 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">🧙‍♂️ دستیار هوشمند تنظیم سلکتورها</h3>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;">با انتخاب الگوی سیستم مدیریت محتوا (CMS)، مقادیر فیلدها را پر کنید یا آدرس یک خبر نمونه را برای یافتن خودکار سلکتورها و تست زنده وارد نمایید.</p>
        
        <div style="margin-bottom: 15px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px;">انتخاب الگوی آماده CMS مقصد:</label>
            <select id="cop_cms_preset" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; background: #fff;">
                <option value="">-- یک مورد را انتخاب کنید --</option>
                <option value="wordpress">وردپرس (WordPress)</option>
                <option value="iransamaneh">ایران سامانه (IranSamaneh)</option>
                <option value="asam">آسام (Asam)</option>
            </select>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px;">آدرس یک خبر نمونه جهت تست:</label>
            <input type="text" id="cop_sample_url" name="cop_sample_url" value="<?php echo esc_attr($cop_sample_url); ?>" placeholder="https://example.com/news/123" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; direction: ltr; font-size: 13px;" />
        </div>
        
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <button type="button" id="cop_btn_suggest" class="button button-secondary" style="flex: 1; height: 35px;">🔍 پیشنهاد خودکار سلکتورها</button>
            <button type="button" id="cop_btn_test" class="button button-primary" style="flex: 1; height: 35px;">⚡ تست لحظه‌ای سلکتورها</button>
        </div>

        <!-- Suggestions Container -->
        <div id="cop_suggestions_wrapper" style="display: none; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
            <h4 style="margin-top: 0; color: #1e293b; font-size: 13px; margin-bottom: 10px;">💡 سلکتورهای پیشنهادی هوشمند (برای اعمال، روی آن‌ها کلیک کنید):</h4>
            <div id="cop_suggestions_list" style="font-size: 13px;"></div>
        </div>

        <!-- Test Results Container -->
        <div id="cop_test_results_wrapper" style="display: none; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px;">
            <h4 style="margin-top: 0; color: #1e293b; font-size: 13px; margin-bottom: 10px;">📋 نتایج استخراج آزمایشی:</h4>
            <div id="cop_test_results" style="font-size: 13px; line-height: 1.6;"></div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Preset selector logic
        $('#cop_cms_preset').on('change', function() {
            var val = $(this).val();
            if (val === 'wordpress') {
                $('#title_selector').val('h1.entry-title, h1.post-title');
                $('#body_selector').val('.entry-content, .post-content, article');
                $('#img_selector').val('.post-thumbnail img, .entry-content img');
                $('#lead_selector').val('.entry-summary, .post-excerpt');
            } else if (val === 'iransamaneh') {
                $('#title_selector').val('.title, h1.title');
                $('#body_selector').val('.body, .text');
                $('#img_selector').val('.image img');
                $('#lead_selector').val('.lead');
            } else if (val === 'asam') {
                $('#title_selector').val('.news_title, h1');
                $('#body_selector').val('.news_body, .news_text');
                $('#img_selector').val('.news_img img');
                $('#lead_selector').val('.news_lead');
            }
        });

        // Auto-suggest logic
        $('#cop_btn_suggest').on('click', function(e) {
            e.preventDefault();
            var sampleUrl = $('#cop_sample_url').val();
            if (!sampleUrl) {
                alert('لطفاً آدرس یک خبر نمونه را وارد کنید.');
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('در حال تجزیه و تحلیل صفحه...');
            $('#cop_suggestions_wrapper').hide();

            $.post(ajaxurl, {
                action: 'cop_suggest_selectors',
                sample_url: sampleUrl,
                security: '<?php echo wp_create_nonce("cop_selector_assistant_nonce"); ?>'
            }, function(response) {
                $btn.prop('disabled', false).text('🔍 پیشنهاد خودکار سلکتورها');
                if (response.success) {
                    var suggestions = response.data;
                    var html = '';
                    var types = { title: 'عنوان', body: 'بدنه خبر', lead: 'لید/خلاصه', image: 'تصویر شاخص' };
                    var fields = { title: '#title_selector', body: '#body_selector', lead: '#lead_selector', image: '#img_selector' };
                    
                    for (var key in types) {
                        html += '<div style="margin-bottom: 10px;"><strong>' + types[key] + ':</strong> ';
                        if (suggestions[key] && suggestions[key].length > 0) {
                            suggestions[key].forEach(function(item) {
                                html += '<span class="cop-suggest-item" data-field="' + fields[key] + '" data-value="' + item.selector + '" style="display: inline-block; background: #e0e7ff; color: #4f46e5; padding: 3px 8px; border-radius: 6px; margin: 4px; cursor: pointer; font-family: monospace; font-size:12px;" title="' + item.reason + ' (اطمینان: ' + item.confidence + ')">' + item.selector + ' (' + item.confidence + ') ⓘ</span> ';
                            });
                        } else {
                            html += '<span style="color:#888;">پیشنهادی یافت نشد.</span>';
                        }
                        html += '</div>';
                    }
                    $('#cop_suggestions_list').html(html);
                    $('#cop_suggestions_wrapper').slideDown();
                } else {
                    alert(response.data.message || 'خطایی رخ داد.');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('🔍 پیشنهاد خودکار سلکتورها');
                alert('ارتباط با سرور برقرار نشد.');
            });
        });

        // Click suggestion to apply
        $(document).on('click', '.cop-suggest-item', function() {
            var field = $(this).data('field');
            var val = $(this).data('value');
            $(field).val(val);
        });

        // Test selectors logic
        $('#cop_btn_test').on('click', function(e) {
            e.preventDefault();
            var sampleUrl = $('#cop_sample_url').val();
            if (!sampleUrl) {
                alert('لطفاً آدرس یک خبر نمونه را وارد کنید.');
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('در حال واکشی و استخراج...');
            $('#cop_test_results_wrapper').hide();

            $.post(ajaxurl, {
                action: 'cop_test_selectors',
                sample_url: sampleUrl,
                title_selector: $('#title_selector').val(),
                lead_selector: $('#lead_selector').val(),
                body_selector: $('#body_selector').val(),
                img_selector: $('#img_selector').val(),
                security: '<?php echo wp_create_nonce("cop_selector_assistant_nonce"); ?>'
            }, function(response) {
                $btn.prop('disabled', false).text('⚡ تست لحظه‌ای سلکتورها');
                if (response.success) {
                    var r = response.data;
                    var html = '<table style="width:100%; border-collapse:collapse; text-align:right;">';
                    var items = [
                        { label: 'عنوان', status: r.title_status, val: r.extracted.title },
                        { label: 'لید/خلاصه', status: r.lead_status, val: r.extracted.lead },
                        { label: 'متن بدنه', status: r.body_status, val: r.extracted.body },
                        { label: 'تصویر شاخص', status: r.img_status, val: r.extracted.img_src, isImg: true }
                    ];
                    
                    items.forEach(function(item) {
                        var statusBadge = '';
                        if (item.status === 'success') {
                            statusBadge = '<span style="background:#d1fae5; color:#065f46; padding:2px 6px; border-radius:4px; font-weight:bold; font-size:11px;">موفق</span>';
                        } else if (item.status === 'failed') {
                            statusBadge = '<span style="background:#fee2e2; color:#991b1b; padding:2px 6px; border-radius:4px; font-weight:bold; font-size:11px;">ناموفق</span>';
                        } else {
                            statusBadge = '<span style="background:#f3f4f6; color:#374151; padding:2px 6px; border-radius:4px; font-weight:bold; font-size:11px;">نادیده گرفته شد</span>';
                        }
                        
                        var valHtml = '-';
                        if (item.val) {
                            if (item.isImg) {
                                valHtml = '<img src="' + item.val + '" style="max-height:80px; border-radius:4px; margin-top:5px; display:block;" /><span style="font-size:10px; color:#64748b; font-family:monospace; word-break:break-all;">' + item.val + '</span>';
                            } else {
                                var displayVal = item.val;
                                if (displayVal.length > 150) {
                                    displayVal = displayVal.substring(0, 150) + '...';
                                }
                                valHtml = '<span style="color:#334155; font-weight:500;">' + displayVal + '</span>';
                            }
                        }
                        
                        html += '<tr style="border-bottom:1px solid #f1f5f9;">' +
                                '<td style="padding:10px 5px; font-weight:bold; width:100px;">' + item.label + '</td>' +
                                '<td style="padding:10px 5px; width:120px;">' + statusBadge + '</td>' +
                                '<td style="padding:10px 5px;">' + valHtml + '</td>' +
                                '</tr>';
                    });
                    
                    html += '</table>';
                    $('#cop_test_results').html(html);
                    $('#cop_test_results_wrapper').slideDown();
                } else {
                    alert(response.data.message || 'خطایی رخ داد.');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('⚡ تست لحظه‌ای سلکتورها');
                alert('ارتباط با سرور برقرار نشد.');
            });
        });

        // Visual selector logic
        var activeVisualTarget = null;
        var activeVisualLabel = '';

        $('.cop-btn-visual-select').on('click', function(e) {
            e.preventDefault();
            var sampleUrl = $('#cop_sample_url').val();
            if (!sampleUrl) {
                alert('لطفاً ابتدا آدرس یک خبر نمونه را در کادر دستیار مانیتورینگ وارد کنید.');
                return;
            }

            activeVisualTarget = $(this).data('target');
            
            var labels = {
                '#title_selector': 'عنوان خبر',
                '#lead_selector': 'خلاصه / لید خبر',
                '#body_selector': 'متن اصلی بدنه',
                '#img_selector': 'تصویر شاخص'
            };
            activeVisualLabel = labels[activeVisualTarget] || 'سلکتور';
            $('#cop-modal-active-field-label').text(activeVisualLabel);

            var proxyUrl = '<?php echo admin_url("admin-post.php?action=cop_proxy_page"); ?>&sample_url=' + encodeURIComponent(sampleUrl);
            $('#cop-visual-iframe').attr('src', proxyUrl);

            $('#cop-visual-modal').fadeIn(200);
        });

        function closeVisualModal() {
            $('#cop-visual-modal').fadeOut(200, function() {
                $('#cop-visual-iframe').attr('src', '');
                $('#cop-selector-choices-container').hide();
                $('#cop-selector-choices-list').empty();
            });
            activeVisualTarget = null;
        }

        $('#cop-modal-cancel').on('click', function(e) {
            e.preventDefault();
            closeVisualModal();
        });

        window.addEventListener('message', function(e) {
            if (e.data && e.data.action === 'cop_visual_clicked_options') {
                var selectors = e.data.selectors;
                
                var html = '';
                selectors.forEach(function(opt) {
                    html += '<div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 8px; direction: rtl; text-align: right;">' +
                            '  <div style="flex: 1; padding-left: 15px;">' +
                            '    <strong style="color: #1e293b; font-size: 13px;">' + opt.label + ':</strong> ' +
                            '    <span style="font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; margin-right: 10px; color: #4f46e5; word-break: break-all; direction: ltr; display: inline-block;">' + opt.selector + '</span>' +
                            '    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">' + opt.desc + '</div>' +
                            '  </div>' +
                            '  <button type="button" class="button button-primary cop-btn-apply-selector" data-value="' + opt.selector + '" style="font-size: 12px; height: 30px; line-height: 28px;">انتخاب این مورد</button>' +
                            '</div>';
                });
                
                $('#cop-selector-choices-list').html(html);
                $('#cop-selector-choices-container').slideDown(200);
            } else if (e.data && e.data.action === 'cop_visual_cancel') {
                closeVisualModal();
            }
        });

        $(document).on('click', '.cop-btn-apply-selector', function(e) {
            e.preventDefault();
            var selector = $(this).data('value');
            if (activeVisualTarget) {
                $(activeVisualTarget).val(selector).css('background', '#d1fae5');
                setTimeout(function() {
                    $(activeVisualTarget).css('background', '#fff');
                }, 1000);
                closeVisualModal();
            }
        });

        // Auto-discover RSS feed link
        $('#cop_btn_discover_feed').on('click', function(e) {
            e.preventDefault();
            var sourceRoot = $('#source_root_link').val();
            if (!sourceRoot) {
                alert('لطفاً ابتدا آدرس اصلی دامنه (Source root Link) را وارد کنید.');
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('در حال کشف فیدهای معتبر...');
            $('#cop_discovered_feeds_wrapper').hide();

            $.post(ajaxurl, {
                action: 'cop_discover_feed_url',
                source_root_link: sourceRoot,
                security: '<?php echo wp_create_nonce("cop_selector_assistant_nonce"); ?>'
            }, function(response) {
                $btn.prop('disabled', false).text('🔍 کشف خودکار فید');
                if (response.success) {
                    var feeds = response.data.feeds;
                    var html = '';
                    feeds.forEach(function(url) {
                        html += '<div style="margin-bottom: 6px;">' +
                                '  <span class="cop-feed-discovered-item" data-url="' + url + '" style="display: inline-block; background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-family: monospace; font-size: 12px; border: 1px solid #a7f3d0;" title="کلیک کنید تا در کادر اعمال شود">' + url + '</span>' +
                                '</div>';
                    });
                    $('#cop_discovered_feeds_list').html(html);
                    $('#cop_discovered_feeds_wrapper').slideDown(200);
                } else {
                    alert(response.data.message || 'فید معتبری یافت نشد.');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('🔍 کشف خودکار فید');
                alert('ارتباط با سرور برقرار نشد.');
            });
        });

        $(document).on('click', '.cop-feed-discovered-item', function() {
            var url = $(this).data('url');
            $('#source_feed_link').val(url).css('background', '#d1fae5');
            setTimeout(function() {
                $('#source_feed_link').css('background', '#fff');
            }, 1000);
            $('#cop_discovered_feeds_wrapper').slideUp(200);
        });

        // ═══════════════════════════════════════════════════════════
        // Escape Elements Pill Manager
        // ═══════════════════════════════════════════════════════════
        var $pillsContainer = $('#cop-escape-pills');
        var $emptyHint      = $('#cop-escape-empty-hint');

        // --- Helper: escape HTML attributes ---
        function escapeHtmlAttr(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // --- Helper: add a pill ---
        function addEscapePill(selector) {
            selector = selector.trim();
            if (!selector) return;

            // Dedup check
            var exists = false;
            $pillsContainer.find('input[name="escape_elements[]"]').each(function() {
                if ($(this).val() === selector) { exists = true; }
            });
            if (exists) {
                // Flash existing pill
                $pillsContainer.find('.cop-escape-pill').each(function() {
                    if ($(this).find('input').val() === selector) {
                        $(this).animate({opacity: 0.3}, 200).animate({opacity: 1}, 200);
                    }
                });
                return;
            }

            // Warn if selector is too generic
            var genericSelectors = ['div', 'span', 'p', 'a', 'ul', 'li', 'section', 'article', 'main'];
            if (genericSelectors.indexOf(selector.toLowerCase()) !== -1) {
                if (!confirm('⚠️ سلکتور «' + selector + '» خیلی عام است و ممکن است محتوای اصلی را هم حذف کند.\nآیا مطمئن هستید؟')) return;
            }

            $emptyHint.hide();
            var $pill = $('<span class="cop-escape-pill" style="display: inline-flex; align-items: center; gap: 6px; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-family: monospace; direction: ltr;">' +
                escapeHtmlAttr(selector) +
                '<button type="button" class="cop-escape-pill-remove" style="background:none;border:none;cursor:pointer;color:#b91c1c;padding:0;line-height:1;font-size:14px;display:flex;" title="حذف">×</button>' +
                '<input type="hidden" name="escape_elements[]" value="' + escapeHtmlAttr(selector) + '">' +
                '</span>');
            $pill.hide().appendTo($pillsContainer).fadeIn(200);

            // Mark suggested chip as added
            $('#cop-escape-suggest-list').find('[data-selector="' + escapeHtmlAttr(selector) + '"]').addClass('cop-escape-chip-added')
                .css({background: '#d1fae5', color: '#065f46', borderColor: '#6ee7b7', cursor: 'default', opacity: 0.7});
        }

        // --- Helper: sync empty hint ---
        function syncEmptyHint() {
            var hasPills = $pillsContainer.find('.cop-escape-pill').length > 0;
            $emptyHint.toggle(!hasPills);
        }

        // Remove pill
        $(document).on('click', '.cop-escape-pill-remove', function() {
            $(this).closest('.cop-escape-pill').fadeOut(200, function() {
                $(this).remove();
                syncEmptyHint();
            });
        });

        // Manual add via button or Enter
        $('#cop-escape-add-btn').on('click', function() {
            var val = $('#cop-escape-manual-input').val().trim();
            if (val) { addEscapePill(val); $('#cop-escape-manual-input').val(''); }
        });
        $('#cop-escape-manual-input').on('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#cop-escape-add-btn').trigger('click'); }
        });

        // --- Smart Suggest ---
        $('#cop_btn_suggest_escape').on('click', function(e) {
            e.preventDefault();
            var sampleUrl = $('#cop_sample_url').val();
            var bodySelector = $('#body_selector').val();

            if (!sampleUrl) {
                alert('لطفاً ابتدا آدرس یک خبر نمونه را در کادر دستیار بالا وارد کنید.');
                return;
            }

            if (!bodySelector) {
                alert('لطفاً ابتدا Body Selector (سلکتور بدنه خبر) را مشخص کنید تا المان‌های درون آن آنالیز شوند.');
                $('#body_selector').focus().css('background', '#fef2f2');
                setTimeout(function() { $('#body_selector').css('background', '#fff'); }, 2000);
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).html('<svg style="width:14px;height:14px;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> در حال تحلیل بدنه...');
            $('#cop-escape-suggest-wrapper').hide();

            $.post(ajaxurl, {
                action: 'cop_suggest_escape_elements',
                sample_url: sampleUrl,
                body_selector: bodySelector,
                security: '<?php echo wp_create_nonce("cop_selector_assistant_nonce"); ?>'
            }, function(response) {
                $btn.prop('disabled', false).html('<svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg> پیشنهاد هوشمند');

                if (response.success && response.data.escape_candidates && response.data.escape_candidates.length > 0) {
                    var html = '';
                    response.data.escape_candidates.forEach(function(item) {
                        var isAdded = false;
                        $pillsContainer.find('input[name="escape_elements[]"]').each(function() {
                            if ($(this).val() === item.selector) { isAdded = true; }
                        });
                        var addedStyle = isAdded ? 'background:#d1fae5;color:#065f46;border-color:#6ee7b7;cursor:default;opacity:0.7;' : '';
                        html += '<span class="cop-escape-chip" data-selector="' + escapeHtmlAttr(item.selector) + '" style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:16px;font-size:11px;font-family:monospace;border:1px solid #fca5a5;background:#fff0f0;color:#991b1b;cursor:pointer;direction:ltr;' + addedStyle + '" title="' + escapeHtmlAttr(item.reason + ' | اطمینان: ' + item.confidence) + '">' +
                            '<span style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtmlAttr(item.selector) + '</span>' +
                            '<span style="font-family:sans-serif;font-size:10px;background:rgba(0,0,0,0.08);padding:1px 5px;border-radius:10px;">' + item.confidence + '</span>' +
                            '</span>';
                    });
                    $('#cop-escape-suggest-list').html(html);
                    $('#cop-escape-suggest-wrapper').slideDown(200);
                } else if (response.success) {
                    alert('هیچ المان قابل حذف مشکوکی در این صفحه یافت نشد. صفحه نمونه را بررسی کنید یا سلکتور را به صورت دستی اضافه کنید.');
                } else {
                    alert(response.data.message || 'خطا در تحلیل صفحه');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('✨ پیشنهاد هوشمند');
                alert('ارتباط با سرور برقرار نشد.');
            });
        });

        // Click on suggested chip → add to pills
        $(document).on('click', '.cop-escape-chip:not(.cop-escape-chip-added)', function() {
            addEscapePill($(this).data('selector'));
        });

        // --- Visual Escape Mode ---
        // Uses the existing visual iframe but in "escape mode"
        // Click → adds to escape list instead of replacing a field
        var isEscapeMode = false;

        $('.cop-btn-visual-select-escape').on('click', function(e) {
            e.preventDefault();
            var sampleUrl = $('#cop_sample_url').val();
            if (!sampleUrl) {
                alert('لطفاً ابتدا آدرس یک خبر نمونه را وارد کنید.');
                return;
            }
            isEscapeMode = true;
            $('#cop-modal-active-field-label').text('🚫 حذف المان').css('background', '#dc2626');
            var proxyUrl = '<?php echo admin_url("admin-post.php?action=cop_proxy_page"); ?>&sample_url=' + encodeURIComponent(sampleUrl);
            $('#cop-visual-iframe').attr('src', proxyUrl);
            $('#cop-visual-modal').fadeIn(200);
        });

        // Patch the existing message handler to handle escape mode
        // We re-listen with a flag — original handler is in window.addEventListener above,
        // but we intercept via a custom check in the existing handler flow.
        // For clean separation, we add our own listener with a mode check.
        window.addEventListener('message', function(eMsg) {
            if (!isEscapeMode) return; // handled by original listener
            if (eMsg.data && eMsg.data.action === 'cop_visual_clicked_options' && isEscapeMode) {
                var selectors = eMsg.data.selectors;
                if (selectors && selectors.length > 0) {
                    // Auto-pick best selector and add to escape list
                    var bestSel = selectors[0].selector;
                    addEscapePill(bestSel);
                    // Show a brief toast inside the modal header
                    var $label = $('#cop-modal-active-field-label');
                    $label.text('✅ اضافه شد: ' + bestSel).css('background','#16a34a');
                    setTimeout(function() {
                        $label.text('🚫 حذف المان').css('background','#dc2626');
                    }, 2000);
                }
            }
        });

        // Reset escape mode on modal close
        var _origModalCancel = $('#cop-modal-cancel').off('click').on('click', function(e) {
            e.preventDefault();
            isEscapeMode = false;
            $('#cop-modal-active-field-label').css('background', '#4f46e5');
            $('#cop-visual-modal').fadeOut(200, function() {
                $('#cop-visual-iframe').attr('src', '');
                $('#cop-selector-choices-container').hide();
                $('#cop-selector-choices-list').empty();
            });
        });
    });
    </script>

    <!-- Visual Selector Fullscreen Modal Overlay -->
    <div id="cop-visual-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 999999; direction: rtl;">
        <div style="background: #1e293b; color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); height: 50px; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-weight: bold; font-size: 15px;">🎯 در حال انتخاب بصری برای:</span>
                <span id="cop-modal-active-field-label" style="background: #4f46e5; color: #fff; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 13px;">-</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 12px; color: #94a3b8;">راهنما: روی المان مورد نظر کلیک کنید تا گزینه‌های سلکتور نمایش داده شوند. برای لغو ESC را فشار دهید.</span>
                <button type="button" id="cop-modal-cancel" class="button button-link" style="color: #ef4444; text-decoration: none; font-weight: bold; border: none; background: none; cursor: pointer; font-size: 13px;">❌ لغو و بستن</button>
            </div>
        </div>
        
        <!-- Alternatives choice panel -->
        <div id="cop-selector-choices-container" style="display: none; background: #fff; border-bottom: 2px solid #cbd5e1; padding: 15px 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); max-height: 200px; overflow-y: auto;">
            <h4 style="margin: 0 0 10px 0; color: #1e293b; font-size: 14px;">💡 مسیرهای سلکتور کاندیدا (یکی از گزینه‌های زیر را جهت انتخاب ثبت کنید):</h4>
            <div id="cop-selector-choices-list" style="display: flex; flex-direction: column; gap: 8px;"></div>
        </div>

        <div style="width: 100%; height: calc(100vh - 50px); background: #fff;">
            <iframe id="cop-visual-iframe" src="" sandbox="allow-same-origin allow-scripts allow-forms" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>

    <p style="text-align:left;direction:ltr;">
        <label for="title_selector">Title Selector:</label><br>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="title_selector" name="title_selector" class="widefat" style="flex: 1;" value="<?php echo esc_attr($title_selector); ?>">
            <button type="button" class="button cop-btn-visual-select" data-target="#title_selector" style="white-space: nowrap;">🎯 انتخاب بصری</button>
        </div><br>

        <label for="img_selector">Image Selector:</label><br>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="img_selector" name="img_selector" class="widefat" style="flex: 1;" value="<?php echo esc_attr($img_selector); ?>">
            <button type="button" class="button cop-btn-visual-select" data-target="#img_selector" style="white-space: nowrap;">🎯 انتخاب بصری</button>
        </div><br>

        <label for="lead_selector">Lead Selector:</label><br>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="lead_selector" name="lead_selector" class="widefat" style="flex: 1;" value="<?php echo esc_attr($lead_selector); ?>">
            <button type="button" class="button cop-btn-visual-select" data-target="#lead_selector" style="white-space: nowrap;">🎯 انتخاب بصری</button>
        </div><br>

        <label for="body_selector">Body Selector:</label><br>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="body_selector" name="body_selector" class="widefat" style="flex: 1;" value="<?php echo esc_attr($body_selector); ?>">
            <button type="button" class="button cop-btn-visual-select" data-target="#body_selector" style="white-space: nowrap;">🎯 انتخاب بصری</button>
        </div><br>

        <label for="bup_date_selector">Bup Date Selector:</label><br>
        <input type="text" id="bup_date_selector" name="bup_date_selector" class="widefat"
            value="<?php echo esc_attr($bup_date_selector); ?>"><br><br>

        <label for="category_selector">Category Selector:</label><br>
        <input type="text" id="category_selector" name="category_selector" class="widefat"
            value="<?php echo esc_attr($category_selector); ?>"><br><br>

        <label for="tags_selector">Tags Selector:</label><br>
        <input type="text" id="tags_selector" name="tags_selector" class="widefat"
            value="<?php echo esc_attr($tags_selector); ?>"><br><br>

        <div class="cop-form-card" style="margin-bottom: 20px; direction: rtl;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; color: var(--cop-text-primary, #1e293b); display: flex; align-items: center; gap: 8px;">
                        🚫 المان‌های حذف‌شونده از بدنه خبر
                    </h4>
                    <p style="margin: 0; font-size: 12px; color: var(--cop-text-muted, #64748b);">
                        CSS selectorهایی که قبل از استخراج بدنه خبر حذف می‌شوند (تبلیغات، اخبار مرتبط و…)
                    </p>
                </div>
                <div style="display: flex; gap: 8px; flex-shrink: 0;">
                    <button type="button" id="cop_btn_suggest_escape" class="cop-btn cop-btn-neutral" style="font-size: 12px;" title="پیشنهاد خودکار المان‌های قابل حذف از طریق تحلیل صفحه نمونه">
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        پیشنهاد هوشمند
                    </button>
                    <button type="button" class="cop-btn cop-btn-secondary cop-btn-visual-select-escape" title="انتخاب بصری المان برای حذف از روی صفحه نمونه">
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        انتخاب بصری
                    </button>
                </div>
            </div>

            <!-- Pills container -->
            <div id="cop-escape-pills" style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 40px; padding: 10px; background: var(--cop-bg-secondary, #f8fafc); border: 1px solid var(--cop-border, #e2e8f0); border-radius: 8px; margin-bottom: 12px;">
                <?php
                $escape_arr = cop_flatten_escape_elements($escape_elements);
                if (empty($escape_arr)): ?>
                    <span id="cop-escape-empty-hint" style="color: #94a3b8; font-size: 12px; align-self: center; width: 100%; text-align: center;">
                        هنوز سلکتوری اضافه نشده. از دکمه‌های بالا یا فیلد زیر اضافه کنید.
                    </span>
                <?php else: $escape_arr = array_values($escape_arr); ?>
                    <span id="cop-escape-empty-hint" style="display:none;"></span>
                <?php endif;
                foreach ($escape_arr as $esc_sel): ?>
                    <span class="cop-escape-pill" style="display: inline-flex; align-items: center; gap: 6px; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-family: monospace; direction: ltr;">
                        <?php echo esc_html($esc_sel); ?>
                        <button type="button" class="cop-escape-pill-remove" data-selector="<?php echo esc_attr($esc_sel); ?>" style="background: none; border: none; cursor: pointer; color: #b91c1c; padding: 0; line-height: 1; font-size: 14px; display: flex;" title="حذف این سلکتور">×</button>
                        <input type="hidden" name="escape_elements[]" value="<?php echo esc_attr($esc_sel); ?>">
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Manual input row -->
            <div style="display: flex; gap: 8px;">
                <input type="text" id="cop-escape-manual-input" placeholder="مثال: div.ad-box یا .related-news" style="flex: 1; padding: 8px 12px; border: 1px solid var(--cop-border, #e2e8f0); border-radius: 8px; font-size: 13px; font-family: monospace; direction: ltr;" />
                <button type="button" id="cop-escape-add-btn" class="cop-btn cop-btn-secondary" style="font-size: 13px; white-space: nowrap;">
                    + افزودن
                </button>
            </div>

            <!-- Smart Suggest Results -->
            <div id="cop-escape-suggest-wrapper" style="display:none; margin-top: 14px;">
                <div style="font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 10px;">
                    ✨ پیشنهادات هوشمند — روی هر مورد کلیک کنید تا اضافه شود:
                </div>
                <div id="cop-escape-suggest-list" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
            </div>
        </div>
        <!-- /Escape Elements Manager -->


        <label for="source_root_link">Source root Link: </label><br>
        <input type="text" id="source_root_link" name="source_root_link" class="widefat"
            value="<?php echo esc_attr($source_root_link); ?>"><br><br>

        <label for="source_feed_link">Source Feed Link:</label><br>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="source_feed_link" name="source_feed_link" class="widefat" style="flex: 1;" value="<?php echo esc_attr($source_feed_link); ?>">
            <button type="button" id="cop_btn_discover_feed" class="button button-secondary" style="white-space: nowrap;">🔍 کشف خودکار فید</button>
        </div>
        <div id="cop_discovered_feeds_wrapper" style="display: none; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-top: 8px; direction: rtl; text-align: right;">
            <strong style="font-size: 13px; color: #1e293b;">💡 آدرس‌های فید معتبر کشف‌شده (برای اعمال، روی آن کلیک کنید):</strong>
            <div id="cop_discovered_feeds_list" style="margin-top: 8px;"></div>
        </div><br>

<?php
error_log('start need value:'.get_post_meta($post->ID, 'need_to_merge_guid_link', true));
?>
        </label><br>
    </p>
    <?php
}

function save_custom_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Verify nonce
    if (!isset($_POST['cop_resource_nonce']) || !wp_verify_nonce($_POST['cop_resource_nonce'], 'cop_save_resource_meta')) {
        return;
    }

    // Check post type
    if (get_post_type($post_id) !== 'resource') {
        return;
    }

    // Save meta values
    if (isset($_POST['title_selector'])) {
        update_post_meta($post_id, 'title_selector', sanitize_text_field($_POST['title_selector']));
    }
    if (isset($_POST['img_selector'])) {
        update_post_meta($post_id, 'img_selector', sanitize_text_field($_POST['img_selector']));
    }
    if (isset($_POST['lead_selector'])) {
        update_post_meta($post_id, 'lead_selector', sanitize_text_field($_POST['lead_selector']));
    }
    if (isset($_POST['body_selector'])) {
        update_post_meta($post_id, 'body_selector', sanitize_text_field($_POST['body_selector']));
    }
    if (isset($_POST['bup_date_selector'])) {
        update_post_meta($post_id, 'bup_date_selector', sanitize_text_field($_POST['bup_date_selector']));
    }
    if (isset($_POST['category_selector'])) {
        update_post_meta($post_id, 'category_selector', sanitize_text_field($_POST['category_selector']));
    }
    if (isset($_POST['tags_selector'])) {
        update_post_meta($post_id, 'tags_selector', sanitize_text_field($_POST['tags_selector']));
    }

    // Save escape_elements as JSON array (from Pill Manager hidden inputs)
    if (isset($_POST['escape_elements']) && (is_array($_POST['escape_elements']) || is_string($_POST['escape_elements']))) {
        $selectors = cop_flatten_escape_elements($_POST['escape_elements']);
        update_post_meta($post_id, 'escape_elements', wp_json_encode($selectors, JSON_UNESCAPED_UNICODE));
    } else {
        // No pills submitted → save empty array
        update_post_meta($post_id, 'escape_elements', '[]');
    }
    if (isset($_POST['source_root_link'])) {
        update_post_meta($post_id, 'source_root_link', sanitize_text_field($_POST['source_root_link']));
    }
    if (isset($_POST['source_feed_link'])) {
        update_post_meta($post_id, 'source_feed_link', sanitize_text_field($_POST['source_feed_link']));
    }
    if (isset($_POST['cop_sample_url'])) {
        update_post_meta($post_id, 'cop_sample_url', esc_url_raw($_POST['cop_sample_url']));
    }
    
    
    // save need_to_merge_guid_link
    if (isset($_POST['need_to_merge_guid_link'])) { 
        error_log('if, need to merge: ' . $_POST['need_to_merge_guid_link']);
        update_post_meta($post_id, 'need_to_merge_guid_link', 1);
    }
    else {
        update_post_meta($post_id, 'need_to_merge_guid_link', 0);
    }

    // Clear API caches so clients get the latest configuration immediately
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cop_val_%' OR option_name LIKE '_transient_timeout_cop_val_%'");
}
add_action('save_post_resource', 'save_custom_meta_box');

// ---------------------------------------------------------
// Phase 5.2: Block Resource deletion if it is used by subscriptions
// ---------------------------------------------------------
add_action('wp_trash_post', 'cop_block_resource_trash_if_in_use');
function cop_block_resource_trash_if_in_use($post_id) {
    if (get_post_type($post_id) === 'resource') {
        global $wpdb;
        $in_use = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = 'subscription_resources_ids' 
            AND meta_value LIKE %s
        ", '%"' . $post_id . '"%'));
        
        if ($in_use > 0) {
            wp_die('<div style="font-family:tahoma,sans-serif; text-align:right; direction:rtl;"><h3>هشدار: حذف منبع مسدود شد!</h3><p>این منبع توسط <b>' . $in_use . '</b> اشتراک در حال استفاده است. حذف آن باعث اختلال در کلاینت‌های متصل می‌شود.</p><a href="javascript:history.back()" style="display:inline-block; padding:10px 20px; background:#f59e0b; color:#fff; text-decoration:none; border-radius:5px;">بازگشت</a></div>');
        }
    }
}