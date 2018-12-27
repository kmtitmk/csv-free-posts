<?php

/*
Plugin Name: Csv Free Posts
Plugin URI:
Description: CSVを使って自由に投稿を管理できます。タイトル、カテゴリー、タグ、コンテンツの投稿・修正が可能になります。
Version: 1.0.0
Author: Kazuki Matsui
Author URI:
*/

class CsvFreePosts
{
    //パス
    public static $path;
    public static $full_path;
    public static $update_csv_path;

    //GETデータ
    public static $get_data;

    //エクスポート
    private $export_html;
    private $export_post_data;
    private $key_items = array('ID', 'post_title', 'post_name', 'post_content', 'post_category', 'tags_input', 'post_type');
    private $export_csv_file;

    //実行
    public function init_actions()
    {

        //外部ファイル
        add_action('admin_enqueue_scripts', array(__CLASS__, 'my_scripts_method'));

        //メニュー
        add_action('admin_menu', array(__CLASS__, 'csv_free_menu'));

        //プラグインまでのパスを準備しておく
        self::$path = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__), '', plugin_basename(__FILE__));
        self::$full_path = plugin_dir_path(__FILE__);

        //GETデータ取得用のフィルター
        $args = array(
            'alert_message' => array(
                'filter' => FILTER_SANITIZE_STRING,
            ),
            'check_post_type' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'flags'  => FILTER_REQUIRE_ARRAY,
            ),
            'import' => array(
                'filter' => FILTER_SANITIZE_STRING,
            ),
            '_wpnonce_csv_free_posts' => array(
                'filter' => FILTER_SANITIZE_STRING,
            ),
            '_wpnonce_csv_free_posts_export' => array(
                'filter' => FILTER_SANITIZE_STRING,
            ),
        );
        self::$get_data = filter_input_array(INPUT_GET, $args);

        //インポートの実行
        require_once('includes/import-class.php');
        $import_class = new ImportClass();
    }


    //ページの設定
    public function csv_free_menu()
    {
        add_menu_page('Csv Free Posts', 'Csv Free Posts', 'manage_options', 'csv_free_posts', array('ImportClass', 'csv_free_posts'), 'dashicons-welcome-learn-more', 80);
        //add_submenu_page('csv_free_posts', 'エクスポート', 'エクスポート', 'manage_options', 'csv_free_posts_sub_export', array($this, 'csv_free_posts_sub_export'));
    }


    //エクスポートの処理
    public function csv_free_posts_sub_export()
    {
        //投稿タイプの選択
        $args = array(
            'public' => true,
        );
        $post_types = get_post_types($args, 'names');
        unset($post_types['attachment']);
        $post_typ_array = array();
        $post_typ_tag = '<tr><th scope="row">投稿タイプ選択</th><td><fieldset>';
        foreach ($post_types as $v) {
            if (is_array($this->get_data['check_post_type'])
                && in_array($v, $this->get_data['check_post_type'])) {
                $post_typ_tag .= sprintf('<label><input type="checkbox" name="check_post_type[]" value="%s" checked="checked">%1$s</label><br>', $v);
            } else {
                $post_typ_tag .= sprintf('<label><input type="checkbox" name="check_post_type[]" value="%s">%1$s</label><br>', $v);
            }
            $post_typ_array[] = $v;
        }
        unset($v);
        $post_typ_tag .= '<p class="description">(選択した投稿タイプがエクスポートされます)</p></fieldset></td></tr>';
        $this->export_items();
        require_once('includes/export.php');
    }


    //エクスポート用tableタグ
    private function export_items()
    {
        $args = array(
            'post_type' => $this->get_data['check_post_type'],
            'posts_per_page' => -1,
        );
        $this->export_post_data = get_posts($args);

        //tableの項目
        $this->export_html = '<tr>';
        foreach ($this->key_items as $item) {
            $this->export_html .= '<td>' . $item . '</td>';
        }
        unset($item);
        $this->export_html .= '</tr>';

        //tableの内容
        foreach ($this->export_post_data as $data) {
            $this->export_html .= '<tr>';

            //カテゴリ
            $cats = get_the_terms($data->ID, 'category');
            $cats_array = array();
            if (is_array($cats)) {
                foreach ($cats as $v) {
                    $cats_array[] = $v->term_id;
                }
                unset($v);
            }
            
            //タグ
            $tags = get_the_tags($data->ID);
            $tags_array = '';
            if (is_array($tags)) {
                foreach ($tags as $v) {
                    $tags_array = $v->name;
                }
                unset($v);
            }

            foreach ($this->key_items as $item) {
                if ($item == 'post_content') {
                    $data->$item = htmlspecialchars($data->$item);
                } elseif ($item == 'post_category') {
                    $data->$item = implode(';', $cats_array);
                } elseif ($item == 'tags_input') {
                    $data->$item = str_replace(',', ';', $tags_array);
                }
                $this->export_html .= '<td>' . $data->$item . '</td>';
            }
            unset($item);
            $this->export_html .= '</tr>';
        }
        unset($data);
    }


    //CSVのエクスポートファイル生成
    private function export_csv_file()
    {
        $data = array($this->key_items);
        $c = 0;
        foreach ($this->export_post_data as $pos_data) {
            $k_count = 0;
            foreach ($this->key_items as $k) {
                if ($k == 'post_content') {
                    $data[$c][$k_count] = htmlspecialchars_decode($pos_data->$k);
                } else {
                    $data[$c][$k_count] = $pos_data->$k;
                }
                $k_count ++;
            }
            $c ++;
        }
        unset($v);
        $fp = fopen($this->full_path . '/data/csv/export.csv', 'w');
        foreach ($data as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
    }


    //jsの読み込み
    public function my_scripts_method()
    {
        wp_enqueue_media();
        wp_enqueue_script('my_admin_script', self::$path . '/assets/js/functions.js', array('jquery'), '', true);
        if (function_exists('wp_add_inline_script')) {
            $content_url = content_url();
            $tag = <<<EOT
            var content_path = '$content_url';
            add_csv();
            execution_button();
EOT;
            wp_add_inline_script('my_admin_script', $tag, 'after');
        }
    }
}

add_action('plugins_loaded', array( 'CsvFreePosts', 'init_actions' ));