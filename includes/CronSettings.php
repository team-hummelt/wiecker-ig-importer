<?php

namespace WiIg\Importer;

trait CronSettings
{
    protected array $cron_default_values;
    protected int $cron_aktiv = 1;
    protected int $delete_duplicate = 1;
    protected string $selected_cron_sync_interval = 'daily';
    protected int $selected_post_type = 0;
    protected int $max_post_sync_selected = 10;
    protected string $select_user_role = 'manage_options';
    protected bool $bootstrap_css_aktiv = false;
    protected bool $bootstrap_js_aktiv = false;
    protected int $cron_update_post = 0;

    protected function get_cron_defaults(string $args = '', $id = null): array
    {
        $this->cron_default_values = [
            'cron_settings' => [
                'cron_aktiv' => $this->cron_aktiv,
                'delete_duplicate' => $this->delete_duplicate,
                'selected_cron_sync_interval' => $this->selected_cron_sync_interval,
                'selected_post_type' => $this->selected_post_type,
                'max_post_sync_selected' => $this->max_post_sync_selected,
                'bootstrap_css_aktiv' => $this->bootstrap_css_aktiv,
                'bootstrap_js_aktiv' => $this->bootstrap_js_aktiv,
                'selected_user_role' => $this->select_user_role,
                'term_id' => ''
            ],
            'max_post_sync' => [
                "0" => [
                    'value' => 5
                ],
                "1" => [
                    'value' => 10
                ],
                "2" => [
                    'value' => 20
                ],
                "3" => [
                    'value' => 30
                ],
                "4" => [
                    'value' => 40
                ],
                "5" => [
                    'value' => 40
                ],
                "6" => [
                    'value' => 50
                ],
            ],
            'select_api_sync_interval' => [
                "0" => [
                    "id" => 'hourly',
                    "bezeichnung" => __('Stündlich', 'wiecker-ig-importer'),
                ],
                "3" => [
                    'id' => 'daily',
                    "bezeichnung" => __('Einmal täglich', 'wiecker-ig-importer'),
                ],
                "1" => [
                    'id' => 'twicedaily',
                    "bezeichnung" => __('Zweimal Täglich', 'wiecker-ig-importer'),
                ],
                "4" => [
                    'id' => 'weekly',
                    "bezeichnung" => __('Einmal wöchentlich', 'wiecker-ig-importer'),
                ],
            ],
            'select_user_role' => [
                "0" => [
                    'value' => 'read',
                    'name' => __('Abonnent', 'wiecker-ig-importer')
                ],
                "1" => [
                    'value' => 'edit_posts',
                    'name' => __('Mitarbeiter', 'wiecker-ig-importer')
                ],
                "2" => [
                    'value' => 'publish_posts',
                    'name' => __('Autor', 'wiecker-ig-importer')
                ],
                "3" => [
                    'value' => 'publish_pages',
                    'name' => __('Redakteur', 'wiecker-ig-importer')
                ],
                "4" => [
                    'value' => 'manage_options',
                    'name' => __('Administrator', 'wiecker-ig-importer')
                ],
            ],

            'select_post_status' => [
                '0' => [
                    'id' => 1,
                    "name" => "Publish",
                    "value" => 'publish'

                ],
                '1' => [
                    'id' => 2,
                    "name" => "Draft",
                    'value' => 'draft'

                ],
            ],
            'select_post_title' => [
                '0' => [
                    'id' => 1,
                    "name" => __('RSS Title', 'wiecker-ig-importer'),
                    "value" => 'title'
                ],
                '1' => [
                    'id' => 2,
                    "name" => __('RSS Channel Title', 'wiecker-ig-importer'),
                    'value' => 'channel_title'
                ],
                '2' => [
                    'id' => 3,
                    "name" => __('RSS Date', 'wiecker-ig-importer'),
                    'value' => 'pubDate'
                ],
            ],
            'select_post_date' => [
                '0' => [
                    'id' => 1,
                    'name' => __('RSS Date', 'wiecker-ig-importer'),
                    'type' => 'rss'
                ],
                '1' => [
                    'id' => 2,
                    'name' => __('Post Date', 'wiecker-ig-importer'),
                    'type' => 'post'
                ],
            ],
            'select_post_content' => [
                '0' => [
                    'id' => 1,
                    'name' => __('RSS Content', 'wiecker-ig-importer'),
                    'type' => 'content'
                ],
                '1' => [
                    'id' => 2,
                    'name' => __('RSS Description', 'wiecker-ig-importer'),
                    'type' => 'description'
                ],
                '2' => [
                    'id' => 3,
                    'name' => __('RSS Link', 'wiecker-ig-importer'),
                    'type' => 'link'
                ],
            ],
            'select_date_format' => [
                '0' => [
                    'name' => 'm.Y',
                    'id' => 1
                ],
                '1' => [
                    'name' => 'd.m.Y',
                    'id' => 2
                ],
                '2' => [
                    'name' => 'F j, Y',
                    'id' => 3
                ],
                '3' => [
                    'name' => 'anderes Format',
                    'id' => 4
                ],
            ],

            'select_gb_count_output' => [
                '0' => [
                    'label' => __('individuell', 'wiecker-ig-importer'),
                    'value' => '0'
                ],
                '1' => [
                    'label' => __('alle', 'wiecker-ig-importer'),
                    'value' => '-1'
                ],
                '2' => [
                    'label' => '5',
                    'value' => '5'
                ],
                '3' => [
                    'label' => '10',
                    'value' => '10'
                ],
                '4' => [
                    'label' => '15',
                    'value' => '15'
                ],
                '5' => [
                    'label' => '20',
                    'value' => '20'
                ],
                '6' => [
                    'label' => '30',
                    'value' => '30'
                ],
                '7' => [
                    'label' => '50',
                    'value' => '50'
                ],
            ],
        ];

        if ($args) {
            if ($id) {
                foreach ($this->cron_default_values[$args] as $tmp) {
                    if (isset($tmp['id']) && $tmp['id'] == $id) {
                        return $tmp;
                    }
                }
            }
            return $this->cron_default_values[$args];
        } else {
            return $this->cron_default_values;
        }
    }

