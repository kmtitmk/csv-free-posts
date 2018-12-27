<?php
class ImportClass
{
    //CSVのパス
    private $update_csv_path;
    //CSVデータの配列
    private $csv_update_array = array();

    public function __construct()
    {
        //get_dataを取得
        if (CsvFreePosts::$get_data['_wpnonce_csv_free_posts']) {
            $this->update_csv_path = wp_upload_dir('url') . CsvFreePosts::$get_data['import'];

            //csv取得、投稿のアップデート。リダイレクトするためinitで実行
            add_action('init', array($this, 'get_csv_array'));
        }
    }

    //アップデート
    public function csv_free_posts()
    {
        if (CsvFreePosts::$get_data['alert_message']) {
            $redirect = remove_query_arg('alert_message');
            $alert_message = CsvFreePosts::$get_data['alert_message'];
            print <<< EOT
            <script>
                alert("{$alert_message}");
                location.href="{$redirect}";
            </script>
EOT;
        }
        require_once(CsvFreePosts::$full_path . 'includes/update.php');
        ?>
        <div class="wrap">
            <h1>アップデート</h1>	
            <form method="get" action="<?php print $_SERVER['SCRIPT_NAME']; ?>">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">CSVのアップロード</th>
                            <td>
                                <input name="import" type="text" value="<?php print CsvFreePosts::$get_data['import']; ?>" class="regular-text" readonly>
                                <span class="button js-add-csv">CSVを追加</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php wp_nonce_field('nonce_csv_free_posts', '_wpnonce_csv_free_posts'); ?>
                <input type="hidden" name="page" value="csv_free_posts">
                <p class="submit">
                    <button type="submit" id="update_url" class="button-primary" >更新する</button>			
                    <img class="load" style="display: none;" src="<?php print CsvFreePosts::$path; ?>/data/images/icon_loader.gif">
                </p>
            </form>
        </div>
        <?php
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
        }

        //投稿のアップデート実行
        $this->update_post();
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
