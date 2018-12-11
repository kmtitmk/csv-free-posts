<?php

/*
Plugin Name: Csv Free Posts
Plugin URI:
Description: CSVを使って自由に投稿を管理できます。タイトル、カテゴリー、タグ、コンテンツの投稿・修正が可能になります。
Version: 1.0.0
Author: Kazuki Matsui
Author URI:
*/

$csv_free_posts = new CsvFreePosts();
class CsvFreePosts
{
    //パス
    private $path;
    private $full_path;
    //GETデータ
    private $get_data;
    //エクスポート
    private $export_html;
    private $export_post_data;
    private $key_items = array('ID', 'post_title', 'post_name', 'post_content', 'post_category', 'tags_input', 'post_type');
    private $export_csv_file;
    //CSV取得
    private $csv_update_array = array();


    //設定ページ
    public function __construct()
    {
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
        $this->get_data = filter_input_array(INPUT_GET, $args);
        
        //プラグインまでのパスを準備しておく
        $this->full_path = plugin_dir_path(__FILE__);
        $this->path = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__), '', plugin_basename(__FILE__));

        if ($this->get_data['_wpnonce_csv_free_posts']) {
            $this->update_csv_path = WP_CONTENT_DIR . $this->get_data['import'];
            add_action('init', array($this, 'get_csv_array'));
        }

        add_action('admin_menu', array($this, 'csv_free_menu'));
        add_action('admin_enqueue_scripts', array($this, 'my_scripts_method'));
    }


    //インサートの実行
    public function csv_free_menu()
    {
        add_menu_page('Csv Free Posts', 'Csv Free Posts', 'manage_options', 'csv_free_posts', array($this, 'csv_free_posts'), 'dashicons-welcome-learn-more', 80);
        add_submenu_page('csv_free_posts', 'エクスポート', 'エクスポート', 'manage_options', 'csv_free_posts_sub_export', array($this, 'csv_free_posts_sub_export'));
    }


    //アップデート
    public function csv_free_posts()
    {
        if ($this->get_data['alert_message']) {
            $redirect = remove_query_arg('alert_message');
            print <<< EOT
            <script>
                alert("{$this->get_data[alert_message]}");
                location.href="{$redirect}";
            </script>
EOT;
        }
        include 'include/update.php';
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
        include 'include/export.php';
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
        wp_enqueue_script('my_admin_script', $this->path.'/assets/js/functions.js', array('jquery'), '', true);
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


    //csvの取得
    public function get_csv_array()
    {
        $csv_key = array();
        $csv_val = array();
        if (file_exists($this->update_csv_path)) {
            $fp = new SplFileObject($this->update_csv_path);
            $fp->setFlags(SplFileObject::READ_CSV);
            $c = 0;
            foreach ($fp as $line) {
                if ($c == 0) {
                    $csv_key = $line;
                } else {
                    $csv_val[] = $line;
                }
                $c ++;
            }
            unset($line);

            $c = 0;
            foreach ($csv_val as $value) {
                $key_c = 0;
                foreach ($csv_key as $key) {
                    if ($key == 'ID') {
                        $error_id = 1;

                        //IDの有無・正誤のチェック
                        if ($value[$key_c]) {
                            $post_data = get_post($value[$key_c]);
                        } else {
                            $post_data = false;
                        }

                        if ($post_data == false) {
                            $value[$key_c] = 'error';
                        }
                    }
                    $this->csv_update_array[$c][$key] = $value[$key_c];
                    $key_c ++;
                }
                unset($key);
                $c ++;
            }
            unset($value);

            //更新の実行
            $this->update_post();
        }
    }


    //投稿のアップデート
    private function update_post()
    {
        $nonce = $_REQUEST['_wpnonce_csv_free_posts'];
        $nonce_check = wp_verify_nonce($nonce, 'nonce_csv_free_posts');
        if (!$nonce_check) {
            $this->alert_message = '不正な投稿を検知しました。';
        } else {
            $c = 0;
            $resurt = array();
            foreach ($this->csv_update_array as $v) {

                //カテゴリー
                if (!isset($v['post_category'])) {
                    $v['post_category'] = '';
                }
                $v['post_category'] = explode(';', $v['post_category']);

                //タグ
                if (!isset($v['tags_input'])) {
                    $v['tags_input'] = '';
                }
                $v['tags_input'] = explode(';', $v['tags_input']);

                $my_post = array(
                    'post_title' => $v['post_title'],
                    'post_name' => $v['post_name'],
                    'post_content' => $v['post_content'],
                    'post_status' => 'publish',
                    'post_category' => $v['post_category'],
                    'tags_input' => $v['tags_input'],
                    'post_type' => $v['post_type'],
                );

                //idがerrorじゃなければ投稿の編集
                if ($v['ID'] != 'error') {
                    $my_post['ID'] = $v['ID'];
                }
                $resurt[] = wp_insert_post($my_post);
            }
            unset($k, $v);
        
            $error_count = array_count_values($resurt);
            if (isset($error_count[0])) {
                $this->alert_message = $error_count[0] . '件の更新エラーがありました。';
            } else {
                $this->alert_message = '更新が完了しました。';
            }
        }

        //リダイレクトしてアラートの実行
        $url = add_query_arg(array('page'=>'csv_free_posts', 'alert_message'=>$this->alert_message), $_SERVER['SCRIPT_NAME']);
        wp_redirect($url);
        exit;
    }
}