    protected function twig_language()
    {
        $lang = [
            __('Import title', 'wiecker-ig-importer'),
            __('Post Type', 'wiecker-ig-importer'),
            __('Category', 'wiecker-ig-importer'),
            __('Imported', 'wiecker-ig-importer'),
            __('Last', 'wiecker-ig-importer'),
            __('Next', 'wiecker-ig-importer'),
            __('Import Status', 'wiecker-ig-importer'),
            __('Active', 'wiecker-ig-importer'),
            __('Import', 'wiecker-ig-importer'),
            __('Edit', 'wiecker-ig-importer'),
            __('Delete', 'wiecker-ig-importer'),
            __('RSS Feed Importer', 'wiecker-ig-importer'),
            __('Settings', 'wiecker-ig-importer'),
            __('RSS Feed Import', 'wiecker-ig-importer'),
            __('Overview', 'wiecker-ig-importer'),
            __('Add import', 'wiecker-ig-importer'),
            __('Manage imports', 'wiecker-ig-importer'),
            __('back', 'wiecker-ig-importer'),
            __('Channel', 'wiecker-ig-importer'),
            __('Channel Title', 'wiecker-ig-importer'),
            __('no data', 'wiecker-ig-importer'),
            __('Channel Link', 'wiecker-ig-importer'),
            __('Channel language', 'wiecker-ig-importer'),
            __('Date of publication', 'wiecker-ig-importer'),
            __('last publication date', 'wiecker-ig-importer'),
            __('Copyright', 'wiecker-ig-importer'),
            __('import new RSS feed', 'wiecker-ig-importer'),
            __('Edit RSS feed', 'wiecker-ig-importer'),
            __('Import name', 'wiecker-ig-importer'),
            __('RSS Feed Url', 'wiecker-ig-importer'),
            __('from', 'wiecker-ig-importer'),
            __('to', 'wiecker-ig-importer'),
            __('Filter by time period', 'wiecker-ig-importer'),
            __('Post Taxonomy', 'wiecker-ig-importer'),
            __('Post Status', 'wiecker-ig-importer'),
            __('Post Title', 'wiecker-ig-importer'),
            __('Post content', 'wiecker-ig-importer'),
            __('Post date', 'wiecker-ig-importer'),
            __('Import posts per update', 'wiecker-ig-importer'),
            __('Delete automatically', 'wiecker-ig-importer'),
            __('Number of days', 'wiecker-ig-importer'),
            __('Useful if you want to remove obsolete articles automatically. If the entry remains empty, the imported articles will not be deleted automatically.', 'wiecker-ig-importer'),
            __('Import active', 'wiecker-ig-importer'),
            __('Remove duplicate entries', 'wiecker-ig-importer'),
            __('Delete items created for this import after a specified number of days.', 'wiecker-ig-importer'),
            __('Save', 'wiecker-ig-importer'),
            __('Cancel', 'wiecker-ig-importer'),
            __('Synchronization settings', 'wiecker-ig-importer'),
            __('Cronjob active', 'wiecker-ig-importer'),
            __('next synchronization on', 'wiecker-ig-importer'),
            __('at', 'wiecker-ig-importer'),
            __('Synchronization interval', 'wiecker-ig-importer'),
            __('Minimum requirement for plugin usage', 'wiecker-ig-importer'),
            __('Clock', 'wiecker-ig-importer'),

        ];
    }

    protected function js_language()
    {
        $jsLang = [
            'checkbox_delete_label' => __('Delete all imported posts?', 'wiecker-ig-importer'),
            'Cancel' => __('Cancel', 'wiecker-ig-importer'),
            'delete_title' => __('Really delete import?', 'wiecker-ig-importer'),
            'delete_subtitle' => __('The deletion cannot be undone.', 'wiecker-ig-importer'),
            'delete_btn_txt' => __('Delete import', 'wiecker-ig-importer'),
        ];

        return $jsLang;

    }
}